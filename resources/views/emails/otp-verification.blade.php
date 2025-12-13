{{-- Author: Liew Zi Li (email otp verification) --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OTP Verification - TARUMT FMS</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">TARUMT FMS</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0;">
        <h2 style="color: #cb2d3e; margin-top: 0;">Hello {{ $userName }},</h2>
        
        <p>Thank you for registering with TARUMT Facilities Management System!</p>
        
        <p>To activate your account, please use the OTP code below:</p>
        
        <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px solid #cb2d3e;">
            <h1 style="color: #cb2d3e; font-size: 36px; letter-spacing: 5px; margin: 0;">{{ $otpCode }}</h1>
        </div>
        
        <p style="color: #666; font-size: 14px;">This code will expire in 3 minutes.</p>
        
        <p>If you didn't create an account, please ignore this email.</p>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 12px;">
            <p>Best regards,<br>TARUMT FMS Team</p>
        </div>
    </div>
</body>
</html>

