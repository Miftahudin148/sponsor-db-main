<?php

namespace App\Support;

/**
 * Tabel klasifikasi kanonik untuk data latih sponsor (per 2024–2026).
 *
 * Jika ada penambahan/penyesuaian kategori, event, atau sinonim perusahaan,
 * cukup ubah konstanta di bawah ini — dipakai bersama oleh EventDetektor,
 * MasterDataSeeder, KontakImportService, dan perintah app:muat-data-pelatihan.
 */
class KlasifikasiTabel
{
    /**
     * Kategori kanonik yang diturunkan dari baris "Target Peserta: ..."
     * pada seluruh file data latih.
     *
     * @var array<string, string> key => nama_kategori
     */
    public const KATEGORI = [
        'umum_estetik' => 'Dokter Umum & Estetik',
        'anak' => 'Dokter Spesialis Anak',
        'obgyn' => 'Dokter Spesialis Obstetri & Ginekologi (OBGYN)',
        'gizi_klinik' => 'Dokter Spesialis Gizi Klinik',
        'paru' => 'Dokter Spesialis Paru (Pulmonologi)',
        'andrologi' => 'Dokter Spesialis Andrologi',
        'kejiwaan' => 'Dokter Spesialis Kejiwaan & Psikolog',
        'neurologi' => 'Dokter Spesialis Neurologi',
        'orthopedi' => 'Dokter Spesialis Orthopedi & Traumatologi',
        'multi' => 'Dokter Umum / Multi-spesialis',
    ];

    /**
     * Kategori fallback bila event/target peserta tidak dikenal.
     */
    public const KATEGORI_FALLBACK = 'multi';

    /**
     * Palet warna kategori (hex) untuk pewarnaan badge & sortir menurut
     * warna di tabel kontak. Kegiatan tanpa warna sendiri mewarisi warna
     * kategorinya.
     *
     * @var array<string, string>
     */
    public const WARNA_KATEGORI = [
        'umum_estetik' => '#EC4899',
        'anak' => '#F59E0B',
        'obgyn' => '#8B5CF6',
        'gizi_klinik' => '#10B981',
        'paru' => '#0EA5E9',
        'andrologi' => '#6366F1',
        'kejiwaan' => '#EF4444',
        'neurologi' => '#14B8A6',
        'orthopedi' => '#F97316',
        'multi' => '#64748B',
    ];

    /**
     * Warna kategori berdasarkan nama kategorinya (kebalikan KATEGORI).
     */
    public static function warnaKategori(string $namaKategori): ?string
    {
        $key = array_search($namaKategori, self::KATEGORI, true);

        return $key === false ? null : (self::WARNA_KATEGORI[$key] ?? null);
    }

    /**
     * Petakan pertanda nama event (sudah dinormalisasi) -> key kategori.
     * Dicocokkan sebagai SUBSTRING dari nama event ternormalisasi.
     *
     * @var array<string, string>
     */
    public const PETAKAN_EVENT = [
        'pirpdp' => 'paru',
        'pdpijateng' => 'paru',
        'pdp' => 'paru',
        'perdosni' => 'neurologi',
        'anxiety' => 'kejiwaan',
        'pinkab' => 'anak',
        'hogsi' => 'obgyn',
        'acebali' => 'obgyn',
        'indaac' => 'umum_estetik',
        'intaac' => 'umum_estetik',
        'mamcn' => 'gizi_klinik',
        'amo2024' => 'gizi_klinik',
        'sunsbatam' => 'gizi_klinik',
        'sunbatam' => 'gizi_klinik',
        'konasperdoktin' => 'multi',
        'persandi' => 'andrologi',
        'poti' => 'orthopedi',
    ];

