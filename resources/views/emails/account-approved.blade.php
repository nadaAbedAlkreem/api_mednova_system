<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>اعتماد الحساب - مدنوفا للاستشارات الطبية</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #333333; margin-bottom: 20px;">مرحباً {{ $user->full_name }}،</h2>

            <p style="color: #555555; line-height: 1.6;">
                نود إعلامك بأن حسابك قد تم اعتماده بنجاح على منصة <strong>مدنوفا للاستشارات الطبية</strong>، المنصة الرائدة في تقديم الاستشارات الطبية وإدارة ملفات المرضى بأمان واحترافية.
            </p>

            <p style="color: #555555; line-height: 1.6;">
                يمكنك الآن الوصول إلى ملفك الشخصي والبدء في استخدام خدماتنا عبر الضغط على الزر أدناه:
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url($url) }}"
                   style="display: inline-block; background-color: #007BFF; color: white; font-weight: bold; padding: 12px 25px; text-decoration: none; border-radius: 6px; transition: background-color 0.3s;">
                    زيارة ملفك الشخصي
                </a>
            </p>

            <p style="color: #555555; line-height: 1.6;">
                نشكرك لاستخدامك منصة <strong>مدنوفا للاستشارات الطبية</strong>. نتطلع دائماً لخدمتك بأفضل مستوى من الاحترافية والجودة.
            </p>

            <p style="color: #999999; font-size: 12px; margin-top: 30px;">
                إذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذا البريد.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
