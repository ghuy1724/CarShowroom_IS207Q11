<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\RentalPayment;
use App\Models\Order;
use App\Models\RentalOrder;
use Carbon\Carbon;

class DashboardChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Tạo dữ liệu mẫu cho biểu đồ dashboard - đầy đủ 12 tháng năm 2025
     */
    public function run(): void
    {
        $this->command->info('Đang tạo dữ liệu thanh toán cho 12 tháng năm 2025...');
        
        // Kiểm tra và tạo Order nếu cần
        $firstOrder = Order::first();
        if (!$firstOrder) {
            $this->command->warn('Không tìm thấy Order, đang tạo Order mẫu...');
            // Lấy account_id đầu tiên hoặc tạo mặc định
            $accountId = \App\Models\Account::first()->id ?? 1;
            $salesCarId = \App\Models\SalesCar::first()->sales_car_id ?? 1;
            
            $firstOrder = Order::create([
                'account_id' => $accountId,
                'sales_car_id' => $salesCarId,
                'status_order' => 1,
                'order_date' => now(),
            ]);
            $this->command->info('✓ Đã tạo Order mẫu');
        }

        // Kiểm tra và tạo RentalOrder nếu cần
        $firstRentalOrder = RentalOrder::first();
        if (!$firstRentalOrder) {
            $this->command->warn('Không tìm thấy RentalOrder, đang tạo RentalOrder mẫu...');
            $userId = \App\Models\Account::first()->id ?? 1;
            $rentalCarId = \App\Models\RentalCars::first()->rental_id ?? 1;
            
            $firstRentalOrder = RentalOrder::create([
                'user_id' => $userId,
                'rental_id' => $rentalCarId,
                'status' => 'Paid',
                'order_date' => now(),
            ]);
            $this->command->info('✓ Đã tạo RentalOrder mẫu');
        }

        // Tạo dữ liệu cho mỗi tháng
        for ($month = 1; $month <= 12; $month++) {
            // Số lượng payment ngẫu nhiên mỗi tháng (4-8 payments để có nhiều data hơn)
            $paymentsCount = rand(4, 8);
            
            $successPayment = 0;
            $successRental = 0;
            
            for ($i = 0; $i < $paymentsCount; $i++) {
                // Ngày ngẫu nhiên trong tháng
                $day = rand(1, min(28, Carbon::create(2025, $month)->daysInMonth));
                $paymentDate = Carbon::create(2025, $month, $day);

                try {
                    // 1. Tạo Payment (xe bán & phụ kiện) - Cân đối 50-50
                    Payment::create([
                        'order_id' => $firstOrder->order_id,
                        'status_deposit' => 1, // 1 = Successful
                        'status_payment_all' => 1, // 1 = Successful
                        'deposit_amount' => rand(10000000, 30000000), // 10-30 triệu
                        'total_amount' => rand(50000000, 150000000), // 50-150 triệu
                        'remaining_amount' => 0,
                        'deposit_deadline' => $paymentDate->copy()->subDays(7),
                        'payment_deadline' => $paymentDate,
                        'payment_deposit_date' => $paymentDate->copy()->subDays(7),
                    ]);
                    $successPayment++;
                } catch (\Exception $e) {
                    $this->command->error("Lỗi tạo Payment tháng {$month}: " . $e->getMessage());
                }

                try {
                    // 2. Tạo RentalPayment (thuê xe) - Cân đối 50-50
                    RentalPayment::create([
                        'order_id' => $firstRentalOrder->order_id,
                        'status_deposit' => 'Successful',
                        'full_payment_status' => 'Successful',
                        'deposit_amount' => rand(10000000, 30000000), // 10-30 triệu
                        'total_amount' => rand(50000000, 150000000), // 50-150 triệu (tương đương payment)
                        'remaining_amount' => 0,
                        'due_date' => $paymentDate,
                        'payment_date' => $paymentDate,
                        'transaction_code' => 'RENTAL_SEED_' . $month . '_' . $i . '_' . time() . rand(100, 999),
                    ]);
                    $successRental++;
                } catch (\Exception $e) {
                    $this->command->error("Lỗi tạo RentalPayment tháng {$month}: " . $e->getMessage());
                }
            }

            $this->command->info("✓ Tháng {$month}/2025: Payment={$successPayment}, Rental={$successRental}");
        }

        $this->command->info('');
        $this->command->info('✅ Hoàn thành! Đã tạo dữ liệu cho tất cả 12 tháng năm 2025');
        $this->command->info('🎨 Biểu đồ dashboard giờ sẽ hiển thị đầy đủ và đẹp hơn!');
        $this->command->info('');
        $this->command->info('📊 Tổng kết:');
        $this->command->info('   - Payment (Xe bán): ' . Payment::whereYear('full_payment_date', 2025)->count() . ' records');
        $this->command->info('   - RentalPayment (Thuê xe): ' . RentalPayment::whereYear('payment_date', 2025)->count() . ' records');
    }
}
