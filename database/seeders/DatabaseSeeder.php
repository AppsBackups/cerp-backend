<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::create([
            'role_name' => 'admin'
        ]);
        Role::create([
            'role_name' => 'user'
        ]);

        User::create([
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Ahmed123'),
            'role_id' => '1',
        ]);
    }
}