    /**
     * Kata kunci di dalam baris "Target Peserta: ..." -> key kategori.
     * Lebih spesifik lebih dulu, agar "gizi klinik" menang atas "umum".
     *
     * @var array<string, string>
     */
    public const PETAKAN_TARGET_PESERTA = [
        'estetik' => 'umum_estetik',
        'estetika' => 'umum_estetik',
        'gizi klinis' => 'gizi_klinik',
        'gizi' => 'gizi_klinik',
        'obgyn' => 'obgyn',
        'ginekologi' => 'obgyn',
        'kandungan' => 'obgyn',
        'anak' => 'anak',
        'paru' => 'paru',
        'pulmonologi' => 'paru',
        'andrologi' => 'andrologi',
        'kejiwaan' => 'kejiwaan',
        'psikolog' => 'kejiwaan',
        'psikiater' => 'kejiwaan',
        'neurologi' => 'neurologi',
        'syaraf' => 'neurologi',
        'saraf' => 'neurologi',
        'orthopedi' => 'orthopedi',
        'ortopedi' => 'orthopedi',
    ];

    /**
     * Sinonim nama perusahaan (key = hasil normalisasi tanpa PT/CV/dll,
     * tanpa spasi/tanda baca) -> nama_standar kanonik.
     *
     * Pencocokan memakai prefiks terpanjang, sehingga "kalbefima" tetap
     * kena "kalbe" bila memang diinginkan, dan "meprofifififi" -> "mepro".
     * Isi yang bermakna beda (Kalbe Nutri, Kalbe Regenic) sengaja TIDAK
     * digabung agar unit/brand terpisah tidak tercampur.
     *
     * @var array<string, string>
     */
    public const SINONIM_PERUSAHAAN = [
        // Abbott
        'ptabbottproductsindonesia' => 'PT Abbott Indonesia',
        'ptabbottindonesia' => 'PT Abbott Indonesia',
        'ptabbott' => 'PT Abbott Indonesia',
        'abboth' => 'PT Abbott Indonesia',
        'abbott' => 'PT Abbott Indonesia',
        'abbot' => 'PT Abbott Indonesia',
        // AstraZeneca
        'ptastrazenecaindonesiarespi' => 'PT AstraZeneca Indonesia',
        'ptastrazenecaindonesiaonko' => 'PT AstraZeneca Indonesia',
        'ptastrazenecaindonesia' => 'PT AstraZeneca Indonesia',
        'ptastrazeneca' => 'PT AstraZeneca Indonesia',
        'aztrazeneca' => 'PT AstraZeneca Indonesia',
        'astrazeneca' => 'PT AstraZeneca Indonesia',
        // Bayer & B. Braun & BTL & Bio Farma & Boehringer
        'bayer' => 'Bayer',
        'ptbbraunmedicalindonesia' => 'PT B. Braun Medical Indonesia',
        'bbraun' => 'PT B. Braun Medical Indonesia',
        'ptboldtechnologiesleadingindonesia' => 'PT BTL Indonesia',
        'ptbtldan' => 'PT BTL Indonesia',
        'ptbtl' => 'PT BTL Indonesia',
        'btl' => 'PT BTL Indonesia',
        'ptbbraun' => 'PT B. Braun Medical Indonesia',
        'biofarma' => 'PT Bio Farma',
        'boehringer' => 'Boehringer Ingelheim',
        // Combiphar, Dexa, Eisai, Esco, Ferron, Fresenius, GSK, Guardian, Herca
        'ptcombiphar' => 'PT Combiphar',
        'combiphar' => 'PT Combiphar',
        'ptdexamedica' => 'PT Dexa Medica',
        'dexamedica' => 'PT Dexa Medica',
        'dexa' => 'PT Dexa Medica',
        'pteisaiindonesa' => 'PT Eisai Indonesia',
        'pteisaiindonesia' => 'PT Eisai Indonesia',
        'pteisai' => 'PT Eisai Indonesia',
        'eisaiindonesia' => 'PT Eisai Indonesia',
        'eisai' => 'PT Eisai Indonesia',
        'escolabdipa' => 'ESCO Medical',
        'escolab' => 'ESCO Medical',
        'esco' => 'ESCO Medical',
        'ptferronparpharmaceuticals' => 'PT Ferron Par Pharmaceuticals',
        'ferron' => 'PT Ferron Par Pharmaceuticals',
        'ptfreseniuskabiindonesia' => 'PT Fresenius Kabi Indonesia',
        'ptfreseniuskabi' => 'PT Fresenius Kabi Indonesia',
        'freseniuskabikabiven' => 'PT Fresenius Kabi Indonesia',
        'fresseniuskabi' => 'PT Fresenius Kabi Indonesia',
        'freseniuskabi' => 'PT Fresenius Kabi Indonesia',
        'ptglaxowellcomeindonesia' => 'PT GSK Indonesia',
        'ptgskfam' => 'PT GSK Indonesia',
        'ptgsk' => 'PT GSK Indonesia',
        'gsk' => 'PT GSK Indonesia',
        'guardianfarmatama' => 'PT Guardian Pharmatama',
        'ptguardianpharmatama' => 'PT Guardian Pharmatama',
        'guardian' => 'PT Guardian Pharmatama',
        'pthercaciptadermalperdana' => 'PT Herca Cipta Dermal Indah',
        'herca' => 'PT Herca Cipta Dermal Indah',
        // Kalbe (khusus varian nama entitas, bukan brand/produk)
        'ptkalbefarma' => 'Kalbe Farma',
        'kalbefarma' => 'Kalbe Farma',
        'kalbe' => 'Kalbe Farma',
        // Konimex & Landson (Pertiwi Agung)
        'konimex' => 'Konimex',
        'landsonpertiwiagung' => 'PT Pertiwi Agung (Landson)',
        'ptpertiwiagunglandson' => 'PT Pertiwi Agung (Landson)',
        'landsonina' => 'PT Pertiwi Agung (Landson)',
        'landsonsinclair' => 'PT Pertiwi Agung (Landson)',
        'landson' => 'PT Pertiwi Agung (Landson)',
        'ptpertiwiagung' => 'PT Pertiwi Agung',
        // Lundbeck, Merck, Meprofarm, Mersifarma, Metiska
        'lundbeckexportas' => 'Lundbeck (Denmark)',
        'lundbeck' => 'Lundbeck Indonesia',
        'ptmercktbk' => 'PT Merck Tbk',
        'mercktbk' => 'PT Merck Tbk',
        'merck' => 'PT Merck Tbk',
        'ptmeprofarmpharmaceuticalindustries' => 'PT Meprofarm Pharmaceutical Industries',
        'ptmeprofarmfarm' => 'PT Meprofarm Pharmaceutical Industries',
        'ptmeprofarm' => 'PT Meprofarm Pharmaceutical Industries',
        'meprofarm' => 'PT Meprofarm Pharmaceutical Industries',
        'myepro' => 'PT Meprofarm Pharmaceutical Industries',
        'mepro' => 'PT Meprofarm Pharmaceutical Industries',
        'ptmersifarmatirtakumercusana' => 'PT Mersifarma Tirmaku Mercusana',
        'ptmersifarma' => 'PT Mersifarma Tirmaku Mercusana',
        'mersifarma' => 'PT Mersifarma Tirmaku Mercusana',
        'ptmetiskafarma' => 'PT Metiska Farma',
        'metiskafarma' => 'PT Metiska Farma',
        'metiska' => 'PT Metiska Farma',
        // Nestle & Novell (termasuk holding Axcelor)
        'ptnestleindonesiabooth' => 'PT Nestlé Indonesia',
        'ptnestleindonesiadinner' => 'PT Nestlé Indonesia',
        'ptnestleindonesiaindustrialsymposium' => 'PT Nestlé Indonesia',
        'ptnestleindonesiatambahanruangan' => 'PT Nestlé Indonesia',
        'ptnestleindonesia' => 'PT Nestlé Indonesia',
        'nestle' => 'PT Nestlé Indonesia',
        'ptnovellpharmaceuticallabsdivisititan' => 'PT Novell Pharmaceutical Laboratories',
        'ptnovellpharmaceuticallaboratoriesvenusmars' => 'PT Novell Pharmaceutical Laboratories',
        'ptnovellpharmaceuticallaboratories' => 'PT Novell Pharmaceutical Laboratories',
        'ptnovellpharmaceuticalslabs' => 'PT Novell Pharmaceutical Laboratories',
        'ptnovellpharmaceuticals' => 'PT Novell Pharmaceutical Laboratories',
        'ptnovell' => 'PT Novell Pharmaceutical Laboratories',
        'novell' => 'PT Novell Pharmaceutical Laboratories',
        'ptaczelorultimamanagement' => 'PT Novell Pharmaceutical Laboratories',
        'ptaxelorultimamanagement' => 'PT Novell Pharmaceutical Laboratories',
        'novel' => 'PT Novell Pharmaceutical Laboratories',
        // Otsuka, Pfizer, Phapros, Pharos, Prodia
        'ptotsukaindonesiarahmat' => 'PT Otsuka Indonesia',
        'ptotsukaindonesia' => 'PT Otsuka Indonesia',
        'otsukaindonesiadesendynabilabelumadanosricky' => 'PT Otsuka Indonesia',
        'otsukaindonesiarara' => 'PT Otsuka Indonesia',
        'otsukaindonesiarahmat' => 'PT Otsuka Indonesia',
        'otsukaindonesia' => 'PT Otsuka Indonesia',
        'ptotsuka' => 'PT Otsuka Indonesia',
        'otsuka' => 'PT Otsuka Indonesia',
        'ptpfizerindonesia' => 'PT Pfizer Indonesia',
        'pfizer' => 'PT Pfizer Indonesia',
        'ptphaprostbk' => 'PT Phapros Tbk',
        'ptphapros' => 'PT Phapros Tbk',
        'phapros' => 'PT Phapros Tbk',
        'ptpharosindonesia' => 'PT Pharos Indonesia',
        'pharos' => 'PT Pharos Indonesia',
        'ptprodiawidyahusadatbk' => 'PT Prodia Widyahusada Tbk',
        'ptprodiawidhahusada' => 'PT Prodia Widyahusada Tbk',
        'prodiamanado' => 'PT Prodia Widyahusada Tbk',
        'prodiapng' => 'PT Prodia Widyahusada Tbk',
        'prodiadiagnosticline' => 'PT Prodia Widyahusada Tbk',
        'laboratoriumprodia' => 'PT Prodia Widyahusada Tbk',
        'prodia' => 'PT Prodia Widyahusada Tbk',
        // Sanbe, Sanofi, Sasa, Siemens, Soho, Sometech
        'sanbeethical' => 'PT Sanbe Farma',
        'sanbeprobiostim' => 'PT Sanbe Farma',
        'ptsanbefarma' => 'PT Sanbe Farma',
        'sanbefarma' => 'PT Sanbe Farma',
        'sanbe' => 'PT Sanbe Farma',
        'sanofiaventispharma' => 'PT Sanofi Indonesia',
        'sanofi' => 'PT Sanofi Indonesia',
        'ptsasainti' => 'PT Sasa Inti',
        'sasa' => 'PT Sasa Inti',
        'ptsiemenshealthineersindonesia' => 'PT Siemens Healthineers Indonesia',
        'siemenshealthineers' => 'PT Siemens Healthineers Indonesia',
        'ptsohoindustripharmasi' => 'PT Soho Industri Pharmasi',
        'sohoindustripharmasi' => 'PT Soho Industri Pharmasi',
        'soho' => 'PT Soho Industri Pharmasi',
        'stiptsometechindonesia' => 'PT Sometech Indonesia',
        'ptsometechindonesia' => 'PT Sometech Indonesia',
        'sometechindonesia' => 'PT Sometech Indonesia',
        'sometech' => 'PT Sometech Indonesia',
        // Sunthi Sepuri, Takeda, Tanabe, Wellesta, Zuellig
        'sunthisepurichandra' => 'Sunthi Sepuri',
        'sunthisepuri' => 'Sunthi Sepuri',
        'sunthi' => 'Sunthi Sepuri',
        'pttakedaindonesiabandungdengueforum' => 'PT Takeda Indonesia',
        'pttakedaindonesiaindustrialsymposium' => 'PT Takeda Indonesia',
        'pttakedaindonesia' => 'PT Takeda Indonesia',
        'pttakeda' => 'PT Takeda Indonesia',
        'takeda' => 'PT Takeda Indonesia',
        'ptmitsubishitanabepharmaindonesia' => 'PT Mitsubishi Tanabe Pharma Indonesia',
        'mitsubishitanabe' => 'PT Mitsubishi Tanabe Pharma Indonesia',
        'tanabe' => 'PT Mitsubishi Tanabe Pharma Indonesia',
        'ptwellestacpihealthcare' => 'PT Wellesta CPI',
        'ptwellestacpi' => 'PT Wellesta CPI',
        'wellesta' => 'PT Wellesta CPI',
        'ptzuelligpharmamounjaro' => 'PT Zuellig Pharma Indonesia',
        'ptzuelligpharmapharmalink' => 'PT Zuellig Pharma Indonesia',
        'zuelligpharmatherapeutics' => 'PT Zuellig Pharma Indonesia',
        'zuelligpharma' => 'PT Zuellig Pharma Indonesia',
        'zuellighxenical' => 'PT Zuellig Pharma Indonesia',
        'zuellighpharma' => 'PT Zuellig Pharma Indonesia',
        'ptzuelligpharmaindonesia' => 'PT Zuellig Pharma Indonesia',
        'ptzuelligpharma' => 'PT Zuellig Pharma Indonesia',
        'zuellig' => 'PT Zuellig Pharma Indonesia',
        'zuelligh' => 'PT Zuellig Pharma Indonesia',
        // Imedco, Radiant, IDS Med
        'ptimedcodjaya' => 'PT Imedco Djaja',
        'ptimedcodjaja' => 'PT Imedco Djaja',
        'imedcodjaja' => 'PT Imedco Djaja',
        'imedco' => 'PT Imedco Djaja',
        'ptradiantcentralnutritiondvitashopindo' => 'PT Radiant Sentral Nutrindo',
        'ptradiantcentralnutritiond' => 'PT Radiant Sentral Nutrindo',
        'radiantcentralnutritiond' => 'PT Radiant Sentral Nutrindo',
        'ptradiantelokdistriversa' => 'PT Radian Elok Distriversa',
        'radiantelokdistriversa' => 'PT Radian Elok Distriversa',
        'ptidsmedicalsystemsindonesia' => 'PT IDS Med',
        'idsmed' => 'PT IDS Med',
    ];

