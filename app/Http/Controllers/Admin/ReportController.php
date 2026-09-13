<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // Rekap penjualan untuk ditampilkan sebagai JSON di frontend (Nuxt)
    // GET /api/admin/reports?start_date=2026-01-01&end_date=2026-01-31
    public function index(Request $request)
    {
        $ordersQuery = Order::whereIn('status', ['paid', 'completed'])
            ->when($request->start_date, fn ($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('created_at', '<=', $request->end_date));

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (clone $ordersQuery)->sum('total_amount');

        $bestSellers = OrderItem::selectRaw('book_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['paid', 'completed'])
                ->when($request->start_date, fn ($qq) => $qq->whereDate('created_at', '>=', $request->start_date))
                ->when($request->end_date, fn ($qq) => $qq->whereDate('created_at', '<=', $request->end_date)))
            ->groupBy('book_id')
            ->with('book:id,title')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        return response()->json([
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'best_sellers' => $bestSellers->map(fn ($row) => [
                'book_title' => $row->book->title,
                'total_sold' => (int) $row->total_sold,
                'total_revenue' => (float) $row->total_revenue,
            ]),
        ]);
    }

    // GET /api/admin/reports/download?start_date=...&end_date=...
    public function download(Request $request)
    {
        return Excel::download(
            new SalesReportExport($request->start_date, $request->end_date),
            'laporan-penjualan.xlsx'
        );
    }
}