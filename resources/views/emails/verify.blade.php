<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
</head>
<body>
    <h1>Hello, {{ $user->FirstName }}!</h1>
    <p>Thank you for registering. Please click the link below to verify your email address:</p>
    <a href="{{ $verificationUrl }}">Verify Email Address</a>
    <p>If you did not create an account, no further action is required.</p>
</body>
</html>