    /**
     * Frasa junk yang membuat sebuah nama dianggap bukan perusahaan.
     * Dibandingkan terhadap nama (perusahaan + nama PIC) yang sudah
     * di-lowercase; JUNK_WORDS pada KontakImportService tetap dipakai untuk
     * baris teknis, daftar ini untuk menolak nama yang mirip butir/deretan.
     *
     * @var array<int, string>
     */
    public const JUNK_COMPANY = [
        'no', 'fix', 'total', 'company', 'perusahaan', 'nama sponsor', 'nama perusahaan',
        'nama company', 'nama pic', 'list doorprize', 'list booth', 'list umkm',
        'floor plan', 'fix sponsor', 'sponsor final', 'sponsorship', 'daftar sponsor',
        'cek kelengkapan', 'cek nama', 'memberikan nametag', 'pita pembukaan',
        'arrange acara', 'jumlah booth', 'jumlah meja', 'pastikan kelistrikan',
        'serah terima', 'urutansupport', 'prioritas sponsor', 'potential sponsor',
        'no respon', 'join ', 'negosiasi', 'total nominal', 'tidak berpartisipasi',
        'sheet2', 'sheet3', 'cheklist', 'checklist',
    ];

    /**
     * Daftar event kanonik beserta metadata (kategori, tanggal, venue).
     * Dipakai MasterDataSeeder sebagai taksonomi; perintah muat-data
     * menambah/memperbarui event lain yang terdeteksi dari nama sheet.
     *
     * @var array<string, array{key: string, mulai?: string, selesai?: string, venue?: string}>
     */
    public const KEGIATAN = [
        'ACE BALI 2024' => ['key' => 'obgyn'],
        'INDAAC JABAR 2024' => ['key' => 'umum_estetik'],
        'INDAAC SUMBAR 2024' => ['key' => 'umum_estetik'],
        'INTAAC 8.0 2024' => ['key' => 'umum_estetik'],
        'INDAAC JABAR 2026' => ['key' => 'umum_estetik', 'mulai' => '2026-01-31', 'selesai' => '2026-02-01', 'venue' => 'Harris Hotel Festival Citylink Bandung'],
        'IntAAC 10 2026' => ['key' => 'umum_estetik', 'mulai' => '2026-06-05', 'selesai' => '2026-06-07', 'venue' => 'Shangri-La Jakarta'],
        'PIKAB 2026' => ['key' => 'anak', 'mulai' => '2026-04-24', 'selesai' => '2026-04-26', 'venue' => 'Holiday Inn Pasteur Bandung'],
        'KONAS PERDOKTIN 2024' => ['key' => 'multi'],
        'MAMCN 2024' => ['key' => 'gizi_klinik'],
        'AMO 2024' => ['key' => 'gizi_klinik'],
        'PIT PERSANDI 2024' => ['key' => 'andrologi'],
        'PIT & KONAS PERSANDI 2025' => ['key' => 'andrologi'],
        'PIR PDPI JATENG 2025' => ['key' => 'paru'],
        'PIT HOGSI XVII 2026' => ['key' => 'obgyn'],
        'POTI 2026' => ['key' => 'orthopedi', 'mulai' => '2026-01-17', 'selesai' => '2026-01-18'],
        '8th PINKAN 2026' => ['key' => 'multi'],
        'Anxiety Master Class by SMC 2026' => ['key' => 'kejiwaan', 'mulai' => '2026-05-23', 'selesai' => '2026-05-24', 'venue' => 'Movenpick Hotel Jakarta City Centre'],
        'SUNS BATAM 2025' => ['key' => 'gizi_klinik'],
        'PIB PERFITRI' => ['key' => 'multi'],
        'SUNs Batam' => ['key' => 'gizi_klinik'],
        '2nd PURE' => ['key' => 'multi'],
        'KONAS P2B2 PABI 2025' => ['key' => 'multi'],
        'BUM 7' => ['key' => 'multi'],
        '1st BUS' => ['key' => 'multi'],
        'PIN PERDOSNI' => ['key' => 'neurologi'],
    ];

