<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back in Stock!</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f3f2; font-family:'Poppins','Lato',Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f3f2; padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background:#111827; padding:24px 32px; text-align:center;">
                            <h1 style="margin:0; color:#fec89a; font-size:22px; font-weight:700;">
                                ProFormance
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 8px; font-size:20px; color:#111827; font-weight:700;">
                                Great News — It's Back! 🎉
                            </h2>

                            <p style="margin:0 0 20px; font-size:15px; color:#6b7280; line-height:1.6;">
                                The item you were waiting for is back in stock:
                            </p>

                            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px 20px; margin-bottom:24px;">
                                <p style="margin:0; font-size:16px; font-weight:600; color:#111827;">
                                    {{ $productName }}
                                </p>
                                @if($variantName)
                                    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">
                                        {{ $variantName }}
                                    </p>
                                @endif
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px;">
                                        <a href="{{ $productUrl }}"
                                           style="display:inline-block; padding:14px 36px; background:#fec89a; color:#111827; text-decoration:none; border-radius:8px; font-size:16px; font-weight:600;">
                                            Shop Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:13px; color:#9ca3af; line-height:1.5;">
                                Stock is limited, so grab it while it lasts. If you no longer wish to receive these
                                notifications, simply ignore this email.
                            </p>
                        </td>
                    </tr>

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
