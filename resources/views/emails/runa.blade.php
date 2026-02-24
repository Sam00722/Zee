<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 2rem auto; padding: 20px; }
        .section { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <p>Hello {{ $email }},</p>
        <p>Your withdrawal request has been approved, and your funds are ready to be claimed.
            To receive your funds, click the secure link below and follow the steps:</p>
        @if($url)
        <p>
            <strong>
                <a href="{{ $url }}" target="_blank" style="color: #007bff;">Claim Your Funds</a>
            </strong>
        </p>
        @endif
        <div class="section">
            <strong>Amount:</strong> ${{ number_format($amount ?? 0, 2) }}
        </div>
        <div class="section">
            <p>This link is secure and valid for one-time use. Please complete the process promptly.</p>
            <p>Thank you.</p>
            <p>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
