<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #ffffff; color: #333333; line-height: 1.6;">

<p>Hi {{ $user->full_name }},</p>

<p>Thank you for registering with <strong>{{ config('app.name') }}</strong>.</p>
<p>To verify your email address and activate your account, please click the link below:</p>

<p>
    <a href="{{ $verificationUrl }}" style="color: #1a73e8; text-decoration: none;">
        Verify Email Address
    </a>
</p>

<p>If you did not create an account with <strong>{{ config('app.name') }}</strong>, you can safely ignore this email.</p>

<p>Best regards,<br>
    The {{ config('app.name') }} Team</p>

</body>
</html>
