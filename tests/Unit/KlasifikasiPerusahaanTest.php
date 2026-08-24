<?php

namespace Tests\Unit;

use App\Support\KlasifikasiTabel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class KlasifikasiPerusahaanTest extends TestCase
{
    #[Test]
    public function sinonim_mepro_menyatu_ke_satu_kanonik(): void
    {
        $harapan = 'PT Meprofarm Pharmaceutical Industries';

        $this->assertSame($harapan, KlasifikasiTabel::perusahaanKanonik('Mepro'));
        $this->assertSame($harapan, KlasifikasiTabel::perusahaanKanonik('Myepro'));
        $this->assertSame($harapan, KlasifikasiTabel::perusahaanKanonik('PT. Meprofarm'));
        $this->assertSame($harapan, KlasifikasiTabel::perusahaanKanonik('Mepro Fifi'));
        $this->assertSame($harapan, KlasifikasiTabel::perusahaanKanonik('PT Meprofarm Pharmaceutical Industries'));
    }

    #[Test]
    public function nama_dengan_pic_atau_nomor_tetap_terpetakan(): void
    {
        $this->assertSame('Sunthi Sepuri', KlasifikasiTabel::perusahaanKanonik('Sunthi Sepuri Chandra'));
        $this->assertSame('PT Novell Pharmaceutical Laboratories', KlasifikasiTabel::perusahaanKanonik('Novell Galih'));

        $phoneCell = 'Sunthi Sepuri'.PHP_EOL.'Nurdhin +62 812-8266-5004';
        $this->assertSame('Sunthi Sepuri', KlasifikasiTabel::perusahaanKanonik($phoneCell));
    }

    #[Test]
    public function nama_tanpa_sinonim_dipakai_sebagaimana_adanya(): void
    {
        $this->assertSame('Konimex', KlasifikasiTabel::perusahaanKanonik('Konimex'));
        $this->assertSame('Boston Scientific', KlasifikasiTabel::perusahaanKanonik('Boston Scientific'));
    }

    #[Test]
    public function junk_nama_dideteksi_dengan_batas_kata(): void
    {
        $this->assertTrue(KlasifikasiTabel::isJunk('No'));
        $this->assertTrue(KlasifikasiTabel::isJunk('Total Nominal'));
        $this->assertTrue(KlasifikasiTabel::isJunk('Cek Kelengkapan Booth'));
        $this->assertTrue(KlasifikasiTabel::isJunk('Nama Sponsor'));
        $this->assertTrue(KlasifikasiTabel::isJunk('FIX SPONSOR'));

        // Batas kata: "no"/"fix" tidak boleh menimpa nama perusahaan wajar.
        $this->assertFalse(KlasifikasiTabel::isJunk('PT Novo Nordisk Indonesia'));
        $this->assertFalse(KlasifikasiTabel::isJunk('Sona Medika'));
        $this->assertFalse(KlasifikasiTabel::isJunk('Novell'));
        $this->assertFalse(KlasifikasiTabel::isJunk('PT Novell Pharmaceutical Laboratories'));
        $this->assertFalse(KlasifikasiTabel::isJunk('Infion'));
    }

    #[Test]
    public function event_kanonik_menyatukan_varian_deteksi_dengan_taksonomi(): void
    {
        $this->assertSame('Anxiety Master Class by SMC 2026', KlasifikasiTabel::eventKanonik('Anxiety MC 2026'));
        $this->assertSame('PIR PDPI JATENG 2025', KlasifikasiTabel::eventKanonik('PDPI Jateng 2025'));
        $this->assertSame('PIKAB 2026', KlasifikasiTabel::eventKanonik('PIKAB 2026'));
        $this->assertSame('Belum Dikenal', KlasifikasiTabel::eventKanonik('Belum Dikenal'));
    }
}
