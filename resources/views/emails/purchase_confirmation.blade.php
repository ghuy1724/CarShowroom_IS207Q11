<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 0; background-color: #f5f5f5;">
    <div style="width: 100%; max-width: 700px; margin: 0 auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
        <div style="background-color: #1a237e; padding: 20px; text-align: center;">
            <img src="https://raw.githubusercontent.com/cotda/image/refs/heads/main/logo%20(2).png" alt="SuperWeb Auto Showroom Logo" style="width: 120px; height: auto; margin-bottom: 10px;">
            <h1 style="font-size: 20px; color: #ffffff; margin: 10px 0 0; text-transform: uppercase;">Xác nhận Thanh Toán Đặt Cọc Mua Xe Thành Công</h1>
        </div>
        
        <div style="padding: 20px;">
            <div style="font-size: 16px; margin-bottom: 20px; color: #333333;">
                Kính gửi Quý khách <strong>{{ $name }}</strong>,
            </div>
            
            <div style="font-size: 14px; line-height: 1.5; margin-bottom: 20px; color: #333333;">
                Trân trọng cảm ơn Quý khách đã tin tưởng và lựa chọn dịch vụ mua xe tại <strong>SuperWeb Auto Showroom</strong>. 
                Chúng tôi xin xác nhận thông tin thanh toán đặt cọc của Quý khách như sau:
            </div>
            
            <div style="background-color: #f8f9fc; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 20px;">
                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: #4a4a4a; font-weight: bold; display: inline-block; width: 150px;">Mã Đơn Hàng:</span>
                    <span style="font-weight: bold; color: #1a237e;">{{ $order_id }}</span>
                </div>
                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: #4a4a4a; font-weight: bold; display: inline-block; width: 150px;">Số Tiền Đặt Cọc:</span>
                    <span style="font-weight: bold; color: #1a237e;">{{ number_format($deposit_amount, 0, ',', '.') }} VND</span>
                </div>
                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: #4a4a4a; font-weight: bold; display: inline-block; width: 150px;">Số Tiền Còn Lại:</span>
                    <span style="font-weight: bold; color: #1a237e;">{{ number_format($remaining_amount, 0, ',', '.') }} VND</span>
                </div>
                <div style="margin-bottom: 10px; font-size: 14px;">
                    <span style="color: #4a4a4a; font-weight: bold; display: inline-block; width: 150px;">Tổng Giá Trị Xe:</span>
                    <span style="font-weight: bold; color: #1a237e;">{{ number_format($total_amount, 0, ',', '.') }} VND</span>
                </div>
            </div>

            <div style="background-color: #e8f5e9; padding: 15px; border: 1px solid #4caf50; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                <div style="color: #2e7d32; font-weight: bold; margin-bottom: 10px;">✓ Thanh toán thành công</div>
                <p style="margin: 0; color: #333333;">Chúng tôi đã nhận được thanh toán đặt cọc. Đơn hàng của Quý khách đang được xử lý. 
                Vui lòng hoàn thành thanh toán phần còn lại để hoàn tất giao dịch.</p>
            </div>
            
            <div style="background-color: #f8f9fc; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                <div style="font-weight: bold; color: #1a237e; margin-bottom: 10px; font-size: 16px;">Thông Tin Liên Hệ</div>
                <div><strong>Điện thoại:</strong> 0708985088</div>
                <div><strong>Email:</strong> 23520706@gm.uit.edu.vn</div>
                <p>Đội ngũ hỗ trợ khách hàng của chúng tôi luôn sẵn sàng phục vụ Quý khách 24/7.</p>
            </div>
            
            <div style="border-top: 1px solid #e0e0e0; padding-top: 20px; text-align: center; font-size: 12px; color: #666666;">
                <p>Đây là email tự động. Vui lòng không trả lời email này.</p>
                <p>&copy; 2025 SuperWeb Auto Showroom. Tất cả các quyền được bảo lưu.</p>
            </div>
        </div>
    </div>
</body>
</html>
