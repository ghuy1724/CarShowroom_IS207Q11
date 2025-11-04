<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ✅ Tạo tài khoản Admin (nếu chưa có), hoặc cập nhật nếu đã tồn tại
        DB::table('users')->updateOrInsert(
            ['email' => 'locminh.2809@gmail.com'], // Điều kiện kiểm tra trùng
            [
                'name' => 'Admin',
                'password' => Hash::make('password'), // Mật khẩu: password
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Nếu sau này bạn có thêm Seeder riêng cho dữ liệu khác, gọi thêm tại đây
        $this->call([
            CarDetailsSeeder::class,
            AccessoriesSeeder::class,
            RentalCar::class,
            SalesCarsSeeder::class,
        ]);
    }
}
