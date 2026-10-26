<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:8px;padding:28px;">
        <tr><td style="font-size:18px;font-weight:bold;padding-bottom:16px;">{{ $subjectLine }}</td></tr>
        <tr><td style="font-size:15px;line-height:1.6;">{!! nl2br(e($text)) !!}</td></tr>
        <tr><td style="font-size:12px;color:#7b8794;padding-top:24px;border-top:1px solid #e4e7eb;">
          This is an automated message about your order. Please do not reply to it.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
