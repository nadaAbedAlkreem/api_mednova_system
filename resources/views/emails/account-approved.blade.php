<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Approval - Mednova Medical Consultations</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #333333; margin-bottom: 20px;">Hello {{ $user->full_name }},</h2>

            <p style="color: #555555; line-height: 1.6;">
                We are pleased to inform you that your account has been successfully approved on the <strong>Mednova Medical Consultations</strong> platform, a leading platform for providing medical consultations and managing patient records securely and professionally.
            </p>

            <p style="color: #555555; line-height: 1.6;">
                You can now access your profile and start using our services by clicking the button below:
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url($url) }}"
                   style="display: inline-block; background-color: #007BFF; color: white; font-weight: bold; padding: 12px 25px; text-decoration: none; border-radius: 6px; transition: background-color 0.3s;">
                    Visit Your Profile
                </a>
            </p>

            <p style="color: #555555; line-height: 1.6;">
                Thank you for using the <strong>Mednova Medical Consultations</strong> platform. We always look forward to serving you with the highest level of professionalism and quality.
            </p>

            <p style="color: #999999; font-size: 12px; margin-top: 30px;">
                If you did not create this account, please ignore this email.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
