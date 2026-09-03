<?php

namespace App\Services;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Support\Concerns\CleansUtf8;
use App\Support\EventDetektor;
use App\Support\KlasifikasiTabel;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KontakImportService
{
    use CleansUtf8;

    /**
     * Kata kunci untuk menolak baris/sheet teknis (bukan data kontak).
     */
    public const JUNK_WORDS = [
        'koordinasi', 'serah terima', 'loading', 'vendor', 'doorprize',
        'persiapan', 'qty', 'checklist', 'cheklist', 'spanduk', 'banner',
        'sound', 'sound system', 'tata letak', 'panitia',
        'cek kelengkapan', 'cek nama', 'memberikan nametag', 'pita pembukaan',
        'arrange acara', 'jumlah booth', 'jumlah meja', 'pastikan kelistrikan',
        'list booth', 'list umkm', 'floor plan', 'fix sponsor', 'sponsor final',
        'sponsorship', 'daftar sponsor', 'list doorprize', 'negosiasi',
        'urutan support', 'prioritas sponsor', 'potential sponsor', 'no respon',
        'total nominal', 'tidak berpartisipasi',
    ];

    /**
     * Baca file (xlsx/xls/ods/csv/tsv) menjadi array baris canonical.
     *
     * - Header dideteksi otomatis (bisa di baris manapun, bukan baris pertama).
     * - Nama kolom dicocokkan secara fuzzy (Indonesia/Inggris, spasi/garis-bawah,
     *   kolom gabungan seperti "PIC / No. HP").
     * - Semua sheet dibaca; tiap sheet di-scan lewat header-nya sendiri.
     * - Nama & nomor telepon yang tercampur dalam satu sel dipisahkan otomatis.
     *
     * @return array<int, array<string, mixed>> Kolom: sheet, nama_perusahaan, industri, nama, no_telepon_mentah, catatan, nama_event, nama_kategori, kategori_key, tanggal_mulai, venue
     */
    public function extractRows(string $path, ?string $extension = null): array
    {
        $extension ??= strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'tsv'], true)) {
            $rows = $this->mapColumns($this->extractRowsFromDelimited($path, $extension));

            foreach ($rows as &$row) {
                $row['sheet'] = null;
                $row['nama_event'] = null;
                $row['nama_kategori'] = null;
                $row['kategori_key'] = null;
                $row['tanggal_mulai'] = null;
                $row['venue'] = null;
            }

            return $rows;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $fileName = basename($path);
        $detektor = new EventDetektor;
        $rows = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $cells = array_values($sheet->toArray(null, true, false, false));

            $meta = [];
            foreach (array_slice($cells, 0, 8) as $rowCells) {
                foreach ($rowCells as $value) {
                    if ($value !== null && trim((string) $value) !== '') {
                        $meta[] = $this->cleanUtf8(trim((string) $value));
                    }
                }
            }

            $detected = $detektor->parse($this->cleanUtf8($sheet->getTitle()), $fileName, $meta);

            foreach ($this->mapColumns($cells) as $row) {
                $row['sheet'] = $this->cleanUtf8($sheet->getTitle());
                $row['nama_event'] = $detected['event'];
                $row['nama_kategori'] = $detected['kategori_nama'];
                $row['kategori_key'] = $detected['kategori_key'];
                $row['tanggal_mulai'] = $detected['tanggal_mulai'];
                $row['venue'] = $detected['venue'];
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function extractRowsFromDelimited(string $path, string $extension): array
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setDelimiter($extension === 'tsv' ? "\t" : ',');

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $rows[] = array_values($record);
        }

        return $rows;
    }

    /**
     * Petakan baris mentah sebuah sheet/CSV menjadi baris canonical.
     *
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array<string, mixed>>
     */
    protected function mapColumns(array $rawRows): array
    {
        $headerIndex = $this->findHeaderRow($rawRows);
        if ($headerIndex === null) {
            return [];
        }

        $header = array_values($rawRows[$headerIndex]);

        $companyIdx = null;
        $nameIdx = null;
        $phoneIdx = null;
        $combinedIdx = null;
        $industriIdx = null;
        $catatanIdx = null;

        foreach ($header as $i => $cell) {
            $kind = $this->classifyHeader((string) $cell);

            match ($kind) {
                'company' => $companyIdx ??= $i,
                'name' => $nameIdx ??= $i,
                'phone' => $phoneIdx ??= $i,
                'combined' => $combinedIdx ??= $i,
                'industri' => $industriIdx ??= $i,
                'catatan' => $catatanIdx ??= $i,
                default => null,
            };
        }

        if ($companyIdx === null && $nameIdx === null && $phoneIdx === null && $combinedIdx === null) {
            return [];
        }

        // Kolom gabungan "PIC / No. HP" menyediakan nama & nomor sekaligus.
        $nameSource = $nameIdx ?? $combinedIdx;
        $phoneSource = $phoneIdx ?? $combinedIdx;
        $combinedUsed = ($nameSource === $combinedIdx || $phoneSource === $combinedIdx) && $combinedIdx !== null;

        $rows = [];
        foreach (array_slice($rawRows, $headerIndex + 1) as $cells) {
            $cells = array_values($cells);

            $company = $this->cell($cells, $companyIdx);
            $name = '';
            $phones = [];

            if ($combinedUsed) {
                $parsed = $this->parseContactCell($this->cell($cells, $nameSource));

                $name = $parsed['name'];
                $phones = $parsed['phones'];
            } else {
                $name = $this->cell($cells, $nameSource);

                // Kolom telepon boleh berisi beberapa nomor sekaligus.
                $rawPhone = $this->cell($cells, $phoneSource);
                $phones = $this->extractPhones($rawPhone);

                // Nomor tak dikenali polanya tetap dibawa apa adanya.
                if ($phones === [] && $rawPhone !== '') {
                    $phones = [$rawPhone];
                }
            }

            // Fallback: nomor/nama terkubur di dalam kolom perusahaan
            // (mis. "Sunthi Sepuri\nNurdhin +62 812-8266-5004").
            if ($phones === [] && $company !== '') {
                $parsed = $this->parseContactCell($company);

                if ($parsed['phones'] !== []) {
                    $phones = $parsed['phones'];

                    if (trim($name) === '') {
                        $name = $this->nameFromPhoneLine($company, $parsed['phones'][0]) ?: $parsed['name'];
                    }

                    $company = $this->companyCoreFromCell($company, $parsed['phones'][0]);
                }
            }

            $company = $this->cleanName($company);
            $name = $this->cleanName($name);

            if ($this->isJunkRow($company, $name)) {
                continue;
            }

            $catatan = $this->cell($cells, $catatanIdx);

            // Satu orang = satu baris. Tanda "/" (atau baris baru) memisahkan
            // beberapa kontak dalam satu sel; nomor dipasangkan secara posisional.
            foreach ($this->splitContacts($name, $phones) as $sub) {
                if ($company === '' && $sub['nama'] === '' && $sub['no_telepon_mentah'] === '') {
                    continue;
                }

                $rows[] = [
                    'nama_perusahaan' => $company,
                    'industri' => $this->cell($cells, $industriIdx),
                    'nama' => $sub['nama'],
                    'no_telepon_mentah' => $sub['no_telepon_mentah'],
                    'catatan' => $catatan,
                ];
            }
        }

        return $rows;
    }

    /**
     * Pecah satu sel kontak multi-orang menjadi sub-baris.
     *
     * - Nama dipisah oleh "/" atau baris baru; setiap segmen adalah satu orang.
     * - Nomor dipasangkan posisional: orang[0] dengan nomor[0], dst.
     * - Nama yang lebih banyak dari jumlah nomor digabung ke baris terakhir
     *   yang punya nomor (mis. "Mira/Nelly" + 1 nomor -> "Mira / Nelly"),
     *   sehingga tidak ada baris tanpa nomor yang berdiri sendiri.
     * - Nomor yang lebih banyak dari jumlah orang menjadi baris tanpa nama
     *   (di analisis berstatus "dilewati", tidak masuk catatan).
     *
     * @return array<int, array{nama: string, no_telepon_mentah: string}>
     */
    protected function splitContacts(string $name, array $phones): array
    {
        $phones = array_values(array_filter(
            array_map(fn (string $p): string => $this->cleanName($p), $phones),
            fn (string $p): bool => $p !== ''
        ));

        $segments = preg_split('#[/\r\n]+#u', $name ?? '') ?: [];
        $segments = array_values(array_filter(
            array_map(fn (string $s): string => trim($s), $segments),
            fn (string $s): bool => $s !== ''
        ));

        if ($segments === []) {
            $segments = [''];
        }

        $subRows = [];
        foreach ($segments as $i => $segment) {
            if ($i < count($phones)) {
                $subRows[] = [
                    'nama' => $segment,
                    'no_telepon_mentah' => $phones[$i],
                ];
            } elseif ($phones !== [] && $subRows !== []) {
                // Nama lebih banyak dari nomor: gabung ke baris terakhir
                // yang punya nomor, agar satu orang tidak dibiarkan tanpa HP.
                $subRows[count($subRows) - 1]['nama'] .= ' / '.$segment;
            } else {
                $subRows[] = [
                    'nama' => $segment,
                    'no_telepon_mentah' => '',
                ];
            }
        }

        // Nomor tanpa pemilik -> baris pendamping (tanpa nama).
        foreach (array_slice($phones, count($segments)) as $extra) {
            $subRows[] = [
                'nama' => '',
                'no_telepon_mentah' => $extra,
            ];
        }

        return $subRows;
    }

    /**
     * Klasifikasikan sebuah header kolom menjadi salah satu dari:
     * company, name, phone, combined, industri, catatan, no, unknown.
     */
    protected function classifyHeader(string $header): string
    {
        $t = mb_strtolower(trim($this->cleanUtf8($header)));
        if ($t === '') {
            return 'unknown';
        }

        $t = preg_replace('/[\s_\-\.\/\\\|\(\)]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        $tokens = array_values(array_filter(explode(' ', $t)));
        $has = fn (array $words): bool => count(array_intersect($tokens, $words)) > 0;

        if ($has(['industri', 'bidang'])) {
            return 'industri';
        }

        if ($has(['catatan', 'keterangan', 'notes', 'note', 'remark'])) {
            return 'catatan';
        }

        if ($has(['qty', 'check', 'checklist', 'cheklist', 'persiapan', 'preparasi'])) {
            return 'junk';
        }

        if ($t === 'no' || $t === 'nomor') {
            return 'no';
        }

        if ($has(['perusahaan', 'company', 'sponsor', 'mitra', 'umkm', 'instansi'])) {
            return 'company';
        }

        if ($has(['kontak', 'contact']) && $has(['person'])) {
            return 'name'; // "Kontak Person" / "Contact Person"
        }

        if ($has(['nama']) && $has(['kontak'])) {
            return 'name'; // "Nama Kontak"
        }

        if ($has(['nama']) && $has(['pic'])) {
            return 'name'; // "Nama PIC"
        }

        if ($t === 'nama' || $t === 'pic' || $t === 'kontak person' || $t === 'contact person') {
            return 'name';
        }

        if ($has(['pic'])) {
            if ($has(['telepon', 'telp', 'hp', 'handphone', 'phone'])) {
                return 'combined'; // "PIC / No. HP"
            }

            if ($has(['no', 'nomor'])) {
                return 'phone'; // "No Kontak PIC"
            }

            return 'name';
        }

        if ($has(['telepon', 'telp', 'hp', 'handphone', 'phone'])) {
            return 'phone'; // "No. Telepon", "Nomor HP"
        }

        if ($has(['kontak', 'contact'])) {
            return 'phone'; // "Kontak", "No Kontak", "CONTACT"
        }

        return 'unknown';
    }

    /**
     * Temukan baris header terbaik. Baris header harus memuat minimal
     * satu kolom utama (company/name/phone/combined); baris judul yang
     * hanya berisi satu sel tidak dianggap header.
     *
     * @param  array<int, array<int, mixed>>  $rawRows
     */
    protected function findHeaderRow(array $rawRows): ?int
    {
        $max = min(count($rawRows), 40);

        for ($i = 0; $i < $max; $i++) {
            $cells = array_values($rawRows[$i]);
            $score = 0;
            $hasMain = false;
            $nonEmpty = 0;

            foreach ($cells as $cell) {
                if (trim((string) $cell) !== '') {
                    $nonEmpty++;
                }

                $kind = $this->classifyHeader((string) $cell);

                if ($kind === 'junk') {
                    continue 2; // sheet teknis/checklist -> cari header berikutnya
                }

                if (in_array($kind, ['company', 'name', 'phone', 'combined', 'industri', 'catatan'], true)) {
                    $score++;
                }

                if (in_array($kind, ['company', 'name', 'phone', 'combined'], true)) {
                    $hasMain = true;
                }
            }

            if ($hasMain && $score >= 2) {
                return $i;
            }

            // Fallback longgar: satu kolom utama + beberapa sel berisi.
            if ($hasMain && $nonEmpty >= 2) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Pisahkan teks sel menjadi nama dan daftar nomor telepon.
     *
     * @return array{name: string, phones: list<string>}
     */
    protected function parseContactCell(string $text): array
    {
        $phones = $this->extractPhones($text);

        $rest = $text;
        foreach ($phones as $phone) {
            $rest = str_replace($phone, ' ', $rest);
        }

        return [
            'name' => $this->cleanName($rest),
            'phones' => $phones,
        ];
    }

    /**
     * Ekstrak kandidat nomor telepon dari teks bebas (bisa berisi
     * nama/keterangan lain). Pola: diawali 62/0/8, panjang 8-19 digit.
     *
     * @return list<string>
     */
    protected function extractPhones(string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }

        if (preg_match_all('/(?:\+?62|0|8)\s?[\d][\d\s\-\.\(\)]{6,17}[\d]/', $text, $m)) {
            $phones = [];
            foreach ($m[0] as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '' && ! in_array($candidate, $phones, true)) {
                    $phones[] = $candidate;
                }
            }

            return $phones;
        }

        return [];
    }

    /**
     * Ambil nama orang dari baris yang memuat nomor telepon
     * ("Nurdhin +62 812-8266-5004" -> "Nurdhin").
     */
    protected function nameFromPhoneLine(string $text, string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $lineDigits = preg_replace('/\D/', '', $line) ?? '';

            if ($digits !== '' && str_contains($lineDigits, $digits)) {
                $rest = $this->cleanName(str_replace($phone, ' ', $line));

                return $rest !== '' && mb_strlen($rest) <= 60 ? $rest : '';
            }
        }

        return '';
    }

    /**
     * Inti nama perusahaan dari sel yang memuat nomor: potong di baris
     * sebelum baris yang mengandung nomor telepon.
     */
    protected function companyCoreFromCell(string $text, string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        $core = [];
        foreach ($lines as $line) {
            $lineDigits = preg_replace('/\D/', '', $line) ?? '';
            if ($digits !== '' && str_contains($lineDigits, $digits)) {
                break;
            }
            $core[] = $line;
        }

        $company = $this->cleanName(implode(' ', $core));

        return $company !== '' ? $company : $this->cleanName($lines[0] ?? '');
    }

    protected function cleanName(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim((string) $text)) ?? '';

        // Trim berbasis regex (aman UTF-8). trim() dengan charlist multi-byte
        // bersifat byte-wise dan memotong byte e2/80 pada karakter UTF-8 tepi
        // (mis. U+202A, U+2014) sehingga merusak string.
        $chars = '\s\x{2011}\x{2014}\x{202A}\x{202C}/|\\\\\-.紫,;';
        $text = preg_replace('~^['.$chars.']+~u', '', $text) ?? '';
        $text = preg_replace('~['.$chars.']+$~u', '', $text) ?? '';

        return $text;
    }

    /**
     * Baris teknis (mis. "Koordinasi dengan vendor...") bukan data kontak.
     */
    protected function isJunkRow(string $company, string $name): bool
    {
        $text = mb_strtolower($company.' '.$name);

        foreach (self::JUNK_WORDS as $word) {
            if (str_contains($text, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    protected function cell(array $cells, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $cells)) {
            return '';
        }

        $val = $cells[$index];
        if (is_object($val) && method_exists($val, 'format')) {
            return $this->cleanUtf8((string) $val->format('Y-m-d'));
        }

        return $this->cleanUtf8(trim((string) $val));
    }

    public static function normalizeCompanyName(?string $name): string
    {
        $s = mb_strtolower(trim((string) $name));
        $s = preg_replace('/\b(pt|cv|firma|perusahaan|the|inc|corporation|ltd|llc|trd|tbk|sdn|bhd|co)\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/[^a-z0-9]+/u', '', $s) ?? '';

        return $s;
    }

    /**
     * Klasifikasi tiap baris: status perusahaan & keputusan kontak + alasan.
     * Nomor HP dinormalisasi SEBELUM dicocokkan, sehingga '0811-1465-133'
     * dan '62811465133' dianggap nomor yang sama.
     *
     * Aturan kelengkapan: baris valid (dibuat) bila nama perusahaan DAN
     * nomor telepon terisi; nama PIC opsional. Selain itu data_tidak_lengkap.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function classify(array $rows): array
    {
        $perusahaanByNormalized = [];
        foreach (Perusahaan::query()->pluck('id', 'nama_standar') as $nama => $id) {
            $key = self::normalizeCompanyName((string) $nama);
            if ($key === '') {
                continue;
            }
            $perusahaanByNormalized[$key] = ['id' => (int) $id, 'nama' => (string) $nama];
        }

        $kontakByCompanyPhone = [];
        $kontakByCompanyName = [];
        foreach (Kontak::query()->get(['perusahaan_id', 'no_telepon', 'nama']) as $kontak) {
            if (filled($kontak->no_telepon)) {
                $kontakByCompanyPhone[$kontak->perusahaan_id][$kontak->no_telepon] = true;
            }
            if (filled($kontak->nama)) {
                $kontakByCompanyName[$kontak->perusahaan_id][mb_strtolower($kontak->nama)] = true;
            }
        }

        // Company baru dalam 1 file (key -> nama resmi, dipakai ulang antar baris)
        $batchCompanies = [];
        // Nomor sudah "dipakai" baris lain file ini: key(perusahaanAkhir) . '|' . nomor
        $batchPhoneKeys = [];
        $batchNameKeys = [];

        $previews = [];

        foreach ($rows as $rowIndex => $row) {
            $namaPerusahaan = trim((string) ($row['nama_perusahaan'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));
            $raw = trim((string) ($row['no_telepon_mentah'] ?? ''));

            $norm = self::normalizeCompanyName($namaPerusahaan);
            $canonicalCompany = KlasifikasiTabel::perusahaanKanonik($namaPerusahaan);
            $namaEvent = KlasifikasiTabel::eventKanonik($row['nama_event'] ?? null);
            $namaKategori = trim((string) ($row['nama_kategori'] ?? ''));
            $isJunk = $namaPerusahaan !== ''
                && (KlasifikasiTabel::isJunk($namaPerusahaan) || KlasifikasiTabel::isJunk($canonicalCompany));
            $phone = PhoneNormalizer::normalize($raw);
            $phoneValid = PhoneNormalizer::isValid($phone);
            $phoneFinal = $phoneValid || $raw === '' ? $phone : $raw;

            // --- Perusahaan ---
            $perusahaanStatus = 'baru';
            $perusahaanId = null;
            $perusahaanNamaResmi = $canonicalCompany !== '' ? $canonicalCompany : $namaPerusahaan;

            if ($isJunk) {
                $perusahaanStatus = 'junk';
            } elseif ($norm !== '' && isset($perusahaanByNormalized[self::normalizeCompanyName($canonicalCompany)])) {
                $entry = $perusahaanByNormalized[self::normalizeCompanyName($canonicalCompany)];
                $perusahaanStatus = 'cocok';
                $perusahaanId = $entry['id'];
                $perusahaanNamaResmi = $entry['nama'];
            } elseif ($norm !== '' && isset($perusahaanByNormalized[$norm])) {
                $perusahaanStatus = 'cocok';
                $perusahaanId = $perusahaanByNormalized[$norm]['id'];
                $perusahaanNamaResmi = $perusahaanByNormalized[$norm]['nama'];
            } elseif ($norm !== '' && isset($batchCompanies[$norm])) {
                $perusahaanId = $batchCompanies[$norm]['id'];
                $perusahaanNamaResmi = $batchCompanies[$norm]['nama'];
            } elseif ($norm !== '') {
                $batchCompanies[$norm] = ['id' => null, 'nama' => $perusahaanNamaResmi];
            }

            // --- Keputusan kontak ---
            $statusKontak = 'dibuat';
            $alasan = '';
            $companyKey = $perusahaanId ?? 'new:'.$norm;
            $lowName = mb_strtolower($nama);

            if ($isJunk) {
                $statusKontak = 'data_tidak_lengkap';
                $alasan = 'Nama bukan perusahaan (baris teknis/deretan)';
            } elseif ($norm === '' && $phone === '') {
                $statusKontak = 'data_tidak_lengkap';
                $alasan = 'Baris tanpa nama perusahaan dan nomor telepon';
            } elseif ($norm === '') {
                $statusKontak = 'data_tidak_lengkap';
                $alasan = 'Baris tanpa nama perusahaan';
            } elseif ($phone === '') {
                $statusKontak = 'data_tidak_lengkap';
                $alasan = 'Nomor telepon kosong (tidak lengkap)';
            } else {
                // Duplikat nomor: hanya bermakna bila polanya valid & terisi.
                $phoneDup = null;
                if ($phoneValid && $phone !== '') {
                    $phoneKey = $companyKey.'|'.$phone;

                    if ($perusahaanId && isset($kontakByCompanyPhone[$perusahaanId][$phone])) {
                        $phoneDup = 'duplikat_telepon';
                        $alasanTelepon = 'Sudah ada kontak bernomor '.$phone.' pada '.$perusahaanNamaResmi;
                    } elseif (isset($batchPhoneKeys[$phoneKey])) {
                        $phoneDup = 'duplikat_batch';
                        $alasanTelepon = 'Nomor '.$phone.' sudah dipakai baris lain pada file ini';
                    } else {
                        $batchPhoneKeys[$phoneKey] = $rowIndex;
                    }
                }

                // Duplikat nama: dicek selalu terlepas validitas nomor,
                // agar nama yang sama di perusahaan yang sama tetap ketahuan.
                // Baris tanpa PIC (nama kosong) tidak ikut pencocokan/pendaftaran
                // agar beberapa kontak tanpa nama tidak saling tertangkap duplikat.
                $nameDup = null;
                if ($nama !== '') {
                    if ($perusahaanId && isset($kontakByCompanyName[$perusahaanId][$lowName])) {
                        $nameDup = 'duplikat_nama';
                        $alasanNama = 'Kontak '.$nama.' sudah ada pada '.$perusahaanNamaResmi;
                    } elseif (isset($batchNameKeys[$companyKey.'|'.$lowName])) {
                        $nameDup = 'duplikat_batch';
                        $alasanNama = 'Kontak '.$nama.' duplikat pada file ini';
                    } else {
                        $batchNameKeys[$companyKey.'|'.$lowName] = $rowIndex;
                    }
                }

                if ($phoneDup !== null) {
                    $statusKontak = $phoneDup;
                    $alasan = $alasanTelepon;
                } elseif ($nameDup !== null) {
                    $statusKontak = $nameDup;
                    $alasan = $alasanNama;
                } elseif (! $phoneValid) {
                    // Nomor terisi tapi bukan pola HP Indonesia (mis. nomor luar negeri).
                    $alasan = 'Nomor tidak lolos pola 628... (tetap disimpan)';
                }
            }

            $previews[$rowIndex] = [
                'sheet' => $row['sheet'] ?? null,
                'baris' => $rowIndex + 1,
                'nama_perusahaan' => $namaPerusahaan,
                'perusahaan_status' => $perusahaanStatus,
                'perusahaan_id' => $perusahaanId,
                'perusahaan_nama_resmi' => $perusahaanNamaResmi,
                'industri' => trim((string) ($row['industri'] ?? '')),
                'nama' => $nama,
                'no_telepon_mentah' => $raw,
                'no_telepon' => $phoneFinal,
                'no_telepon_valid' => $phoneValid,
                'catatan' => trim((string) ($row['catatan'] ?? '')),
                'nama_event' => $namaEvent,
                'nama_kategori' => $namaKategori,
                'status_kontak' => $statusKontak,
                'alasan' => $alasan,
            ];
        }

        return $previews;
    }

    /**
     * Simpan baris berstatus 'dibuat' dalam satu transaksi.
     * - Perusahaan dibuat dengan NAMA KANONIK (kamus sinonim).
     * - Baris dipetakan ke Kegiatan & Kategori Kegiatan via nama event hasil
     *   deteksi sheet (firstOrCreate, idempoten).
     * - Kontak baru otomatis status_verifikasi = perlu_dicek.
     *
     * @param  array<int, array<string, mixed>>  $previews
     * @return array{perusahaan_dibuat: int, kontak_dibuat: int, dilewati: int}
     */
    public function save(int $authId, array $previews): array
    {
        $dilewati = 0;
        $perusahaanDibuat = 0;
        $kontakDibuat = 0;
        $createdCompanies = [];
        $kegiatanCache = [];
        $kategoriCache = [];

        DB::transaction(function () use ($previews, $authId, &$dilewati, &$perusahaanDibuat, &$kontakDibuat, &$createdCompanies, &$kegiatanCache, &$kategoriCache) {
            foreach ($previews as $preview) {
                if ($preview['status_kontak'] !== 'dibuat') {
                    $dilewati++;

                    continue;
                }

                $canonical = KlasifikasiTabel::perusahaanKanonik($preview['nama_perusahaan']);
                $norm = self::normalizeCompanyName($canonical !== '' ? $canonical : (string) $preview['nama_perusahaan']);

                $perusahaan = null;

                if ($preview['perusahaan_id'] && ($candidate = Perusahaan::find($preview['perusahaan_id']))) {
                    $perusahaan = $candidate;
                } elseif (isset($createdCompanies[$norm])) {
                    $perusahaan = $createdCompanies[$norm];
                } elseif ($preview['perusahaan_status'] === 'baru' && $norm !== '') {
                    $perusahaan = Perusahaan::create([
                        'nama_standar' => $canonical !== '' ? $canonical : (string) $preview['nama_perusahaan'],
                        'industri' => $preview['industri'] ?: null,
                        'catatan' => null,
                        'updated_by' => $authId,
                    ]);
                    $createdCompanies[$norm] = $perusahaan;
                    $perusahaanDibuat++;
                }

                if (! $perusahaan) {
                    $dilewati++;

                    continue;
                }

                // --- Kegiatan & kategori ---
                $kegiatanId = null;
                $kategoriId = null;
                $namaEvent = KlasifikasiTabel::eventKanonik($preview['nama_event'] ?? null);

                if (filled($namaEvent)) {
                    if (! isset($kegiatanCache[$namaEvent])) {
                        $kegiatan = Kegiatan::firstOrCreate(['nama_event' => $namaEvent]);
                        $kegiatanCache[$namaEvent] = $kegiatan;

                        if (filled($preview['nama_kategori'] ?? null)) {
                            $kategoriNama = (string) $preview['nama_kategori'];
                            if (! isset($kategoriCache[$kategoriNama])) {
                                $kategoriCache[$kategoriNama] = KategoriKegiatan::firstOrCreate(['nama_kategori' => $kategoriNama]);
                            }
                            $kegiatan->kategori_kegiatan_id = $kategoriCache[$kategoriNama]->id;
                        }

                        if (filled($preview['venue'] ?? null)) {
                            $kegiatan->venue = (string) $preview['venue'];
                        }

                        if (filled($preview['tanggal_mulai'] ?? null)) {
                            $kegiatan->tanggal_mulai = (string) $preview['tanggal_mulai'];
                        }

                        $kegiatan->save();
                    }

                    $kegiatan = $kegiatanCache[$namaEvent];
                    $kegiatanId = $kegiatan->id;
                    $kategoriId = $kegiatan->kategori_kegiatan_id;
                }

                Kontak::create([
                    'perusahaan_id' => $perusahaan->id,
                    'kegiatan_id' => $kegiatanId,
                    'kategori_kegiatan_id' => $kategoriId,
                    'nama' => $preview['nama'],
                    'no_telepon' => $preview['no_telepon'],
                    'status_verifikasi' => 'perlu_dicek',
                    'status_format_valid' => $preview['no_telepon_valid'],
                    'updated_by' => $authId,
                    'catatan' => $preview['catatan'] ?: null,
                ]);
                $kontakDibuat++;
            }
        });

        return [
            'perusahaan_dibuat' => $perusahaanDibuat,
            'kontak_dibuat' => $kontakDibuat,
            'dilewati' => $dilewati,
        ];
    }

    public static function summaryCounts(array $previews): array
    {
        $counts = [
            'baru' => 0,
            'cocok' => 0,
            'duplikat' => 0,
            'data_tidak_lengkap' => 0,
            'nomor_tidak_valid' => 0,
        ];

        foreach ($previews as $p) {
            if ($p['status_kontak'] === 'dibuat') {
                $counts[$p['perusahaan_status'] === 'cocok' ? 'cocok' : 'baru']++;
                if (! $p['no_telepon_valid'] && $p['no_telepon'] !== '') {
                    $counts['nomor_tidak_valid']++;
                }
            } elseif ($p['status_kontak'] === 'data_tidak_lengkap') {
                $counts['data_tidak_lengkap']++;
            } else {
                $counts['duplikat']++;
            }
        }

        return $counts;
    }
}
