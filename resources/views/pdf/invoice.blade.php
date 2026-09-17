<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_code }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 2px 0;
            color: #555;
        }
        table.meta {
            width: 100%;
            margin-bottom: 16px;
        }
        table.meta td {
            vertical-align: top;
            padding: 2px 0;
        }
        table.meta .label {
            color: #555;
            width: 120px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.items th, table.items td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        table.items th {
            background-color: #f2f2f2;
        }
        table.items td.number {
            text-align: right;
        }
        table.totals {
            width: 100%;
            margin-top: 8px;
        }
        table.totals td {
            padding: 4px 8px;
        }
        table.totals .label {
            text-align: right;
            color: #555;
        }
        table.totals .value {
            text-align: right;
            width: 150px;
            font-weight: bold;
        }
        table.totals tr.grand-total .value,
        table.totals tr.grand-total .label {
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            background-color: #e6f4ea;
            color: #1e7e34;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .footer-note {
            margin-top: 10px;
            font-size: 10px;
            color: #777;
            text-align: center;
        }
        .qr-section {
            margin-top: 24px;
            text-align: center;
        }
        .qr-section img {
            width: 110px;
            height: 110px;
        }
        .qr-section p {
            margin: 4px 0 0;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p>Bookstore</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Kode Pesanan</td>
            <td>: <strong>{{ $order->order_code }}</strong></td>
            <td class="label">Status</td>
            <td>: <span class="status-badge">{{ $order->status }}</span></td>
        </tr>
        <tr>
            <td class="label">Tanggal Pesan</td>
            <td>: {{ $order->created_at->format('d M Y, H:i') }}</td>
            <td class="label">Dikonfirmasi Oleh</td>
            <td>: {{ $order->confirmedBy->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td>
            <td>: {{ $order->user->name }}</td>
            <td class="label">Tanggal Konfirmasi</td>
            <td>: {{ $order->confirmed_at?->format('d M Y, H:i') ?? '-' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Judul Buku</th>
                <th style="width: 60px;" class="number">Qty</th>
                <th style="width: 110px;" class="number">Harga Satuan</th>
                <th style="width: 120px;" class="number">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->book->title }}</td>
                    <td class="number">{{ $item->quantity }}</td>
                    <td class="number">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total Belanja</td>
            <td class="value">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
        @if ($order->cash_received !== null)
            <tr>
                <td class="label">Cash Diterima</td>
                <td class="value">Rp {{ number_format($order->cash_received, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Kembalian</td>
                <td class="value">Rp {{ number_format($order->change_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
    </table>

    @if (isset($qrCode))
        <div class="qr-section">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Verifikasi">
            <p>Scan untuk melihat Kode Pesanan ini</p>
        </div>
    @endif

    <div class="footer-note">
        Invoice ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan basah.
    </div>
</body>
</html>