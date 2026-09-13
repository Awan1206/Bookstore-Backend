<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    // GET /api/admin/orders/{order}/invoice -> download PDF
    public function download(Order $order)
    {
        $order->load(['user', 'items.book', 'confirmedBy']);

        $pdf = Pdf::loadView('pdf.invoice', compact('order'));

        return $pdf->download("invoice-{$order->order_code}.pdf");
    }
}