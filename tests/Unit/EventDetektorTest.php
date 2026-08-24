<?php

namespace Tests\Unit;

use App\Support\EventDetektor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventDetektorTest extends TestCase
{
    private EventDetektor $detektor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detektor = new EventDetektor;
    }

    #[Test]
    public function pikab_dengan_target_dan_tanggal(): void
    {
        $r = $this->detektor->parse('PIKAB 2026 - Sponsor Fix', null, [
            'PIKAB 2026',
            'Target Peserta: Dokter Spesialis Anak',
            'Tanggal: 24-26 April 2026',
            'Venue: Holiday Inn Pasteur Bandung',
        ]);

        $this->assertSame('PIKAB 2026', $r['event']);
        $this->assertSame('anak', $r['kategori_key']);
        $this->assertSame('2026-04-24', $r['tanggal_mulai']);
        $this->assertSame('2026-04-26', $r['tanggal_selesai']);
        $this->assertSame('Holiday Inn Pasteur Bandung', $r['venue']);
    }

    #[Test]
    public function ace_bali_dari_judul_sheet_dan_tahun_dari_nama_file(): void
    {
        $r = $this->detektor->parse('ACE BALI (OBGYN)', 'DATABASE SPONSOR 2024 - 2026.xlsx', [
            'ACE BALI 2024',
            'Target Peserta: Dokter Spesialis Obgyn',
        ]);

        $this->assertSame('ACE BALI 2024', $r['event']);
        $this->assertSame('obgyn', $r['kategori_key']);
    }

    #[Test]
    public function hogsi_dipakai_judul_metadata_yang_lebih_kaya(): void
    {
        $r = $this->detektor->parse('Sponsor HOGSI', null, [
            'SPONSORSHIP',
            'PIT HOGSI XVII 2026',
            'FIX',
        ]);

        $this->assertSame('PIT HOGSI XVII 2026', $r['event']);
        $this->assertSame('obgyn', $r['kategori_key']);
    }

    #[Test]
    public function poti_dengan_rentang_tanggal_bulan_terpisah(): void
    {
        $r = $this->detektor->parse('Sponsor POTI', null, [
            'LIST SPONSORSHIP POTI 2026',
            '17- 18 Januari 2026',
        ]);

        $this->assertSame('POTI 2026', $r['event']);
        $this->assertSame('2026-01-17', $r['tanggal_mulai']);
        $this->assertSame('2026-01-18', $r['tanggal_selesai']);
        $this->assertSame('orthopedi', $r['kategori_key']);
    }

    #[Test]
    public function mamcn_tidak_diambil_dari_judul_tiruan_konas(): void
    {
        $r = $this->detektor->parse('MAMCN (DOKTER SPESIALIS GIZI KLINIS)', 'DATABASE SPONSOR 2024 - 2026.xlsx', [
            'KONAS PERDOKTIN 2024',
            'Target Peserta: Dokter Umum, Dokter spesialis Gizi Klinis',
        ]);

        $this->assertSame('MAMCN 2024', $r['event']);
        $this->assertSame('gizi_klinik', $r['kategori_key']);
    }

    #[Test]
    public function suns_batam_dari_nama_file_saat_sheet_generik(): void
    {
        $r = $this->detektor->parse('FIX SPONSOR', 'REPORT SPONSOR SUNS BATAM 2025.xlsx', [
            'REPORT SPONSOR SUNS BATAM 2025',
        ]);

        $this->assertSame('SUNS BATAM 2025', $r['event']);
        $this->assertSame('gizi_klinik', $r['kategori_key']);
    }

    #[Test]
    public function indaac_jabar_2026_dengan_rentang_cross_bulan(): void
    {
        $r = $this->detektor->parse('IndAAC Jabar 2026 - Fix', 'Database Sponsor Event Rangga 2026-07-10.xlsx', [
            'INDAAC JABAR 2026',
            'Target Peserta: Dokter Umum dan Estetika (Berbagai Spesialis)',
            'Tanggal: 31 Januari - 1 Februari 2026',
            'Venue: Harris Hotel Festival Citylink Bandung',
        ]);

        $this->assertSame('IndAAC Jabar 2026', $r['event']);
        $this->assertSame('umum_estetik', $r['kategori_key']);
        $this->assertSame('2026-01-31', $r['tanggal_mulai']);
        $this->assertSame('2026-02-01', $r['tanggal_selesai']);
    }

    #[Test]
    public function anxiety_kejiwaan_dari_target_peserta(): void
    {
        $r = $this->detektor->parse('Anxiety MC 2026 - Sponsor Fix', null, [
            'Anxiety Master Class by SMC 2026',
            'Target Peserta: Dokter Spesialis Kejiwaan, Psikolog, Dokter Umum',
            'Tanggal: 23-24 Mei 2026',
            'Venue: Movenpick Hotel Jakarta City Centre',
        ]);

        $this->assertSame('Anxiety MC 2026', $r['event']);
        $this->assertSame('kejiwaan', $r['kategori_key']);
        $this->assertSame('2026-05-23', $r['tanggal_mulai']);
        $this->assertSame('2026-05-24', $r['tanggal_selesai']);
    }
}
