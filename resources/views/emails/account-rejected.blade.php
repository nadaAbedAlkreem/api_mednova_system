<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Regarding Your Registration Request - Mednova Medical Consultations</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #333333; margin-bottom: 20px;">Hello {{ $user->full_name }},</h2>

            <p style="color: #555555; line-height: 1.6;">
                Thank you for your interest in registering on the <strong>Mednova Medical Consultations</strong> platform.
            </p>

            <p style="color: #555555; line-height: 1.6;">
                After reviewing your account registration request and the submitted information, we regret to inform you that your registration request has not been approved at this time.
            </p>

            @if(!empty($reason))
                <p style="margin-top: 20px; font-weight: bold; color: #e74c3c;">Reason for rejection:</p>

                <p style="background-color:#f8f8f8; border-left:4px solid #e74c3c; padding:15px; border-radius:4px; color:#555555;">
                    {{ $reason }}
                </p>
            @endif

            <p style="color: #555555; line-height: 1.6; margin-top: 20px;">
                You may update your profile information and complete the required details using the link below:
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url($url) }}"
                   style="display: inline-block; background-color: #007BFF; color: white; font-weight: bold; padding: 12px 25px; text-decoration: none; border-radius: 6px; transition: background-color 0.3s;">
                    Edit Profile
                </a>
            </p>

            <p style="color: #555555; line-height: 1.6;">
                Once your information has been updated, your request will be reviewed again. If you have any questions, please feel free to contact the support team through the platform.
            </p>

            <p style="color: #555555; line-height: 1.6; margin-top: 30px;">
                Best wishes,<br>
                <strong>Mednova Medical Consultations Team</strong>
            </p>

            <p style="color: #999999; font-size: 12px; margin-top: 30px;">
                If you did not submit this request, please ignore this email.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
```
