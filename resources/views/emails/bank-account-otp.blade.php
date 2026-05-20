<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Bank Account Verification Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .otp { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a56db; text-align: center; padding: 16px 0; }
        .footer { font-size: 12px; color: #888; margin-top: 24px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Hello {{ $user->full_name }},</h2>
    <p>Use the verification code below to confirm your bank account on the Mednova platform.</p>
    <div class="otp">{{ $otp }}</div>
    <p>This code is only valid for <strong>10 minutes</strong>.</p>
    <p>If you did not request this code, please ignore this email and contact technical support.</p>
    <div class="footer">
        <p>Mednova Team — Do not share this code with anyone.</p>
    </div>
</div>
</body>
</html>
