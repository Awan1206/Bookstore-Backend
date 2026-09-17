<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesSummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected ?string $startDate;

    protected ?string $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        return Order::with('user')
            ->whereIn('status', ['paid', 'completed'])
            ->when($this->startDate, fn ($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Pesanan',
            'Tanggal',
            'Pelanggan',
            'Status',
            'Total Belanja (Rp)',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_code,
            $order->created_at->format('Y-m-d H:i'),
            $order->user->name,
            ucfirst($order->status),
            (float) $order->total_amount,
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Penjualan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}