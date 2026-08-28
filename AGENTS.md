# AGENTS.md — ICM Sponsor Database

Domain pakai **bahasa Indonesia** (`Kontak`, `perusahaan_id`, `muatData`, `KlasifikasiTabel`) — ikuti untuk model, kolom, method, komentar.

## Stack & Entrypoint

- Laravel 12 + PHP ^8.2 + Filament ^5.7 (Livewire 3) di `/admin` brand "ICM Sponsor" (wajib login) + Tailwind v4 (Vite) + phpspreadsheet ^5.9 + SQLite. Sumber kebenaran: `composer.json`/`package.json`/`vite.config.js`/`phpunit.xml` — `README.md` boilerplate, abaikan.
- Entrypoint: `app/Support/KlasifikasiTabel.php` (kamus kanonik), `app/Services/KontakImportService.php` (impor), `app/Livewire/SanitizeUtf8State.php` (hook global), `app/Filament/Resources/*/{Schemas,Tables,Pages}` (Filament v5 split), `routes/web.php:11` export CSV.
- Bukan monorepo. 4 resource saja: Kontaks, Perusahaans, Kegiatans, KategoriKegiatans (tidak ada resource Users).

## Perintah

```bash
composer setup          # install + copy .env + key:generate + migrate --force + npm install + build
composer dev            # concurrently serve + queue:listen --tries=1 + pail + vite (butuh node_modules)
composer test           # config:clear + php artisan test
vendor/bin/pint         # formatter — wajib sebelum commit
npm run build           # vite build; dev: npm run dev
```

```bash
php artisan app:muat-data-pelatihan [--dir=path]  # default --dir=../data training (DI LUAR repo!)
php artisan kontak:normalisasi-nomor                # re-normalisasi semua no_telepon
php artisan db:seed --class=MasterDataSeeder        # taksonomi awal
```

Single-test / fokus (agent sering salah):
```bash
php artisan test --filter=PhoneNormalizerTest
vendor/bin/phpunit tests/Unit/PhoneNormalizerTest.php --filter=test_is_valid
php artisan test tests/Feature/KontakImportTest.php
```

## Konvensi Wajib

1. **Telepon** — hanya via `App\Support\PhoneNormalizer::normalize()`/`isValid()` (`^628\d{7,10}$` di `app/Support/PhoneNormalizer.php:40`). Mutator `Kontak::setNoTeleponAttribute` (`app/Models/Kontak.php:34`) simpan VALID sebagai `628…`, INVALID simpan apa adanya (spasi digabung) + `status_format_valid=false`. Jangan normalisasi manual, jangan set flag manual, selalu cek flag.
2. **Kamus kanonik** — `App\Support\KlasifikasiTabel` satu-satunya sumber KATEGORI/PETAKAN_EVENT/SINONIM_PERUSAHAAN/JUNK. Dipakai EventDetektor, Seeder, KontakImportService, muat-data-pelatihan. Tambah data = edit konstanta di sana.
3. **UTF-8** — data lama Windows-1252. Bersihkan via `cleanUtf8()` (`app/Services/KontakImportService.php:561`, `app/Livewire/SanitizeUtf8State.php:83`), jangan `trim()` charlist byte-wise (merusak UTF-8 tepi).
4. **SanitizeUtf8State** — daftar di `AppServiceProvider::register()` (`app/Providers/AppServiceProvider.php:29`) BUKAN `boot()` (hook harus sebelum Livewire boot). Dual tugas: sanitasi dehydrate + tahan `__lazyLoad` duplikat (`call()` di `app/Livewire/SanitizeUtf8State.php:60`, race Livewire 4.4.x → 500 tanpa guard, lihat `LazyWidgetDoubleLoadTest`).
5. **Filament v5** — tiap resource split `Schemas/`/`Tables/`/`Pages/`. Ikuti pola existing.
6. **Policy** — `UserPolicy` admin-only; policy Kontak/Perusahaan/Kegiatan/KategoriKegiatan allow semua user panel (`admin`|`karyawan` setara). Export `GET /admin/kontak/export` cek `viewAny` manual.
7. **Impor idempoten** — `firstOrCreate` + `KontakImportService::normalizeCompanyName()` (hapus PT/CV/dll, `app/Services/KontakImportService.php:597`).
8. `league/csv` dipakai langsung tapi hanya transitif via `filament/actions` — jangan tambah dep tanpa kebutuhan.

