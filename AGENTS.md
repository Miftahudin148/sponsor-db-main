# AGENTS.md — ICM Sponsor Database

Panduan untuk AI agent & developer yang bekerja di codebase ini.

## 1. Ringkasan Sistem

Sistem informasi manajemen **database kontak sponsor** (sponsor CRM ringkas) untuk kegiatan/event medis. Fitur utama:

- **Admin panel** (Filament v5) di path `/admin` dengan brand "ICM Sponsor".
- **Master data**: Perusahaan, Kegiatan (event), Kategori Kegiatan.
- **Kontak**: PIC perusahaan per kegiatan, dengan status verifikasi & validitas format nomor HP.
- **Import Excel/CSV** multi-sheet cerdas (`Import Data` page): deteksi header otomatis, pemisahan nama/nomor dalam satu sel, klasifikasi duplikat/baru/junk, pratinjau sebelum simpan.
- **Ekspor CSV** kontak: `/admin/kontak/export`.
- **Dashboard widget**: statistik kontak, chart status verifikasi, pipeline.
- **Perintah artisan**: muat data latih dari folder "data training", normalisasi nomor kontak massal.

## 2. Teknologi

| Komponen | Versi/Teknologi |
|---|---|
| PHP | ^8.2 |
| Framework | Laravel 12 |
| Admin panel | Filament 5.7 (panel `admin`) |
| Spreadsheet | phpoffice/phpspreadsheet + league/csv |
| Frontend | Vite 7 + Tailwind CSS 4 (tema Filament custom) |
| Database | SQLite (default `.env`), mendukung MySQL |
| Test | PHPUnit 11 (`phpunit.xml`, env `testing`) |
| Code style | Laravel Pint |

## 3. Perintah Penting

```bash
composer setup          # install + key generate + migrate + npm install/build
composer dev            # jalankan server + queue + pail + vite (concurrently)
composer test           # config:clear lalu php artisan test
vendor/bin/pint         # formatter/code style (jalankan sebelum commit)
php artisan migrate     # migrasi database
npm run build           # build aset frontend
```

Perintah artisan khusus proyek:

```bash
php artisan app:muat-data-pelatihan [--dir=path]   # impor semua .xlsx folder data latih
php artisan app:normalisasi-kontak-nomor           # normalisasi ulang nomor telepon tersimpan
```

## 4. Struktur Direktori

```
app/
├── Console/Commands/        # MuatDataPelatihan, NormalizeKontakNomor
├── Filament/
│   ├── Pages/               # ImportKontaks (wizard impor 3 langkah)
│   ├── Resources/           # Kontaks, Perusahaans, Kegiatans, KategoriKegiatans (+Users)
│   │   └── {Nama}/          # pola Filament v5: Schemas/, Tables/, Pages/
│   └── Widgets/             # KontakStatsOverview, StatusVerifikasiChart, KontakPipeline
├── Http/Controllers/        # KontakExportController (invokable, streaming CSV)
├── Livewire/                # SanitizeUtf8State
├── Models/                  # User, Perusahaan, Kontak, Kegiatan, KategoriKegiatan
├── Policies/                # otorisasi per resource
├── Providers/Filament/      # AdminPanelProvider
├── Services/                # KontakImportService (inti logika impor)
└── Support/                 # PhoneNormalizer, EventDetektor, KlasifikasiTabel,
                             # KontakSmartSearch (helper murni/static)
database/
├── migrations/              # skema: users, perusahaans, kontaks, kegiatans, kategori_kegiatans
└── seeders/                 # MasterDataSeeder (taksonomi dari KlasifikasiTabel)
resources/css/filament/admin/theme.css   # tema panel
routes/web.php               # hanya welcome + export route
tests/{Unit,Feature}/        # PHPUnit
```

## 5. Model Data & Relasi

- **User** — kolom `role` string: `admin` | `karyawan`. Method `isAdmin()`, `isKaryawan()`.
- **KategoriKegiatan** `hasMany` **Kegiatan** `hasMany` **Kontak**
- **Perusahaan** `hasMany` **Kontak**
- **Kontak** `belongsTo` → Perusahaan, Kegiatan, KategoriKegiatan, User (`updated_by`)
- Kolom penting Kontak:
  - `no_telepon` — selalu ternormalisasi via mutator `setNoTeleponAttribute` (lihat §6).
  - `status_format_valid` (bool) — apakah nomor lolos pola valid.
  - `status_verifikasi` — enum-ish string, nilai termasuk `perlu_dicek`, `belum_direspon`, `sudah_dikirim`, `sudah_dicoba`, `belum_dicoba`.
