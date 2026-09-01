<?php

namespace Cesa\Rekrutmen\Exports;

use Carbon\Carbon;
use Cesa\Rekrutmen\Models\JobPosting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecruitmentProgressExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle
{
    protected int $dataRowCount = 0;

    public function title(): string
    {
        return 'Recruitment Progress';
    }

    public function collection()
    {
        $postings = JobPosting::withCount(['applications'])->latest('created_at')->get();

        $rows = collect();
        $totalNeeded = 0;
        $totalApplicants = 0;
        $totalInProcess = 0;
        $totalHired = 0;

        $no = 1;
        foreach ($postings as $p) {
            $needed = $p->needed_count ?? 1;
            $applicants = $p->applications_count ?? 0;
            $hired = 0;
            $inProcess = $applicants - $hired;
            $fulfillment = $needed > 0 ? round(($hired / $needed) * 100, 1) : 0;
            $health = 'Normal';
            $status = $p->is_published ? 'Published (Tayang)' : 'Draft (Nonaktif)';

            $totalNeeded += $needed;
            $totalApplicants += $applicants;
            $totalInProcess += $inProcess;
            $totalHired += $hired;

            $rows->push([
                $no++,
                $p->title,
                'PT Complete Selular Group',
                $p->location ?? 'Indonesia',
                $needed,
                $applicants,
                $inProcess,
                $hired,
                $fulfillment.'%',
                $status,
                $health,
            ]);
        }

        $this->dataRowCount = $rows->count();

        // Add Summary / Total Row
        $avgFulfillment = $totalNeeded > 0 ? round(($totalHired / $totalNeeded) * 100, 1) : 0;
        $rows->push([
            'TOTAL',
            '',
            '',
            '',
            $totalNeeded,
            $totalApplicants,
            $totalInProcess,
            $totalHired,
            $avgFulfillment.'%',
            '',
            '',
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // 1. Insert Title & Header Metadata Block
        $sheet->insertNewRowBefore(1, 5);

        // Company Header
        $sheet->setCellValue('A1', 'PT COMPLETE SELULAR GROUP');
        $sheet->setCellValue('A2', 'LAPORAN MONITORING PROGRES PEMENUHAN TENAGA KERJA (RECRUITMENT PROGRESS)');
        $sheet->setCellValue('A3', 'Waktu Cetak: '.Carbon::now()->translatedFormat('d F Y, H:i').' WIB | Sumber Data: CESA HR System');

        // Table Column Headings (Row 5)
        $headings = [
            'NO',
            'POSISI LOWONGAN',
            'PERUSAHAAN',
            'LOKASI PENEMPATAN',
            'KEBUTUHAN',
            'TOTAL PELAMAR',
            'DALAM PROSES',
            'LOLOS / HIRED',
            'PEMENUHAN (%)',
            'STATUS PUBLIKASI',
            'STATUS HEALTH',
        ];

        foreach ($headings as $index => $heading) {
            $colLetter = chr(65 + $index);
            $sheet->setCellValue($colLetter.'5', $heading);
        }

        $startRow = 6;
        $endDataRow = $startRow + $this->dataRowCount - 1;
        $totalRow = $endDataRow + 1;

        // Title Block Styling
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0C2340'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new Color('1E293B'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));

        // Table Header Styling (Row 5)
        $sheet->getRowDimension(5)->setRowHeight(28);
        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0C2340'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '1E293B'],
                ],
            ],
        ];
        $sheet->getStyle('A5:K5')->applyFromArray($headerStyle);

        // Data Rows Styling
        for ($r = $startRow; $r <= $endDataRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(22);

            $isEven = ($r % 2 === 0);
            $bgColor = $isEven ? 'F8FAFC' : 'FFFFFF';

            $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getFont()->setBold(true);
            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Total applicants (Blue)
            $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$r}")->getFont()->setBold(true)->setColor(new Color('2563EB'));

            // In process (Amber)
            $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$r}")->getFont()->setBold(true)->setColor(new Color('D97706'));

            // Hired (Emerald)
            $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$r}")->getFont()->setBold(true)->setColor(new Color('059669'));

            $sheet->getStyle("I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Summary / Total Row Styling
        $sheet->getRowDimension($totalRow)->setRowHeight(24);
        $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A{$totalRow}:K{$totalRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 10,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '0F172A'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color'       => ['rgb' => '0F172A'],
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }
}
