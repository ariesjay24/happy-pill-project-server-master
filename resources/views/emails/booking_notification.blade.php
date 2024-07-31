<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation</title>
</head>
<body>
    <h1>Hello, {{ $user->FirstName }}!</h1>
    <p>Thank you for your booking. Here are the details of your booking:</p>
    <ul>
        <li>Service: {{ $booking->ServiceName }}</li>
        <li>Date: {{ $booking->BookingDate }}</li>
        <li>Time: {{ $booking->BookingTime }}</li>
        <li>Location: {{ $booking->Location }}</li>
        <li>Price: {{ $booking->Price }}</li>
    </ul>
    <p>We look forward to serving you!</p>
</body>
</html>
