# AGENTS.md — ICM Sponsor Database

Panduan untuk AI agent & developer di codebase ini. Semua nama domain memakai **bahasa Indonesia** (`Kontak`, `perusahaan_id`, `muatData`) — ikuti itu juga untuk komentar/docblock.

## Ringkasan

CRM ringkas kontak sponsor event medis: panel Filament v5 di `/admin` (brand "ICM Sponsor", wajib login), master data Perusahaan/Kegiatan/KategoriKegiatan, Kontak PIC per kegiatan, impor Excel/CSV multi-sheet, ekspor CSV `/admin/kontak/export`. Stack: Laravel 12 + PHP ^8.2 + Filament ^5.7 + phpspreadsheet + SQLite (versi detail lihat `composer.json`/`package.json`).

## Perintah

```bash
composer setup          # install + key generate + migrate + npm install/build
composer dev            # server + queue + pail + vite (concurrently)
composer test           # config:clear lalu php artisan test
vendor/bin/pint         # formatter — jalankan sebelum commit
npm run build           # build aset frontend
```

Perintah artisan khusus proyek:

```bash
php artisan app:muat-data-pelatihan [--dir=path]   # default --dir = ../data training (DI LUAR root repo!)
php artisan kontak:normalisasi-nomor               # normalisasi ulang semua no_telepon tersimpan
```

Seed taksonomi awal: `php artisan db:seed --class=MasterDataSeeder`.

## Konvensi Wajib

1. **Nomor telepon** — normalisasi HANYA via `App\Support\PhoneNormalizer::normalize()` / `isValid()` (pola `^628\d{7,10}$`). Model `Kontak` punya mutator `setNoTeleponAttribute`: nomor VALID disimpan polos `628xxxxxxxxxx`, tapi nomor INVALID disimpan apa adanya (spasi digabung) dengan `status_format_valid=false`. Jadi jangan berasumsi semua nilai `no_telepon` cocok pola `628…` — cek flag-nya. Jangan menormalkan manual sebelum assign.
2. **Kamus kanonik terpusat** di `App\Support\KlasifikasiTabel` (kategori event, petakan event→kategori, sinonim perusahaan, junk words). Dipakai bersama oleh EventDetektor, MasterDataSeeder, KontakImportService, dan command muat-data-pelatihan. Tambah master data = edit konstanta di sana, bukan hard-code.
3. **UTF-8 aman**: data file lama sering Windows-1252. Bersihkan string eksternal via pattern `cleanUtf8()` (ada di `KontakImportService` dan hook `SanitizeUtf8State`), bukan `trim()` charlist byte-wise.
4. **Hook global `SanitizeUtf8State`** (registrasi di `AppServiceProvider`) punya DUA tugas: sanitasi UTF-8 properti Livewire saat dehydrate, DAN menahan panggilan `__lazyLoad` duplikat widget lazy (race Livewire v4.4.x → 500 MethodNotFoundException tanpa guard ini, lihat `LazyWidgetDoubleLoadTest`). Jangan hapus bagian `call()`.
5. **Filament v5 structure**: tiap resource dipisah per folder `Schemas/`, `Tables/`, `Pages/` — ikuti pola resource existing. Resource yang ada hanya 4: Kontaks, Perusahaans, Kegiatans, KategoriKegiatans (tidak ada resource Users).
6. **Otorisasi via Policy**: `UserPolicy` admin-only; policy lain (Kontak, Perusahaan, Kegiatan, KategoriKegiatan) membuka semua aksi untuk semua user panel (`role` `admin`|`karyawan` setara). Route export cek policy `viewAny` secara manual.
7. **Impor idempoten**: gunakan `firstOrCreate` + `KontakImportService::normalizeCompanyName()` (menghapus PT/CV/dll) agar impor berulang tidak menduplikasi data.
8. Hindari dependensi baru tanpa kebutuhan nyata. Catatan: `league/csv` dipakai langsung oleh `KontakImportService` tapi hanya dependensi transitif (via filament/actions).

## Kolom Penting & Enum

- `kontaks.status_verifikasi` adalah **enum level DB**: `terverifikasi` (default form manual) | `perlu_dicek` (dipakai impor) | `tidak_aktif`. Menambah nilai = migrasi baru, bukan sekadar opsi UI.
- `kontaks.status_format_valid` (bool) — dihitung otomatis oleh mutator, jangan di-set manual.
- `updated_by` diisi id user pada create/update (audit ringan).

## Alur Import

Wizard `ImportKontaks` (Livewire page: `analyze()` → pratinjau → `saveImport()`) → `KontakImportService`:

1. `extractRows()` — baca xlsx/xls/ods/csv/tsv; SEMUA sheet dipindai; header dideteksi fuzzy di baris manapun (alias ID/EN); metadata event dari judul sheet/baris kepala via `EventDetektor`.
2. `classify()` — status per baris: perusahaan `baru|cocok|junk`; kontak `dibuat|duplikat_*|data_tidak_lengkap` + alasan; nomor dinormalisasi dulu sebelum cek duplikat; dedup juga intra-batch.
3. `save()` — satu transaksi DB; perusahaan dibuat dengan NAMA KANONIK; kegiatan/kategori via `firstOrCreate`; kontak baru berstatus `perlu_dicek`.

Baris teknis/junk (checklist vendor, doorprize, dsb.) difilter via `JUNK_WORDS`.

## Pengujian

- Jalankan `composer test` (phpunit.xml: sqlite `:memory:`, cache/session array). Suite lengkap ~40 detik.
- Unit: `PhoneNormalizerTest`, `EventDetektorTest`, `KlasifikasiPerusahaanTest` (helper murni, cepat).
- Feature: impor (`KontakImportTest`), CRUD resource, role access, seeder, command, widget dashboard, smart search, lazy-load guard.
- Mengubah `app/Support/*` atau `KontakImportService` WAJIB disertai update test — area ini padat edge case (format nomor aneh, sheet teknis, encoding).

## Lingkungan

- Default DB SQLite (`DB_CONNECTION=sqlite`); sesi/cache/queue driver `database`. Pragma WAL/synchronous/busy_timeout diatur via `DB_*` env (lihat `config/database.php`).
- Aset publik Filament sudah ter-publish di `public/js/filament` & `public/css/filament`.
- **Deploy produksi**: `php artisan config:cache route:cache view:cache`, `composer install --no-dev --optimize-autoloader`, `APP_DEBUG=false`, aktifkan OPcache.
- **Jaga jumlah query tabel Kontak**: pewarnaan baris & sorotan nomor ganda sengaja dibatch (lihat `PetaNomorPerusahaan`, `petaNomorDipakai()` di ListKontaks, ringkasan agregat di `summaryCards`). Jangan kembali memanggil query per-baris di dalam closure kolom/recordClasses.
