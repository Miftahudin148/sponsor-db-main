<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use App\Models\Divisi;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;
use Spatie\Permission\Models\Role;

// app/Filament/Auth/CustomRegister.php
class CustomRegister extends Register
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Daftar Akun Karyawan';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Isi data diri - akun akan aktif setelah disetujui Admin.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->placeholder('Budi Santoso'),
                TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->maxLength(50)
                    ->unique($this->getUserModel())
                    ->placeholder('198001012005011001'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique($this->getUserModel()),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel()
                    ->maxLength(20)
                    ->placeholder('0812-3456-7890'),
                Select::make('divisi_id')
                    ->label('Divisi')
                    ->options(fn () => Divisi::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('Pilih divisi')
                    ->required()
                    ->exists('divisis', 'id'),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        $data['is_active'] = false;
        $data['role'] = 'karyawan';

        return $data;
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $user = $this->getUserModel()::create($data);
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
        $user->assignRole('karyawan');

        return $user;
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isRegisterRateLimited($this->data['email'] ?? '')) {
            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            $data = $this->mutateFormDataBeforeRegister($data);
            $this->callHook('beforeRegister');
            $user = $this->handleRegistration($data);
            $this->form->model($user)->saveRelationships();
            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        // Pending approval — jangan auto-login, tampilkan notif
        Notification::make()
            ->title('Pendaftaran berhasil')
            ->body('Akun Anda menunggu persetujuan Admin sebelum bisa login.')
            ->success()
            ->send();

        $this->redirect(filament()->getLoginUrl());

        return null;
    }
}
