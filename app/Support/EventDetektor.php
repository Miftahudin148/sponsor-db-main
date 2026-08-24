<?php

namespace App\Support;

/**
 * Deteksi metadata event/kegiatan dari judul sheet + baris-baris pertama file.
 *
 * Sumber data latih menaruh klasifikasi di metadata sheet:
 *   - Judul sheet:  "PIKAB 2026 - Sponsor Fix", "ACE BALI (OBGYN)", ...
 *   - Baris kepala: "Target Peserta: ...", "Tanggal: ...", "Venue: ..."
 *
 * Hasil parse dipakai bersama oleh KontakImportService (impor), perintah
 * app:muat-data-pelatihan, dan MasterDataSeeder.
 */
class EventDetektor
{
    private const BULAN = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
    ];

    /**
     * @param  array<int, mixed>  $cells  sel-sel non-kosong dari baris pertama sheet
     * @param  string|null  $fileName  nama file (sumber cadangan nama event/tahun)
     * @return array{event: string|null, kategori_key: string|null, kategori_nama: string|null, tanggal_mulai: string|null, tanggal_selesai: string|null, venue: string|null}
     */
    public function parse(string $sheetTitle, ?string $fileName = null, array $cells = []): array
    {
        $metaText = $this->metaCellsText($cells);

        $event = $this->eventName($sheetTitle, $fileName, $cells);
        $kategoriKey = $this->kategoriKey($event, $metaText);
        [$mulai, $selesai] = $this->tanggal($metaText, $fileName);
        $venue = $this->venue($metaText);

        return [
            'event' => $event,
            'kategori_key' => $kategoriKey,
            'kategori_nama' => $kategoriKey ? KlasifikasiTabel::kategoriNama($kategoriKey) : null,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'venue' => $venue,
        ];
    }

    protected function eventName(string $sheetTitle, ?string $fileName, array $cells): ?string
    {
        $base = $this->cleanSheetTitle($sheetTitle);
        $baseToken = KlasifikasiTabel::normalizeToken((string) $base);

        $metaName = $this->metaEventName($cells);
        $fileNameYear = $this->ekstrakTahun($fileName ?? '');

        // Judul generik ("FIX SPONSOR", "Sponsor", "Database") -> ambil dari sel
        // yang memuat tahun ("REPORT SPONSOR SUNS BATAM 2025" -> "SUNS BATAM 2025").
        $generic = ['', 'fix', 'sponsor', 'fixsponsor', 'database', 'sheet', 'report', 'sponsorship'];

        // Base pendek (akronim) yang tercakup dalam nama metadata yang lebih kaya:
        // "HOGSI" -> "PIT HOGSI XVII 2026"; "POTI" -> "POTI 2026".
        $metaContainsBase = $metaName !== null
            && $baseToken !== ''
            && str_contains(KlasifikasiTabel::normalizeToken($metaName), $baseToken);

        if ($metaName !== null && (in_array($baseToken, $generic, true) || (strlen($baseToken) <= 6 && $metaContainsBase))) {
            return $metaName;
        }

        if ($baseToken === '') {
            return $metaName ?? null;
        }

        // Base tanpa tahun + ada tahun di metadata/file -> sisipkan tahun.
        $metaYear = $this->ekstrakTahun($this->metaCellsText($cells));
        $tahun = $fileNameYear ?? $metaYear;

        if (! $this->memuatTahun($base) && $tahun !== null) {
            return trim("{$base} {$tahun}");
        }

        return $base;
    }

    /**
     * Bersihkan judul sheet menjadi nama event: buang kurung, akhiran
     * "- Fix", "- Database", "- Sponsor", dan awalan "Sponsor ...".
     */
    protected function cleanSheetTitle(string $title): string
    {
        $s = trim($title);
        $s = preg_replace('/[（(].*?[)）]/u', '', $s) ?? $s;
        $s = preg_replace('/\s*[-\x{2013}]\s*(?:fix|database(?:\s*sponsor)?|sponsor(?:\s*fix)?)\s*$/iu', '', $s) ?? $s;
        $s = preg_replace('/^(?:list\s+)?(?:daftar\s+)?(?:database\s+)?sponsor\s+/iu', '', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s, " \t\n\r\0\x0B-");
    }

    /**
     * Ambil nama event dari sel metadata yang memuat tahun, dengan awalan noise
     * ("LIST SPONSORSHIP", "REPORT SPONSOR", ...) dibuang.
     */
    protected function metaEventName(array $cells): ?string
    {
        $noise = ['list', 'daftar', 'database', 'report', 'fix', 'laporan', 'support', 'sponsorship', 'sponsor', 'rekap', 'data'];

        foreach ($cells as $cell) {
            if (! is_scalar($cell)) {
                continue;
            }

            $text = trim((string) $cell);
            if (! $this->memuatTahun($text) || mb_strlen($text) > 80) {
                continue;
            }

            $tokens = preg_split('/\s+/u', $text) ?: [];
            $tokens = array_values(array_filter($tokens, fn (string $t): bool => $t !== ''));

            while ($tokens !== [] && in_array(KlasifikasiTabel::normalizeToken($tokens[0]), $noise, true)) {
                array_shift($tokens);
            }

            if ($tokens === []) {
                continue;
            }

            return implode(' ', array_slice($tokens, 0, 6));
        }

        return null;
    }

    protected function metaCellsText(array $cells): string
    {
        return implode(' | ', array_map(
            static fn ($c) => is_scalar($c) ? trim((string) $c) : '',
            $cells
        ));
    }

    protected function memuatTahun(string $text): bool
    {
        return preg_match('/\b(?:19|20)\d{2}\b/', $text) === 1;
    }

    protected function ekstrakTahun(string $text): ?string
    {
        if (preg_match('/\b(?:19|20)\d{2}\b/', $text, $m)) {
            return $m[0];
        }

        return null;
    }

    protected function kategoriKey(?string $event, string $metaText): ?string
    {
        // Prioritas: pertanda nama event dulu, lalu baris "Target Peserta".
        if ($event !== null) {
            $byEvent = KlasifikasiTabel::kategoriKeyDariEvent($event);

            if ($byEvent !== null) {
                return $byEvent;
            }
        }

        if (preg_match('/Target\s*Peserta\s*:\s*([^\n|]+)/iu', $metaText, $m)) {
            $byTarget = KlasifikasiTabel::kategoriKeyDariTarget($m[1]);

            if ($byTarget !== null) {
                return $byTarget;
            }
        }

        return KlasifikasiTabel::KATEGORI_FALLBACK;
    }

    protected function venue(string $metaText): ?string
    {
        if (preg_match('/Venue\s*:\s*([^\n|]+)/iu', $metaText, $m)) {
            $venue = trim($m[1]);

            return $venue !== '' ? $venue : null;
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    protected function tanggal(string $metaText, ?string $fileName): array
    {
        // Prioritas: blok "Tanggal: ..."; bila absen, cari pola tanggal
        // di seluruh teks metadata (mis. sel bernilai "17-18 Januari 2026").
        $text = $metaText;

        if (preg_match('/Tanggal\s*:\s*([^\n|]+)/iu', $metaText, $m)) {
            $text = $m[1];
        }

        $fallbackYear = $this->ekstrakTahun($fileName ?? '');
        $dates = $this->tanggalDariTeks($text, $fallbackYear);

        if ($dates === []) {
            return [null, null];
        }

        sort($dates);

        return [reset($dates), end($dates)];
    }

    /**
     * @return array<int, string> daftar tanggal "Y-m-d" yang muncul dalam teks
     */
    protected function tanggalDariTeks(string $text, ?string $fallbackYear): array
    {
        $bulanKata = '(?:'.implode('|', array_keys(self::BULAN)).')';
        $dates = [];

        // Rentang hari dalam bulan yang sama: "17-18 Januari 2026", "17- 18 Januari 2026".
        if (preg_match_all('/\b(\d{1,2})\s*[-\x{2013}]\s*(\d{1,2})\s+('.$bulanKata.')\s+(\d{4})\b/iu', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $dates[] = $this->buatTanggal((int) $hit[1], self::BULAN[mb_strtolower($hit[3])], $hit[4]);
                $dates[] = $this->buatTanggal((int) $hit[2], self::BULAN[mb_strtolower($hit[3])], $hit[4]);
            }
        }

        // Tanggal tunggal: "31 Januari - 1 Februari 2026" -> dua entri terpisah.
        if (preg_match_all('/\b(\d{1,2})\s+('.$bulanKata.')(?:\s+(\d{4}))?\b/iu', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $tahun = $hit[3] ?? $fallbackYear;
                if ($tahun === null) {
                    continue;
                }
                $dates[] = $this->buatTanggal((int) $hit[1], self::BULAN[mb_strtolower($hit[2])], $tahun);
            }
        }

        return array_values(array_unique(array_filter($dates)));
    }

    protected function buatTanggal(int $hari, string $bulan, string $tahun): ?string
    {
        $tanggal = sprintf('%s-%s-%02d', $tahun, $bulan, $hari);
        $check = new \DateTime($tanggal);

        // Hari 31 di bulan 30-hari -> roll ke bulan berikutnya; tolak.
        if ($check->format('j') !== (string) $hari || $check->format('n') !== (string) (int) $bulan) {
            return null;
        }

        return $check->format('Y-m-d');
    }
}