    /**
     * Nama event hasil deteksi yang berbeda token-nya dari nama kanonik
     * di KEGIATAN (typo akronim, dst). Key = token ternormalisasi.
     *
     * @var array<string, string>
     */
    public const ALIAS_EVENT = [
        'anxietymc2026' => 'Anxiety Master Class by SMC 2026',
        'pdpijateng2025' => 'PIR PDPI JATENG 2025',
        'pirpdpijateng2025' => 'PIR PDPI JATENG 2025',
    ];

    public static function kategoriNama(string $key): string
    {
        return self::KATEGORI[$key] ?? self::KATEGORI[self::KATEGORI_FALLBACK];
    }

    /**
     * Nama event kanonik: cek alias lalu KEGIATAN (by token), agar hasil
     * deteksi ("Anxiety MC 2026", "PDPI Jateng 2025") menyatu dengan taksonomi
     * master ("Anxiety Master Class by SMC 2026", "PIR PDPI JATENG 2025").
     */
    public static function eventKanonik(?string $nama): ?string
    {
        if ($nama === null) {
            return null;
        }

        $token = self::normalizeToken($nama);

        if (isset(self::ALIAS_EVENT[$token])) {
            return self::ALIAS_EVENT[$token];
        }

        foreach (self::KEGIATAN as $kanon => $meta) {
            if (self::normalizeToken($kanon) === $token) {
                return $kanon;
            }
        }

        return $nama;
    }

