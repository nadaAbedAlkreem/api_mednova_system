<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>بخصوص طلب التسجيل - مدنوفا للاستشارات الطبية</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <tr>
        <td style="padding: 30px;">
            <h2 style="color: #333333; margin-bottom: 20px;">مرحباً {{ $user->full_name }}،</h2>

            <p style="color: #555555; line-height: 1.6;">
                نشكرك على اهتمامك بالتسجيل في منصة <strong>مدنوفا للاستشارات الطبية</strong>.
            </p>

            <p style="color: #555555; line-height: 1.6;">
                بعد مراجعة طلب إنشاء الحساب والبيانات المرفقة، نود إعلامك بأنه لم يتم قبول طلب التسجيل في الوقت الحالي.
            </p>

            @if(!empty($reason))
                <p style="margin-top: 20px; font-weight: bold; color: #e74c3c;">سبب الرفض:</p>

                <p style="background-color:#f8f8f8; border-left:4px solid #e74c3c; padding:15px; border-radius:4px; color:#555555;">
                    {{ $reason }}
                </p>
            @endif

            <p style="color: #555555; line-height: 1.6; margin-top: 20px;">
                يمكنك تعديل بيانات ملفك الشخصي واستكمال المعلومات المطلوبة من خلال الرابط أدناه:
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ url($url) }}"
                   style="display: inline-block; background-color: #007BFF; color: white; font-weight: bold; padding: 12px 25px; text-decoration: none; border-radius: 6px; transition: background-color 0.3s;">
                    تعديل الملف الشخصي
                </a>
            </p>

            <p style="color: #555555; line-height: 1.6;">
                بعد تحديث البيانات، سيتم إعادة مراجعة طلبك مرة أخرى. في حال كان لديك أي استفسار، لا تتردد في التواصل مع فريق الدعم عبر المنصة.
            </p>

            <p style="color: #555555; line-height: 1.6; margin-top: 30px;">
                مع تمنياتنا لك بالتوفيق،<br>
                <strong>فريق مدنوفا للاستشارات الطبية</strong>
            </p>

            <p style="color: #999999; font-size: 12px; margin-top: 30px;">
                إذا لم تكن قد تقدمت بهذا الطلب، يرجى تجاهل هذا البريد.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
