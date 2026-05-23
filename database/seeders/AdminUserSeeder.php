<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create(
            [
                'name'=>'Admin',
                'email'=>'Admin@gmail.com',
                'phone'=>'0986605554',
                'password'=>Hash::make('12345678'),
                'type_user'=>'admin'
            ]
        );
    }
}
