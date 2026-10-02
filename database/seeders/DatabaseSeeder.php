<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Access\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // First Super Admin comes from .env so no credentials live in code.
        if ($email = env('ADMIN_EMAIL')) {
            $admin = User::firstOrCreate(
                ['email' => $email],
                ['name' => env('ADMIN_NAME', 'Super Admin'), 'password' => env('ADMIN_PASSWORD')],
            );
            $admin->assignRole(Role::SuperAdmin->value);
        }
    }
}
