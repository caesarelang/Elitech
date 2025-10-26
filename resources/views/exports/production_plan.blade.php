<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Tanggal Target</th>
            <th>Prioritas</th>
            <th>Status</th>
            <th>Catatan</th>
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
            <td>{{ $plan->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
