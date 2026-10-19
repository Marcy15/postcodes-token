<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'postcodes:create-admin {email? : Admin email címe} {--name=Admin : Megjelenő név}';
    protected $description = 'Tokennel védett API használatához felhasználó létrehozása';

    public function handle(): int
    {
        $email = trim((string) ($this->argument('email') ?: $this->ask('Admin email címe')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Érvénytelen email cím.');
            return self::FAILURE;
        }
        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ezzel az email címmel már van felhasználó.');
            return self::FAILURE;
        }
        $password = (string) $this->secret('Jelszó (legalább 10 karakter)');
        if (mb_strlen($password) < 10) {
            $this->error('Legalább 10 karakteres jelszó szükséges.');
            return self::FAILURE;
        }
        $repeat = (string) $this->secret('Jelszó ismét');
        if (!hash_equals($password, $repeat)) {
            $this->error('A két jelszó nem egyezik.');
            return self::FAILURE;
        }
        User::query()->create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $this->info('Admin felhasználó létrehozva. A jelszó nincs forrásfájlba mentve.');
        return self::SUCCESS;
    }
}
