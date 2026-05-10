<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رمز التحقق من الحساب البنكي</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .otp { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a56db; text-align: center; padding: 16px 0; }
        .footer { font-size: 12px; color: #888; margin-top: 24px; }
    </style>
</head>
<body>
<div class="container">
    <h2>مرحباً {{ $user->full_name }}</h2>
    <p>استخدم رمز التحقق أدناه لتأكيد حسابك البنكي على منصة Mednova.</p>
    <div class="otp">{{ $otp }}</div>
    <p>هذا الرمز صالح لمدة <strong>10 دقائق</strong> فقط.</p>
    <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد والتواصل مع الدعم الفني.</p>
    <div class="footer">
        <p>فريق Mednova — لا تشارك هذا الرمز مع أي شخص.</p>
    </div>
</div>
</body>
</html>
