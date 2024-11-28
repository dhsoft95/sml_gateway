<!-- resources/views/qr/show.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .qr-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .control-number {
            text-align: center;
            font-size: 18px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .details {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="amount">
        {{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}
    </div>

    <div class="control-number">
        Control #: {{ $qrCode->control_number }}
    </div>

    <div class="qr-container">
        {!! $qrCode->qr_data !!}
    </div>

    <div class="details">
        <p><strong>Status:</strong> {{ $invoice->status }}</p>
        <p><strong>Created:</strong> {{ $qrCode->created_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Expires:</strong> {{ $qrCode->expires_at->format('Y-m-d H:i:s') }}</p>
    </div>

    <a href="{{ route('qr.list') }}" class="back-link">← Back to List</a>
</div>
</body>
</html>
