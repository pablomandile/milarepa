<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el usuario administrador inicial de UNA instalación.
 *
 * Antes creaba dos usuarios con mails reales y contraseñas `12345678` / `editor`
 * escritas en el código: cualquier instalación nueva nacía con un admin conocido y
 * con la cuenta de otra persona. Ahora los datos vienen del `.env` de cada centro
 * (o se piden por consola), y si no hay contraseña el seeder no crea nada en vez de
 * inventar una débil.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');
        $nombre = env('SEED_ADMIN_NAME', 'admin');

        // Modo interactivo: `php artisan db:seed` desde la consola de una instalación nueva.
        if (blank($email) && $this->command?->getOutput()->isInteractive()) {
            $email = $this->command->ask('Email del administrador inicial');
            $password = $this->command->secret('Contraseña del administrador inicial');
            $nombre = $this->command->ask('Nombre del administrador inicial', 'admin');
        }

        if (blank($email) || blank($password)) {
            $this->command?->warn(
                'UserSeeder: sin SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD en el .env no se crea ningún usuario. '
                .'(Ojo: con la config cacheada env() devuelve null; corré `config:clear` antes de seedear.)'
            );

            return;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'password' => Hash::make($password)]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command?->info("UserSeeder: administrador {$email} listo.");
    }
}