- `updated_by` diisi id user pada create/update (audit ringan).

## 6. Konvensi Wajib (jangan dilanggar)

1. **Bahasa Indonesia** untuk nama domain (model, kolom, method UI) dan komentar kode. Contoh: `Kontak`, `perusahaan_id`, `status_format_valid`, `muatData`.
2. **Nomor telepon Indonesia**: simpan polos tanpa tanda baca sebagai `628xxxxxxxxxx`.
   - Normalisasi HANYA lewat `App\Support\PhoneNormalizer::normalize()` dan validasi via `PhoneNormalizer::isValid()` (pola `^628\d{7,10}$`).
   - Model `Kontak` memakai **mutator** — set penulisan `'no_telepon' => $raw` otomatis dinormalisasi; jangan menormalkan manual sebelum assign ganda.
3. **Kamus kanonik terpusat** di `App\Support\KlasifikasiTabel`: kategori event, petakan event→kategori, sinonim nama perusahaan, daftar junk words. Penambahan master data baru = edit konstanta di sana, bukan hard-code di service/seeder.
4. **UTF-8 aman**: data file lama sering Windows-1252. Selalu bersihkan string dari eksternal via pattern `cleanUtf8()` (lihat `KontakImportService`) agar Livewire/JSON tidak gagal serialisasi. Jangan pakai `trim()` charlist multi-byte byte-wise — gunakan regex `\u` seperti di `cleanName()`.
5. **Filament v5 structure**: resource dipisah per folder (`Schemas/`, `Tables/`, `Pages/`). Ikuti pola resource yang sudah ada saat membuat resource baru.
6. **Otorisasi via Policy**: `UserPolicy` admin-only; resource lain (Kontak, Perusahaan, Kegiatan, KategoriKegiatan) terbuka untuk semua user panel (karyawan setara admin). Route export cek `viewAny` policy secara manual.
7. **Impor idempoten**: gunakan `firstOrCreate`/pemetaan normalisasi (`normalizeCompanyName` menghapus PT/CV/dll) agar impor berulang tidak menduplikasi data.

## 7. Alur Import (konteks penting)

Wizard `ImportKontaks` (Livewire page) → `KontakImportService`:

1. **extractRows()** — baca xlsx/xls/ods/csv/tsv; SEMUA sheet dipindai; header dideteksi fuzzy di baris manapun (alias ID/EN: "PIC", "No. HP", "Contact Person", dst); metadata event dari judul sheet + baris kepala via `EventDetektor` (event, kategori, tanggal, venue).
2. **classify()** — status per baris: perusahaan `baru|cocok|junk`; kontak `dibuat|duplikat_telepon|duplikat_nama|duplikat_batch|data_tidak_lengkap` + alasan; nomor dinormalisasi dulu sebelum cek duplikat; dedup juga intra-batch.
3. **save()** — satu transaksi DB; perusahaan dibuat dengan NAMA KANONIK; kegiatan/kategori via `firstOrCreate`; kontak baru berstatus `perlu_dicek`.

Baris teknis/junk (checklist vendor, doorprize, dsb.) difilter via `JUNK_WORDS`.

## 8. Pengujian

- Jalankan: `composer test` (atau `php artisan test`).
- **Unit**: `PhoneNormalizerTest`, `EventDetektorTest`, `KlasifikasiPerusahaanTest` — helper murni, cepat.
- **Feature**: impor kontak (`KontakImportTest`), CRUD resource, role access (`RoleAccessTest`), seeder, command, widget dashboard, smart search.
- Saat mengubah logika di `app/Support/*` atau `KontakImportService`, tambahkan/perbarui test terkait — area ini padat edge case (format nomor aneh, sheet teknis, encoding).

## 9. Catatan Lingkungan

- Default DB: **SQLite** (`DB_CONNECTION=sqlite`); sesi, cache, queue memakai driver `database`.
- Panel admin butuh login (`->login()` di `AdminPanelProvider`).
- Seed taksonomi awal: `php artisan db:seed --class=MasterDataSeeder`; data riil via `app:muat-data-pelatihan`.
- Aset publik Filament sudah ter-publish di `public/js/filament` & `public/css/filament`.

## 10. Gaya Kode

- Ikuti PSR-12 + preset Laravel; format dengan `vendor/bin/pint`.
- Dokumen-dokumen (docblock) ditulis bahasa Indonesia, sertakan `@param`/`@return` array-shape bila relevan.
- Hindari menambah dependensi baru tanpa kebutuhan nyata; stack sudah mencukupi (spreadsheet, csv, filament).