    public static function namaKategoriGlobal(string $kalimat): ?string
    {
        foreach (self::KATEGORI as $key => $nama) {
            if (mb_stripos($kalimat, (string) $nama) !== false) {
                return (string) $nama;
            }
        }

        return null;
    }

    /**
     * Normalisasi nama untuk pencocokan: huruf kecil, huruf/angka saja.
     */
    public static function normalizeToken(string $value): string
    {
        $s = mb_strtolower(trim($value));

        return preg_replace('/[^a-z0-9]+/u', '', $s) ?? '';
    }

    /**
     * Cari key kategori via kata kunci pada teks target peserta.
     */
    public static function kategoriKeyDariTarget(string $target): ?string
    {
        $norm = mb_strtolower($target);

        foreach (self::PETAKAN_TARGET_PESERTA as $word => $key) {
            if (str_contains($norm, $word)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Cari key kategori via pertanda nama event ternormalisasi.
     * Pola terpanjang yang cocok (sebagai substring) yang menang.
     */
    public static function kategoriKeyDariEvent(string $event): ?string
    {
        $norm = self::normalizeToken($event);

        if ($norm === '') {
            return null;
        }

        $terbaik = null;
        $panjangTerbaik = 0;

        foreach (self::PETAKAN_EVENT as $pattern => $key) {
            if (str_contains($norm, $pattern) && strlen($pattern) > $panjangTerbaik) {
                $panjangTerbaik = strlen($pattern);
                $terbaik = $key;
            }
        }

        return $terbaik;
    }

    /**
     * Nama perusahaan kanonik dari string mentah (lewat kamus sinonim).
     * Memakai pencocokan PREFIX terpanjang terhadap SINONIM_PERUSAHAAN,
     * sehingga nama yang masih mengandung nama PIC ("Mepro Fifi") ikut terpetakan.
     */
    public static function perusahaanKanonik(?string $raw): string
    {
        $text = trim((string) $raw);
        if ($text === '') {
            return '';
        }

        $norm = self::normalizeToken($text);
        if ($norm === '') {
            return '';
        }

        $terbaik = '';
        $panjangTerbaik = 0;

        foreach (self::SINONIM_PERUSAHAAN as $variant => $canonical) {
            if ($variant !== '' && str_starts_with($norm, $variant) && strlen($variant) > $panjangTerbaik) {
                $panjangTerbaik = strlen($variant);
                $terbaik = $canonical;
            }
        }

        if ($panjangTerbaik > 0) {
            return $terbaik;
        }

        // Bukan sinonim: bersihkan serpihan nomor/PIC yang masih menempel,
        // lalu pakai nama asli (tampilan) sebagai fallback.
        $core = preg_replace('/\d[\d\s\-\.\+()]{6,}\d/u', ' ', $text) ?? $text;
        $core = preg_replace('/\s{2,}/u', ' ', $core) ?? $core;

        return trim($core);
    }

    /**
     * Apakah nama ini dianggap junk (bukan perusahaan) setelah dilowercase?
     * Kata pendek (<=3 huruf) dicocokkan dengan batas kata; frasa panjang
     * cukup lewat substring, agar "no" tidak menimpa "Novell", "Otto", dll.
     */
    public static function isJunk(string $text): bool
    {
        $norm = self::normalizeToken($text);

        if ($norm === '') {
            return false;
        }

        foreach (self::JUNK_COMPANY as $phrase) {
            $token = self::normalizeToken($phrase);

            if ($token === '') {
                continue;
            }

            if (mb_strlen($token) <= 3) {
                if (preg_match('/(?<![a-z0-9])'.preg_quote($token, '/').'(?![a-z0-9])/', $norm) === 1) {
                    return true;
                }

                continue;
            }

            if (str_contains($norm, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kunci penggabung kontak untuk dedupe antar-baris saat muat data.
     */
    public static function kunciKontak(string $perusahaan, string $event, string $nama, string $noTelepon): string
    {
        return implode('|', [self::normalizeToken($perusahaan), self::normalizeToken($event), self::normalizeToken($nama), PhoneNormalizer::normalize($noTelepon)]);
    }
}