## TALL / Livewire Perf (pragmatis)

- Eloquent: selalu `with()` hindari N+1. Jangan query per-baris di `render()`/closure kolom — batch via `PetaNomorPerusahaan::ambil()` + `ListKontaks::petaNomorDipakai()` (`app/Support/PetaNomorPerusahaan.php:20`, `app/Filament/Resources/Kontaks/Pages/ListKontaks.php:43`).
- Binding: `wire:model.blur` atau `wire:model.live.debounce.300ms`, jangan `wire:model` polos (spam request).
- Form >3 field → `Livewire\Form` Form Object; validasi `#[Validate]` di properti, otorisasi `#[Authorize]`; PHP 8.3 Attributes (`#[Fillable]`/`#[Url]`) bila ada.
- Navigasi `wire:navigate` untuk SPA; interaksi lokal (modal/dropdown) pakai Alpine tanpa round-trip.
- Tailwind v4: `sm:/md:/lg:` + `dark:`, class rapi, tanpa inline style/`@apply` kecuali komponen berulang. Tulis lokasi file di atas tiap blok kode `// path/to/file.php`.

## Kolom & Enum Kritis

- `kontaks.status_verifikasi` enum DB (`2026_08_18_232941`): `terverifikasi` (default form) | `perlu_dicek` (impor) | `tidak_aktif` — tambah nilai = migrasi.
- `kontaks.status_format_valid` bool auto mutator; `updated_by` FK users (audit).
- `kegiatans.tanggal_mulai` nullable (`2026_08_20_000003`), ada `warna` di kegiatans+kategori (`2026_08_24_000000`).

## Alur Import

`Filament\Pages\ImportKontaks::analyze()` → preview → `saveImport()` → `KontakImportService`:
1. `extractRows()` — xlsx/xls/ods/csv/tsv, semua sheet, header fuzzy (alias ID/EN, baris mana pun), metadata event via `EventDetektor`.
2. `classify()` — `baru|cocok|junk` (perusahaan), `dibuat|duplikat_*|data_tidak_lengkap` (kontak) + alasan; nomor dinormalisasi dulu baru cek duplikat (intra-batch juga).
3. `save()` — 1 transaksi; perusahaan pakai NAMA KANONIK; kegiatan/kategori `firstOrCreate`; kontak `perlu_dicek`. Junk via `JUNK_WORDS` (`app/Services/KontakImportService.php:34`) + `JUNK_COMPANY`.

## Pengujian

- `composer test` = `phpunit.xml:26` `sqlite :memory:` + `CACHE_STORE=array` + `SESSION_DRIVER=array`. Suite ~40 detik.
- Unit cepat: `PhoneNormalizerTest`, `EventDetektorTest`, `KlasifikasiPerusahaanTest`. Feature: `KontakImportTest`, CRUD, role, seeder, command, widget, smart search, lazy-guard.
- Ubah `app/Support/*` atau `KontakImportService` wajib update test (edge: format nomor, sheet teknis, encoding).

## Lingkungan & Gotcha

- Default `DB_CONNECTION=sqlite` (`config/database.php:20`), pragma `DB_BUSY_TIMEOUT=5000`/`DB_JOURNAL_MODE=WAL`/`DB_SYNCHRONOUS=NORMAL` (`config/database.php:43`). `.env.example` masih `mysql` — jangan ikuti.
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` (testing override ke array/sync).
- Aset Filament ter-publish `public/js/filament` & `public/css/filament`; `vite.config.js:8` input `resources/css/app.css` + `resources/css/filament/admin/theme.css` + `resources/js/app.js`.
- Deploy: `php artisan config:cache route:cache view:cache`, `composer install --no-dev --optimize-autoloader`, `APP_DEBUG=false`, OPcache.
- CI/pre-commit tidak ada (cek `composer.json` scripts + `.github/` kosong) — verifikasi manual via `composer test` + `vendor/bin/pint`.
