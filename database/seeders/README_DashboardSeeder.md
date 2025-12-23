# Hướng dẫn tạo dữ liệu mẫu cho Dashboard

## Mục đích
Tạo dữ liệu thanh toán mẫu cho tất cả 12 tháng năm 2025 để biểu đồ dashboard hiển thị đẹp và đầy đủ.

## File đã tạo
- `database/seeders/DashboardChartSeeder.php`

## Cách sử dụng

### Chạy seeder
```bash
php artisan db:seed --class=DashboardChartSeeder
```

### Dữ liệu được tạo
Seeder sẽ tạo cho mỗi tháng (1-12/2025):
- **2-5 Payment records** (xe bán & phụ kiện)
  - Tổng tiền: 300-800 triệu VNĐ
  - Đặt cọc: 50-100 triệu VNĐ
  - Trạng thái: Successful

- **2-5 RentalPayment records** (thuê xe)
  - Tổng tiền: 15-50 triệu VNĐ
  - Đặt cọc: 5-10 triệu VNĐ
  - Trạng thái: Successful

### Kết quả
- Biểu đồ sẽ hiển thị dữ liệu cho **TẤT CẢ 12 THÁNG**
- Mỗi tháng có doanh thu ngẫu nhiên
- Dữ liệu trông tự nhiên và đẹp mắt

## Lưu ý

### Yêu cầu
Cần có ít nhất:
- 1 record trong bảng `orders`
- 1 record trong bảng `rental_orders`

Nếu chưa có, seeder sẽ báo lỗi và yêu cầu tạo trước.

### Xóa dữ liệu cũ (nếu cần)
Nếu muốn xóa dữ liệu seed cũ và tạo lại:

```sql
-- Xóa dữ liệu seed (có transaction_code bắt đầu bằng SEED hoặc RENTAL_SEED)
DELETE FROM payments WHERE transaction_code LIKE 'SEED_%';
DELETE FROM rental_payments WHERE transaction_code LIKE 'RENTAL_SEED_%';
```

Sau đó chạy lại seeder:
```bash
php artisan db:seed --class=DashboardChartSeeder
```

### Tùy chỉnh

Nếu muốn thay đổi số lượng hoặc giá trị, sửa trong file seeder:

```php
// Số payment mỗi tháng
$paymentsCount = rand(2, 5); // Thay đổi 2-5 thành giá trị khác

// Giá trị Payment
'total_amount' => rand(300000000, 800000000), // 300-800 triệu

// Giá trị RentalPayment
'total_amount' => rand(15000000, 50000000), // 15-50 triệu
```

## Kiểm tra kết quả

1. Truy cập dashboard: `/admin/dashboard`
2. Xem biểu đồ đầu tiên
3. Biểu đồ sẽ hiển thị đầy đủ 12 tháng với dữ liệu

## Troubleshooting

### Lỗi: "Cần có ít nhất 1 Order"
**Giải pháp**: Tạo order thủ công hoặc chạy seeder khác trước:
```bash
php artisan db:seed --class=OrderSeeder
```

### Biểu đồ vẫn trống
**Kiểm tra**:
1. Xem database có dữ liệu chưa:
```sql
SELECT COUNT(*) FROM payments WHERE YEAR(full_payment_date) = 2025;
SELECT COUNT(*) FROM rental_payments WHERE YEAR(payment_date) = 2025;
```

2. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
```

3. Refresh trang dashboard (Ctrl + F5)
