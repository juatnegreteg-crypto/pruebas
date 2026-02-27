<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            [
                'email' => 'test@example.com',
            ],
            [
                'name' => 'Super Admin',
                'username' => 'super-admin',
                'full_name' => 'Super Admin',
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );

        $superAdminProfile = Profile::query()
            ->where('slug', 'super-admin')
            ->where('guard_name', 'web')
            ->first();

        if ($superAdminProfile) {
            $user->syncSingleProfile($superAdminProfile);
        }
    }
}
