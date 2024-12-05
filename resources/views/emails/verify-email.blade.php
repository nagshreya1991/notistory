<!doctype html>
<html lang="en-US">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <title>Welcome to {{ $siteName }}</title>
    <meta name="description" content="Verify Email Template.">
    <style type="text/css">
        .btn {
            color: #ffffff !important;
            text-decoration: none;
            background: #434854;
            border: none;
            height: 43px;
            font-size: 18px;
            font-weight: 200;
            line-height: 43px;
            padding: 0 20px;
            width: 190px;
            margin: auto;
            border-radius: 0.25rem;
            display: inline-block;
            cursor: pointer;
        }

        .btn:hover {
            background: #393d47;
        }

        p {
            color: #434854;
            font-family: "Be Vietnam Pro", sans-serif;
            font-size: 14px;
        }
    </style>
</head>
<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f3f3f3;" leftmargin="0">
<!--100% body table-->
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff"
       style="@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');">
    <tr>
        <td>
            <table style="background-color:#fff;color:#000;border-radius:0;width:600px;margin:0 auto" width="100%"
                   border="0"
                   align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height:80px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center;">
                        <a href="{{ $siteUrl }}" target="_blank">
                            <img alt="{{ $siteName }}" class="center fixedwidth"
                                 src="{{ asset('public/images/logo.png') }}"
                                 style="-ms-interpolation-mode: bicubic; height: auto; width: 152px;"
                                 title="{{ $siteName }}"/>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="height:20px;">&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                               style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                            <tr>
                                <td style="height:40px;"><h2>Welcome to {{ $siteName }}!</h2></td>
                            </tr>
                            <tr>
                                <td style="padding:0 35px;">
                                    <p>Hello {{ $userName }},</p>

                                    <p>Thank you for joining our community! To complete your registration, please verify
                                        your email address by clicking the button below:</p>

                                    <a href="{{ $siteUrl }}verify-email/{{ $token }}" class="btn">
                                        Verify Email
                                    </a>

                                    <p>If you didn’t create an account, please ignore this email.</p>

                                    <p>Thanks,<br>{{ $siteName }} Team</p>

                                </td>
                            </tr>
                            <tr>
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="height:20px;">&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <p style="color:#455056; font-size:12px;line-height:18px; margin:20px 0 10px; text-align:center">
                            Please do not reply to this email as it is an automated message.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:center;">
                        <p style="font-size:14px; color:#393939; line-height:18px; margin:0 0 0;">&copy; {{ date('Y') }}
                            <strong>{{ $siteName }}</strong>. All rights reserved.</p>
                    </td>
                </tr>
                <tr>
                    <td style="height:80px;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<!--/100% body table-->
</body>
</html>
