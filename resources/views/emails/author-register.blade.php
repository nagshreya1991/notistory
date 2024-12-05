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
            background: #475168;
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
            background: #000;
        }
    </style>
</head>
<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #fff;" leftmargin="0">
<!--100% body table-->
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff"
       style="@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); font-family: 'Poppins', sans-serif;">
    <tr>
        <td>
            <table style="background-color: #fff; max-width:670px;  margin:0 auto;" width="100%" border="0"
                   align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height:80px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center;">
                        <a href="{{ $siteUrl }}" target="_blank">
                            <img align="center" alt="{{ $siteName }}" border="0" class="center fixedwidth"
                                 src="{{ $appUrl }}/public/images/logo.png"
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
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:0 35px;">
                                    <p>Hello {{ $userName }},</p>

                                    <p>Thank you for registering with us. Please click the link below to verify your email address:</p>

                                    <a href="{{ $siteUrl }}verify-email/{{ $token }}" class="btn">
                                        Verify Email
                                    </a>

                                    <p>If you did not create this account, no further action is required.</p>

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
