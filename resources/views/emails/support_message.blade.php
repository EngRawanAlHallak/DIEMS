<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسالة دعم فني</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:30px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);padding:32px 40px;border-radius:12px 12px 0 0;text-align:center;">
                        <div style="font-size:36px;margin-bottom:8px;">📨</div>
                        <h1 style="color:#ffffff;margin:0;font-size:20px;font-weight:700;letter-spacing:0.5px;">
                            رسالة دعم فني جديدة
                        </h1>
                        <p style="color:#a0aec0;margin:6px 0 0;font-size:13px;">
                            Damascus International Exhibition Management System
                        </p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#ffffff;padding:36px 40px;" dir="rtl">

                        {{-- Tag --}}
                        <div style="margin-bottom:20px;">
              <span style="display:inline-block;background:#ebf8ff;color:#2b6cb0;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;">
                🔔 رسالة واردة من نظام الزوار
              </span>
                        </div>

                        {{-- Alert --}}
                        <div style="background:#fef3cd;border-right:4px solid #f59e0b;border-radius:6px;padding:12px 16px;margin-bottom:28px;font-size:13px;color:#92400e;line-height:1.6;">
                            وصلت رسالة دعم فني جديدة تستدعي المراجعة والرد. يُرجى التعامل معها في أقرب وقت ممكن.
                        </div>

                        {{-- Info Table --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:24px;">
                            <tr>
                                <td width="140" style="background:#f7fafc;padding:12px 16px;font-size:12px;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">
                                    📧 البريد
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#2d3748;border-bottom:1px solid #e2e8f0;">
                                    {{ $userEmail }}
                                </td>
                            </tr>
                            <tr>
                                <td style="background:#f7fafc;padding:12px 16px;font-size:12px;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;">
                                    📌 الموضوع
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#2d3748;border-bottom:1px solid #e2e8f0;">
                                    {{ $subjectText }}
                                </td>
                            </tr>
                            <tr>
                                <td style="background:#f7fafc;padding:12px 16px;font-size:12px;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.5px;">
                                    🕐 وقت الإرسال
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#2d3748;">
                                    {{ $sentAt ?? now()->format('Y-m-d — H:i') }}
                                </td>
                            </tr>
                        </table>

                        {{-- Divider --}}
                        <div style="height:1px;background:#e2e8f0;margin:28px 0;"></div>

                        {{-- Message --}}
                        <p style="margin:0 0 10px;font-size:12px;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.5px;">
                            📝 نص الرسالة
                        </p>
                        <div style="background:#f7fafc;border:1px solid #e2e8f0;border-radius:8px;padding:20px;font-size:14px;color:#4a5568;line-height:1.8;white-space:pre-wrap;word-break:break-word;">
                            {{ $messageText }}
                        </div>

                        {{-- Button --}}
                        <div style="text-align:center;margin-top:28px;">
                            <a href="mailto:{{ $userEmail }}"
                               style="display:inline-block;background:#1a1a2e;color:#ffffff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600;letter-spacing:0.3px;">
                                الرد المباشر على المرسل
                            </a>
                        </div>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f7fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
                        <p style="margin:0;font-size:12px;color:#a0aec0;">
                            مع التحية، <strong style="color:#718096;">فريق إدارة نظام DIEMS</strong>
                        </p>
                        <p style="margin:6px 0 0;font-size:12px;color:#a0aec0;">
                            نظام إدارة معرض دمشق الدولي — دفعة 2026
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
