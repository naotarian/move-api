<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // スーパー管理者を作成
        Admin::create([
            'name' => 'システム管理者',
            'email' => 'admin@moving-auction.local',
            'password' => Hash::make('admin123'),
            'role' => Admin::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        // 一般管理者を作成
        Admin::create([
            'name' => '一般管理者',
            'email' => 'manager@moving-auction.local',
            'password' => Hash::make('manager123'),
            'role' => Admin::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // 閲覧者を作成
        Admin::create([
            'name' => '閲覧者',
            'email' => 'viewer@moving-auction.local',
            'password' => Hash::make('viewer123'),
            'role' => Admin::ROLE_VIEWER,
            'is_active' => true,
        ]);
    }
}
