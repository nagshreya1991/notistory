<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>Hi {{ $name }},</p>
    <p>We received a request to reset your password. You can reset your password by clicking the link below:</p>
    <a href="{{ $url }}">Reset Password</a>
    <p>If you did not request a password reset, please ignore this email.</p>

    <p>Thanks,<br>{{ $site_name }}</p>
</body>
</html>