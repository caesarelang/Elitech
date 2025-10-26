<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produksi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            font-size: 24pt;
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            font-size: 11pt;
            color: #64748b;
        }
        
        .report-info {
            background: #f8fafc;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }
        
        .report-info table {
            width: 100%;
        }
        
        .report-info td {
            padding: 5px 0;
        }
        
        .report-info td:first-child {
            font-weight: bold;
            width: 150px;
            color: #475569;
        }
        
        .summary {
            background: #eff6ff;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
            border: 1px solid #bfdbfe;
        }
        
        .summary h2 {
            font-size: 14pt;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .summary-item {
            background: white;
            padding: 12px;
            border-radius: 5px;
            border-left: 3px solid #3b82f6;
        }
        
        .summary-item .label {
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 16pt;
            font-weight: bold;
            color: #1e293b;
        }
        
        .summary-item.success .value {
            color: #15803d;
        }
        
        .summary-item.danger .value {
            color: #dc2626;
        }
        
        .summary-item.info .value {
            color: #2563eb;
        }
        
        .detail-section {
            margin-top: 30px;
        }
        
        .detail-section h2 {
            font-size: 14pt;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table.data-table th {
            background: #1e40af;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #1e3a8a;
        }
        
        table.data-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        
        table.data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        table.data-table tr:hover {
            background: #f1f5f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-in-progress {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .priority-urgent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .priority-high {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .priority-medium {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .priority-low {
            background: #f1f5f9;
            color: #475569;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9pt;
            color: #64748b;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PRODUKSI</h1>
        <div class="subtitle">Sistem Manajemen Produksi</div>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td>Periode Laporan</td>
                <td>: {{ date('d/m/Y', strtotime($start_date)) }} - {{ date('d/m/Y', strtotime($end_date)) }}</td>
            </tr>
            <tr>
                <td>Tipe Laporan</td>
                <td>: {{ ucfirst($report_type) }}</td>
            </tr>
            <tr>
                <td>Dibuat oleh</td>
                <td>: {{ $generated_by }}</td>
            </tr>
            <tr>
                <td>Tanggal Pembuatan</td>
                <td>: {{ $generated_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            @if(!empty($additional_info))
            <tr>
                <td>Catatan Tambahan</td>
                <td>: {{ $additional_info }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="summary">
        <h2>RINGKASAN</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Rencana Produksi</div>
                <div class="value">{{ $summary['total_plans'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Target Produksi</div>
                <div class="value">{{ number_format($summary['total_target']) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Diproduksi</div>
                <div class="value">{{ number_format($summary['total_produced']) }}</div>
            </div>
            <div class="summary-item success">
                <div class="label">Total Lulus QC</div>
                <div class="value">{{ number_format($summary['total_qc_passed']) }}</div>
            </div>
            <div class="summary-item danger">
                <div class="label">Total Reject QC</div>
                <div class="value">{{ number_format($summary['total_qc_failed']) }}</div>
            </div>
            <div class="summary-item info">
                <div class="label">Pass Rate</div>
                <div class="value">{{ $summary['pass_rate'] }}%</div>
            </div>
            <div class="summary-item">
                <div class="label">Status Selesai</div>
                <div class="value">{{ $summary['completed_count'] }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Dalam Proses</div>
                <div class="value">{{ $summary['in_progress_count'] }}</div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2>DETAIL PRODUKSI</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 20%;">Produk</th>
                    <th style="width: 10%;">SKU</th>
                    <th style="width: 8%;" class="text-right">Target</th>
                    <th style="width: 8%;" class="text-right">Produksi</th>
                    <th style="width: 8%;" class="text-right">QC Pass</th>
                    <th style="width: 8%;" class="text-right">QC Reject</th>
                    <th style="width: 8%;" class="text-center">Pass Rate</th>
                    <th style="width: 10%;" class="text-center">Status</th>
                    <th style="width: 10%;" class="text-center">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productions as $index => $production)
                @php
                    $qc = $production->quality_control ?? null;
                    $passRate = $qc && ($qc->passed_quantity + $qc->failed_quantity) > 0
                        ? round(($qc->passed_quantity / ($qc->passed_quantity + $qc->failed_quantity)) * 100, 2)
                        : 0;
                    
                    $statusClass = [
                        'completed' => 'status-completed',
                        'in_progress' => 'status-in-progress',
                        'pending_approval' => 'status-pending',
                        'rejected' => 'status-rejected'
                    ][$production->status] ?? '';
                    
                    $statusLabel = [
                        'draft' => 'Draft',
                        'pending_approval' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'in_progress' => 'Proses',
                        'completed' => 'Selesai'
                    ][$production->status] ?? $production->status;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">#{{ $production->id }}</td>
                    <td>{{ $production->product->name ?? '-' }}</td>
                    <td>{{ $production->product->sku ?? '-' }}</td>
                    <td class="text-right">{{ number_format($production->quantity) }}</td>
                    <td class="text-right">{{ number_format($production->produced_quantity) }}</td>
                    <td class="text-right" style="color: #15803d; font-weight: bold;">
                        {{ number_format($qc->passed_quantity ?? 0) }}
                    </td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">
                        {{ number_format($qc->failed_quantity ?? 0) }}
                    </td>
                    <td class="text-center">{{ $passRate }}%</td>
                    <td class="text-center">
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="text-center">{{ date('d/m/Y', strtotime($production->target_date)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Manajemen Produksi</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>