<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Dishtansya</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7fafc; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h1 style="color: #e53e3e; margin-top: 0;">Welcome to Dishtansya! 🍔</h1>
        <p>Hello <strong>{{ $user->name ?? $user->email }}</strong>,</p>
        <p>Thank you for registering with <strong>Dishtansya</strong>, your premier food delivery app connecting you with food chains and restaurants around the globe.</p>
        <p>Your account (<strong>{{ $user->email }}</strong>) is now active and ready to order delicious meals.</p>
        <p style="margin-top: 30px;">Happy dining!<br><strong>The Dishtansya Team</strong></p>
    </div>
</body>
</html>
