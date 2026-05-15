<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f3f2; font-family:'Poppins','Lato',Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f3f2; padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#111827; padding:24px 32px; text-align:center;">
                            <h1 style="margin:0; color:#fec89a; font-size:22px; font-weight:700; letter-spacing:0.02em;">
                                ProFormance
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 8px; font-size:20px; color:#111827; font-weight:700;">
                                Reset Your Password
                            </h2>

                            <p style="margin:0 0 20px; font-size:15px; color:#6b7280; line-height:1.6;">
                                We received a request to reset the password for the account associated with
                                <strong style="color:#111827;">{{ $email }}</strong>.
                            </p>

                            <p style="margin:0 0 24px; font-size:15px; color:#6b7280; line-height:1.6;">
                                Click the button below to choose a new password. This link will expire in
                                <strong style="color:#111827;">{{ $minutes }} minutes</strong>.
                            </p>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px;">
                                        <a href="{{ $resetUrl }}"
                                           style="display:inline-block; padding:14px 36px; background:#fec89a; color:#111827; text-decoration:none; border-radius:8px; font-size:16px; font-weight:600; letter-spacing:0.01em;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Fallback link --}}
                            <p style="margin:0 0 20px; font-size:13px; color:#9ca3af; line-height:1.5;">
                                If the button doesn't work, copy and paste this URL into your browser:
                            </p>
                            <p style="margin:0 0 24px; font-size:12px; color:#3b82f6; word-break:break-all; line-height:1.4;">
                                {{ $resetUrl }}
                            </p>

                            <hr style="border:none; border-top:1px solid #e5e7eb; margin:24px 0;">

                            <p style="margin:0; font-size:13px; color:#9ca3af; line-height:1.5;">
                                If you didn't request a password reset, you can safely ignore this email.
                                Your password will remain unchanged.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f9fafb; padding:20px 32px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                &copy; {{ date('Y') }} ProFormance. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
