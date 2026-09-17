<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceController extends Controller
{
    // GET /api/admin/orders/{order}/invoice -> download PDF
    public function download(Order $order)
    {
        $order->load(['user', 'items.book', 'confirmedBy']);

        // QR sama seperti yang ditunjukkan user saat checkout (isinya order_code),
        // supaya invoice bisa discan ulang kapan saja untuk lookup pesanan ini.
        $qrCode = base64_encode(QrCode::format('png')->size(150)->generate($order->order_code));

        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'qrCode'));

        return $pdf->download("invoice-{$order->order_code}.pdf");
    }
}