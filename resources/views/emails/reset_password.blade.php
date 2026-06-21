<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đặt lại mật khẩu</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #FF4D00; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #111; }
        .logo span { color: #FF4D00; }
        .content { font-size: 16px; line-height: 1.6; color: #555; }
        .btn-wrapper { text-align: center; margin: 30px 0; }
        .btn { background-color: #FF4D00; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; box-shadow: 0 4px 12px rgba(255, 77, 0, 0.2); }
        .footer { text-align: center; margin-top: 40px; font-size: 13px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Saigon<span>Shoes</span></div>
        </div>
        <div class="content">
            <p>Xin chào,</p>
            <p>Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
            <p>Vui lòng click vào nút dưới đây để tiến hành đặt lại mật khẩu mới. Liên kết này có hiệu lực trong vòng 60 phút.</p>
            <div class="btn-wrapper">
                <a href="{{ $resetUrl }}" class="btn" target="_blank" style="color: #ffffff;">Đặt lại mật khẩu</a>
            </div>
            <p>Nếu bạn không yêu cầu thay đổi mật khẩu này, bạn không cần thực hiện thêm hành động nào.</p>
            <p>Trân trọng,<br>Đội ngũ SaigonShoes</p>
        </div>
        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không phản hồi trực tiếp.</p>
            <p>&copy; {{ date('Y') }} SaigonShoes. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
