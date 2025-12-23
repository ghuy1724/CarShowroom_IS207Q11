<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo quá hạn trả xe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #dc3545;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            color: #333333;
        }
        .content p {
            line-height: 1.6;
            margin: 10px 0;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid: #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box strong {
            color: #856404;
        }
        .fee-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        .fee-box strong {
            color: #721c24;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin: 20px 0;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⚠️ THÔNG BÁO QUÁ HẠN TRẢ XE</h1>
        </div>
        <div class="content">
            <p>Kính gửi <strong>{{ $data['name'] }}</strong>,</p>
            
            <p>Chúng tôi xin thông báo rằng hợp đồng thuê xe của quý khách đã <strong>quá hạn trả xe</strong>.</p>
            
            <div class="info-box">
                <p><strong>Thông tin hợp đồng:</strong></p>
                <p>📋 Mã hóa đơn: <strong>#{{ $data['receipt_id'] }}</strong></p>
                <p>🚗 Xe thuê: <strong>{{ $data['car_name'] }}</strong></p>
                <p>📅 Ngày kết thúc thuê: <strong>{{ \Carbon\Carbon::parse($data['rental_end_date'])->format('d/m/Y H:i') }}</strong></p>
            </div>

            <div class="fee-box">
                <p><strong>Chi phí phát sinh do quá hạn:</strong></p>
                <p>⏰ Số ngày quá hạn: <strong>{{ $data['overdue_days'] }} ngày</strong></p>
                <p>💰 Giá thuê mỗi ngày: <strong>{{ number_format($data['rental_price_per_day'], 0, ',', '.') }} VNĐ</strong></p>
                <p style="font-size: 18px; color: #721c24;">💸 Tổng phí quá hạn: <strong>{{ number_format($data['overdue_fee'], 0, ',', '.') }} VNĐ</strong></p>
            </div>

            <p><strong>Lưu ý quan trọng:</strong></p>
            <ul>
                <li>Phí quá hạn được tính theo số ngày quá hạn × giá thuê mỗi ngày</li>
                <li>Quý khách vui lòng trả xe và thanh toán phí quá hạn sớm nhất có thể</li>
                <li>Mỗi ngày trễ thêm sẽ phát sinh thêm phí tương ứng</li>
                <li>Quý khách sẽ nhận được email thông báo hàng ngày cho đến khi trả xe</li>
            </ul>

            <p>Để trả xe, quý khách vui lòng liên hệ với chúng tôi hoặc đến trực tiếp showroom trong giờ làm việc.</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('rental.payment.vnpay_overdue', ['receipt_id' => $data['receipt_id']]) }}" 
                   class="button" 
                   style="display: inline-block; padding: 15px 40px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                    💳 Thanh toán phí quá hạn qua VNPay
                </a>
            </div>

            <p style="font-size: 12px; color: #666; text-align: center;">
                Sau khi thanh toán thành công, xe sẽ tự động được trả và có thể thuê lại.
            </p>

            <p>Trân trọng,<br><strong>CarShowroom Team</strong></p>
        </div>
        <div class="footer">
            <p>© 2025 CarShowroom. Mọi quyền được bảo lưu.</p>
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>
</html>
