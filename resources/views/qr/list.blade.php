<!-- resources/views/qr/list.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .view-button {
            display: inline-block;
            padding: 6px 12px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>QR Codes List</h1>
    <table>
        <thead>
        <tr>
            <th>Control Number</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($qrCodes as $qrCode)
            <tr>
                <td>{{ $qrCode->control_number }}</td>
                <td>{{ number_format($qrCode->invoice->amount, 2) }} {{ $qrCode->invoice->currency }}</td>
                <td>{{ $qrCode->invoice->status }}</td>
                <td>{{ $qrCode->created_at->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="{{ route('qr.show', $qrCode->control_number) }}" class="view-button">View QR</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $qrCodes->links() }}
</div>
</body>
</html>
