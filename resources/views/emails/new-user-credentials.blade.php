<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Rental System Account</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f4f4f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%"
                    style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;"
                    cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="font-size:20px; color:#1f2937; margin:0 0 16px;">Welcome to Rental System</h1>
                            <p style="font-size:15px; color:#374151; line-height:1.5; margin:0 0 16px;">
                                Hi {{ $fullName }},
                            </p>
                            <p style="font-size:15px; color:#374151; line-height:1.5; margin:0 0 24px;">
                                An account has been created for you as a <strong>{{ ucfirst($role) }}</strong> on the
                                Rental System platform. Use the credentials below to log in.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px 20px; font-size:14px; color:#374151; line-height:1.8;">
                                        <strong>Email:</strong> {{ $email }}<br>
                                        <strong>Username:</strong> {{ $username }}<br>
                                        <strong>Temporary Password:</strong> {{ $password }}
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px; color:#374151; line-height:1.5; margin:0 0 24px;">
                                For your security, please log in and change this password as soon as possible.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:6px; background-color:#2563eb;">
                                        <a href="{{ $loginUrl }}" target="_blank"
                                            style="display:inline-block; padding:12px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:600;">
                                            Log In Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:13px; color:#6b7280; line-height:1.5; margin:24px 0 0;">
                                If you weren't expecting this account or believe this was sent in error, please contact
                                your landlord or the system administrator.
                            </p>
                            <p style="font-size:14px; color:#374151; margin:24px 0 0;">
                                Thanks,<br>{{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
