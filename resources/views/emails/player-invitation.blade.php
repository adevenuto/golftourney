<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Set up your GolfTourney login</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#f2eee3; -webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2eee3" style="background-color:#f2eee3;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#faf7ef; border-radius:16px; overflow:hidden; border:1px solid #e8e1d0;">
                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:40px 40px 12px 40px;">
                            <img src="{{ asset('img/logo-emblem-email.png') }}?v=1" width="112" height="112" alt="GolfTourney" style="display:block; border:0; outline:none; text-decoration:none; width:112px; height:112px;">
                        </td>
                    </tr>

                    <!-- Heading -->
                    <tr>
                        <td align="center" style="padding:4px 40px 0 40px;">
                            <h1 style="margin:0; font-family:Fraunces,Georgia,'Times New Roman',serif; font-size:26px; font-weight:600; line-height:1.3; color:#14432f;">
                                You're invited to GolfTourney
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:20px 40px 0 40px; font-family:Figtree,'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; line-height:1.6; color:#1b1d1a;">
                            <p style="margin:0 0 16px 0;">Hi {{ $firstName }},</p>
                            <p style="margin:0;">You've been invited to manage your handicap on GolfTourney — track your rounds and keep your Handicap Index up to date.</p>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td align="center" style="padding:28px 40px 4px 40px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#14432f" style="border-radius:9999px;">
                                        <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:14px 34px; font-family:Figtree,'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:16px; font-weight:600; color:#faf7ef; text-decoration:none; border-radius:9999px;">
                                            Set up your account
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Fallback link -->
                    <tr>
                        <td style="padding:20px 40px 0 40px; font-family:Figtree,'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:13px; line-height:1.6; color:#6b6f68;">
                            <p style="margin:0 0 4px 0;">Or paste this link into your browser:</p>
                            <a href="{{ $url }}" style="color:#8a6c3f; text-decoration:underline; word-break:break-all;">{{ $url }}</a>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:28px 40px 0 40px;">
                            <div style="border-top:1px solid #e8e1d0; font-size:0; line-height:0;">&nbsp;</div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:16px 40px 40px 40px; font-family:Figtree,'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:12px; line-height:1.6; color:#9a9e96;">
                            <p style="margin:0 0 8px 0;">If you weren't expecting this, you can safely ignore this email.</p>
                            <p style="margin:0; font-family:Fraunces,Georgia,'Times New Roman',serif; font-size:14px; letter-spacing:1px; color:#b08d57;">GolfTourney</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
