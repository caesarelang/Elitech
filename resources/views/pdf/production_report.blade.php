<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Produksi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Laporan Produksi ({{ $start_date }} - {{ $end_date }})</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Tanggal Target</th>
                <th>Prioritas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plans as $plan)
            <tr>
                <td>{{ $plan->id }}</td>
                <td>{{ $plan->product->name ?? '-' }}</td>
                <td>{{ $plan->quantity }}</td>
                <td>{{ $plan->target_date }}</td>
                <td>{{ ucfirst($plan->priority) }}</td>
                <td>{{ ucfirst($plan->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
