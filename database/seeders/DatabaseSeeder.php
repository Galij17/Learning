<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        if (app()->environment(['local', 'testing']) || config('app.demo_mode')) {
            $this->call(LmsDemoSeeder::class);
        }
    }
}
