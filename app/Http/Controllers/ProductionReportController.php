<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionPlan;
use App\Models\QualityControl;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class ProductionReportController extends Controller
{
    /**
     * Export production report
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:weekly,monthly,single',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
            'additional_info' => 'nullable|string',
            'production_plan_id' => 'required_if:report_type,single|exists:production_plans,id'
        ]);

        try {
            if ($validated['report_type'] === 'single') {
                $productions = ProductionPlan::with(['product', 'createdBy', 'approvedBy'])
                    ->where('id', $validated['production_plan_id'])
                    ->get();
            } else {
                $productions = ProductionPlan::with(['product', 'createdBy', 'approvedBy'])
                    ->whereBetween('target_date', [$validated['start_date'], $validated['end_date']])
                    ->orderBy('target_date', 'desc')
                    ->get();
            }

            $productionIds = $productions->pluck('id');
            $qcData = QualityControl::with(['inspector', 'productionPlan'])
                ->whereIn('production_plan_id', $productionIds)
                ->get()
                ->keyBy('production_plan_id');

            $productions->transform(function ($production) use ($qcData) {
                if (isset($qcData[$production->id])) {
                    $production->quality_control = $qcData[$production->id];
                }
                return $production;
            });

            $summary = [
                'total_plans' => $productions->count(),
                'total_target' => $productions->sum('quantity'),
                'total_produced' => $productions->sum('produced_quantity'),
                'total_qc_passed' => $productions->sum(function ($p) {
                    return $p->quality_control->passed_quantity ?? 0;
                }),
                'total_qc_failed' => $productions->sum(function ($p) {
                    return $p->quality_control->failed_quantity ?? 0;
                }),
                'completed_count' => $productions->where('status', 'completed')->count(),
                'in_progress_count' => $productions->where('status', 'in_progress')->count(),
            ];

            $summary['pass_rate'] = $summary['total_qc_passed'] + $summary['total_qc_failed'] > 0
                ? round(($summary['total_qc_passed'] / ($summary['total_qc_passed'] + $summary['total_qc_failed'])) * 100, 2)
                : 0;

            $reportData = [
                'report_type' => $validated['report_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'generated_at' => now(),
                'generated_by' => auth()->user()->name,
                'additional_info' => $validated['additional_info'] ?? '',
                'productions' => $productions,
                'summary' => $summary
            ];

            if ($validated['format'] === 'pdf') {
                return $this->generatePDF($reportData);
            } else {
                return $this->generateExcel($reportData);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF Report
     */
    private function generatePDF($data)
    {
        $pdf = Pdf::loadView('reports.production-report', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        $filename = $this->generateFilename($data, 'pdf');
        
        return $pdf->download($filename);
    }

    /**
     * Generate Excel Report
     */
    private function generateExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator($data['generated_by'])
            ->setTitle('Laporan Produksi')
            ->setSubject('Production Report')
            ->setDescription('Laporan hasil produksi');

        $row = 1;
        
        $sheet->setCellValue('A' . $row, 'LAPORAN PRODUKSI');
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $row++;
        $sheet->setCellValue('A' . $row, 'Periode:');
        $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($data['start_date'])) . ' - ' . date('d/m/Y', strtotime($data['end_date'])));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Tipe Laporan:');
        $sheet->setCellValue('B' . $row, ucfirst($data['report_type']));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Dibuat oleh:');
        $sheet->setCellValue('B' . $row, $data['generated_by']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Tanggal:');
        $sheet->setCellValue('B' . $row, $data['generated_at']->format('d/m/Y H:i'));
        $row++;

        if (!empty($data['additional_info'])) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Catatan:');
            $sheet->setCellValue('B' . $row, $data['additional_info']);
            $sheet->mergeCells('B' . $row . ':L' . $row);
            $row++;
        }

        $row++;
        $sheet->setCellValue('A' . $row, 'RINGKASAN');
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $row++;

        $summaryData = [
            ['Total Rencana Produksi', $data['summary']['total_plans']],
            ['Total Target Produksi', number_format($data['summary']['total_target'])],
            ['Total Diproduksi', number_format($data['summary']['total_produced'])],
            ['Total Lulus QC', number_format($data['summary']['total_qc_passed'])],
            ['Total Reject QC', number_format($data['summary']['total_qc_failed'])],
            ['Pass Rate', $data['summary']['pass_rate'] . '%'],
            ['Status Selesai', $data['summary']['completed_count']],
            ['Status Dalam Proses', $data['summary']['in_progress_count']],
        ];

        foreach ($summaryData as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $row++;
        }

        $row++;
        $row++;
        $sheet->setCellValue('A' . $row, 'DETAIL PRODUKSI');
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $row++;

        $headers = ['No', 'ID', 'Produk', 'SKU', 'Target', 'Diproduksi', 'QC Pass', 'QC Reject', 'Pass Rate', 'Status', 'Prioritas', 'Tanggal Target'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBDEFB');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }
        $row++;

        $no = 1;
        foreach ($data['productions'] as $production) {
            $qc = $production->quality_control ?? null;
            $passRate = $qc && ($qc->passed_quantity + $qc->failed_quantity) > 0
                ? round(($qc->passed_quantity / ($qc->passed_quantity + $qc->failed_quantity)) * 100, 2)
                : 0;

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $production->id);
            $sheet->setCellValue('C' . $row, $production->product->name ?? '-');
            $sheet->setCellValue('D' . $row, $production->product->sku ?? '-');
            $sheet->setCellValue('E' . $row, $production->quantity);
            $sheet->setCellValue('F' . $row, $production->produced_quantity);
            $sheet->setCellValue('G' . $row, $qc->passed_quantity ?? 0);
            $sheet->setCellValue('H' . $row, $qc->failed_quantity ?? 0);
            $sheet->setCellValue('I' . $row, $passRate . '%');
            $sheet->setCellValue('J' . $row, $this->getStatusLabel($production->status));
            $sheet->setCellValue('K' . $row, $this->getPriorityLabel($production->priority));
            $sheet->setCellValue('L' . $row, date('d/m/Y', strtotime($production->target_date)));

            foreach (['A', 'E', 'F', 'G', 'H', 'I'] as $col) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        $lastRow = $row - 1;
        $sheet->getStyle('A' . ($row - count($data['productions']) - 1) . ':L' . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $this->generateFilename($data, 'xlsx');
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Generate filename for report
     */
    private function generateFilename($data, $extension)
    {
        $type = ucfirst($data['report_type']);
        $date = date('Ymd', strtotime($data['start_date']));
        return "Laporan-Produksi-{$type}-{$date}.{$extension}";
    }

    /**
     * Get status label in Indonesian
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'in_progress' => 'Dalam Proses',
            'completed' => 'Selesai'
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Get priority label in Indonesian
     */
    private function getPriorityLabel($priority)
    {
        $labels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak'
        ];
        return $labels[$priority] ?? $priority;
    }
}