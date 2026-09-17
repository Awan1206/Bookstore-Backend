<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesReportExport implements WithMultipleSheets
{
    protected ?string $startDate;

    protected ?string $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            'Ringkasan Penjualan' => new SalesSummarySheet($this->startDate, $this->endDate),
            'Buku Terlaris' => new BestSellersSheet($this->startDate, $this->endDate),
        ];
    }
}