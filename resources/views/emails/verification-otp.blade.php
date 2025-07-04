<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .header h1 {
            color: #009688;
            margin: 0;
            padding: 0;
        }
        .content {
            padding: 20px 0;
        }
        .otp-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #333;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ceylon Rover</h1>
        </div>
        
        <div class="content">
            <p>Hello,</p>
            
            <p>Thank you for registering with Ceylon Rover. To verify your email address, please use the following One-Time Password (OTP):</p>
            
            <div class="otp-box">
                {{ $otp }}
            </div>
            
            <p>This OTP is valid for 10 minutes. If you did not request this verification, please ignore this email.</p>
            
            <p>Best regards,<br>The Ceylon Rover Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Ceylon Rover. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>
