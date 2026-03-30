<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Activated - Mednova Medical Consultations</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #333333; margin-bottom: 20px;">Hello {{ $customer->full_name }},</h2>

            <p style="color: #555555; line-height: 1.6;">
                Congratulations! Your subscription on the <strong>Mednova Medical Consultations</strong> platform has been successfully activated 🎉
            </p>

            <p style="color: #555555; line-height: 1.6;">
                You now have full access to the platform and can start providing services and receiving consultations from your clients immediately.
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url($url) }}"
                   style="display: inline-block; background-color: #28a745; color: white; font-weight: bold; padding: 12px 25px; text-decoration: none; border-radius: 6px; transition: background-color 0.3s;">
                    Go to Your Dashboard
                </a>
            </p>

            <p style="color: #555555; line-height: 1.6;">
                Thank you for choosing <strong>Mednova Medical Consultations</strong>. We look forward to supporting you in providing professional and secure medical services.
            </p>

            <p style="color: #999999; font-size: 12px; margin-top: 30px;">
                If you did not subscribe to this package, please ignore this email.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
