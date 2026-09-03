<?php

namespace App\Services;

use App\Models\Kontak;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;

class KontakSmartSearch
{
    /**
     * Minimal jumlah digit dalam token agar dianggap pencarian nomor telepon.
     * Nomor di DB disimpan ternormalisasi (628...), jadi 4 digit pun cukup
     * unik (mis. "0811" -> "62811").
     */
    protected const MIN_PHONE_DIGITS = 4;

    /**
     * Terapkan pencarian cerdas ke query kontak.
     *
     * - Query dipecah per kata; SETIAP kata harus cocok di salah satu kolom
     *   (AND antar kata, OR antar kolom) sehingga pencarian multi-kata presisi.
     * - Token ber-digit >= MIN_PHONE_DIGITS ikut dicocokkan ke no_telepon
     *   setelah dinormalisasi (mendukung "0811...", "62811...", "8xx...").
     *
     * @return Builder<Kontak>
     */
    public function applyTo(Builder $query, string $search): Builder
    {
        $tokens = $this->tokens($search);

        if ($tokens === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($tokens): void {
            foreach ($tokens as $token) {
                $useText = ! $this->isPureNumber($token);
                $phoneNeedle = $this->phoneDigitsCount($token) >= self::MIN_PHONE_DIGITS
                    ? $this->phoneNeedle($token)
                    : '';

                $hasText = $useText;
                $hasPhone = $phoneNeedle !== '';

                if (! $hasText && ! $hasPhone) {
                    continue;
                }

                $q->where(function (Builder $inner) use ($token, $hasText, $hasPhone, $phoneNeedle): void {
                    // Gabung text OR phone untuk token yang sama — hindari AND berlebihan yang bikin 0 hasil
                    if ($hasText && $hasPhone) {
                        $needle = $this->textNeedle($token);
                        $inner->where(function (Builder $sub) use ($needle): void {
                            $sub->whereHas('perusahaan', fn (Builder $p): Builder => $p->where('nama_standar', 'like', $needle))
                                ->orWhere('kontaks.nama', 'like', $needle)
                                ->orWhereHas('kegiatan', fn (Builder $k): Builder => $k->where('nama_event', 'like', $needle))
                                ->orWhereHas('kategoriKegiatan', fn (Builder $k): Builder => $k->where('nama_kategori', 'like', $needle));
                        })->orWhere('kontaks.no_telepon', 'like', $phoneNeedle);
                    } elseif ($hasText) {
                        $needle = $this->textNeedle($token);
                        $inner->whereHas('perusahaan', fn (Builder $p): Builder => $p->where('nama_standar', 'like', $needle))
                            ->orWhere('kontaks.nama', 'like', $needle)
                            ->orWhereHas('kegiatan', fn (Builder $k): Builder => $k->where('nama_event', 'like', $needle))
                            ->orWhereHas('kategoriKegiatan', fn (Builder $k): Builder => $k->where('nama_kategori', 'like', $needle));
                    } else {
                        $inner->where('kontaks.no_telepon', 'like', $phoneNeedle);
                    }
                });
            }
        });
    }

    /**
     * Kolom mana saja yang cocok dengan kata kunci pada sebuah rekaman.
     * Dipakai untuk badge "Cocok pada" di tabel.
     *
     * @return array<int, string>
     */
    public function matchColumns(Kontak $record, string $search): array
    {
        $tokens = $this->tokens($search);

        if ($tokens === []) {
            return [];
        }

        $labels = [];

        foreach ($tokens as $token) {
            $low = mb_strtolower($token);

            if ($this->contains((string) $record->perusahaan?->nama_standar, $low)) {
                $labels[] = 'Nama Perusahaan';
            }

            if ($this->contains((string) $record->nama, $low)) {
                $labels[] = 'PIC';
            }

            if (! $this->isPureNumber($token) && $this->contains((string) $record->kegiatan?->nama_event, $low)) {
                $labels[] = 'Kegiatan';
            }

            if (! $this->isPureNumber($token) && $this->contains((string) $record->kategoriKegiatan?->nama_kategori, $low)) {
                $labels[] = 'Kategori';
            }

            if ($this->phoneDigitsCount($token) >= self::MIN_PHONE_DIGITS) {
                $phone = $this->phoneDigits($token);

                if ($phone !== '' && $this->contains((string) $record->no_telepon, $phone)) {
                    $labels[] = 'No. Telepon';
                }
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return array<int, string>
     */
    protected function tokens(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $search) ?: [];

        return array_values(array_filter($parts, fn (string $p): bool => $p !== ''));
    }

    protected function textNeedle(string $token): string
    {
        return '%'.mb_strtolower(trim($token)).'%';
    }

    protected function phoneDigitsCount(string $token): int
    {
        return mb_strlen($this->digits($token));
    }

    protected function digits(string $token): string
    {
        return preg_replace('/\D/u', '', $token) ?? '';
    }

    protected function phoneDigits(string $token): string
    {
        return PhoneNormalizer::normalize($this->digits($token));
    }

    protected function phoneNeedle(string $token): string
    {
        $phone = $this->phoneDigits($token);

        return $phone === '' ? '' : '%'.$phone.'%';
    }

    /**
     * Token murni angka (mis. "0811", "628111465133") tidak perlu dicocokkan
     * ke kolom teks; hanya nomor telepon.
     */
    protected function isPureNumber(string $token): bool
    {
        return $this->digits($token) === $token;
    }

    protected function contains(string $haystack, string $needle): bool
    {
        if ($needle === '' || trim($haystack) === '') {
            return false;
        }

        return mb_strpos(mb_strtolower($haystack), mb_strtolower($needle)) !== false;
    }
}
