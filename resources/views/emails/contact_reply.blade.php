<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Phản hồi liên hệ từ SaigonShoes</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 2px;
        }
        .header span {
            color: #ff4d00;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .message-box {
            background-color: #f1f5f9;
            border-left: 4px solid #ff4d00;
            padding: 16px;
            margin: 24px 0;
            border-radius: 8px;
            font-style: italic;
            font-size: 14px;
        }
        .reply-box {
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
            margin-bottom: 24px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SAIGON<span style="color: #ff4d00;">SHOES</span></h1>
        </div>
        <div class="content">
            <p class="greeting">Xin chào {{ $customerName }},</p>
            <p>Cảm ơn bạn đã liên hệ với SaigonShoes. Chúng tôi đã nhận được thông tin đóng góp / thắc mắc của bạn:</p>
            
            <div class="message-box">
                "{{ $originalMessage }}"
            </div>

            <p class="greeting">Ban quản trị SaigonShoes xin phản hồi như sau:</p>
            <div class="reply-box">
                {!! nl2br(e($replyContent)) !!}
            </div>

            <p>Nếu bạn có bất kỳ câu hỏi nào khác, vui lòng liên hệ lại với chúng tôi qua Hotline 1900 6789.</p>
            <p>Trân trọng,<br><strong>Ban quản trị SaigonShoes</strong></p>
        </div>
        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống chăm sóc khách hàng của SaigonShoes.</p>
            <p>© 2026 SaigonShoes. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
