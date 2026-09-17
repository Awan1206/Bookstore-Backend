<?php

namespace App\Exports;

use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BestSellersSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return OrderItem::selectRaw('book_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['paid', 'completed'])
                    ->when($this->startDate, fn ($qq) => $qq->whereDate('created_at', '>=', $this->startDate))
                    ->when($this->endDate, fn ($qq) => $qq->whereDate('created_at', '<=', $this->endDate));
            })
            ->groupBy('book_id')
            ->with('book:id,title')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Judul Buku',
            'Jumlah Terjual',
            'Total Pendapatan (Rp)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->book->title,
            (int) $row->total_sold,
            (float) $row->total_revenue,
        ];
    }

    public function title(): string
    {
        return 'Buku Terlaris';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}