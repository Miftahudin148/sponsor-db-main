<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Unique;

// app/Filament/Pages/ProfilSaya.php
class ProfilSaya extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.profil-saya';

    protected static ?string $title = 'Profil Saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static string|\UnitEnum|null $navigationGroup = 'Akun Saya';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto Profil')
                    ->description('Klik foto untuk ganti. Atur posisi & bentuk lingkaran secara manual sebelum simpan. Otomatis di-resize ke 400px.')
                    ->icon('heroicon-o-photo')
                    ->columns(1)
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto')
                            ->avatar()
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400')
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->helperText('JPG / PNG / WebP, maks 2 MB. Geser & zoom untuk atur, lalu klik Simpan.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Diri')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Budi Santoso'),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique('users', 'email', modifyRuleUsing: fn (Unique $rule) => $rule->ignore(auth()->user()))
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('0812-3456-7890'),
                    ]),

                Section::make('Ganti Password')
                    ->description('Kosongkan bila tidak ingin ganti password')
                    ->icon('heroicon-o-lock-closed')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->placeholder('••••••••'),
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->confirmed()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->placeholder('Minimal 8 karakter'),
                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->placeholder('Ulangi password baru'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('keManajemen')
                ->label('Kelola Akun (Admin)')
                ->icon('heroicon-o-users')
                ->color('gray')
                ->url(fn () => UserResource::getUrl('index'))
                ->visible(fn () => (bool) auth()->user()?->isAdmin()),
        ];
    }

    public function save(): void
    {
        $user = auth()->user();
        $data = $this->form->getState();

        // Validasi manual untuk current_password bila ganti password
        if (filled($data['password'] ?? null)) {
            $this->validate([
                'data.current_password' => ['required', 'string'],
            ], [], ['data.current_password' => 'Password Saat Ini']);

            if (! Hash::check($data['current_password'], $user->password)) {
                Notification::make()->title('Password saat ini salah')->danger()->send();

                return;
            }
        }

        $payload = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => $data['phone'] ? trim($data['phone']) : null,
            'avatar_url' => $data['avatar_url'] ?? null,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = Hash::make($data['password']);
        }

        $oldAvatar = $user->avatar_url;
        $newAvatar = $payload['avatar_url'] ?? null;

        try {
            DB::transaction(fn () => $user->update($payload));
            // hapus lama hanya setelah DB sukses & file baru ada
            if ($newAvatar && $oldAvatar && $oldAvatar !== $newAvatar) {
                Storage::disk('public')->delete($oldAvatar);
            }
            // jika avatar dihapus (null) dan ada lama, hapus lama
            if ($newAvatar === null && $oldAvatar) {
                Storage::disk('public')->delete($oldAvatar);
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal menyimpan')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Profil diperbarui')->body('Perubahan profil Anda berhasil disimpan.')->success()->send();
        $this->form->fill([
            'name' => $user->fresh()->name,
            'email' => $user->fresh()->email,
            'phone' => $user->fresh()->phone,
            'avatar_url' => $user->fresh()->avatar_url,
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);
    }
}
