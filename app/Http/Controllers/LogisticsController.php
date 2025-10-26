<?php

namespace App\Http\Controllers;

use App\Models\Logistics;
use App\Models\Product;
use App\Models\QualityControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LogisticsController extends Controller
{
    /**
     * Display a listing of logistics/warehouse stock.
     */
    public function index(Request $request)
    {
        $query = Logistics::with(['product', 'updater']);

        if ($request->filled('warehouse')) {
            $query->byWarehouse($request->warehouse);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->outOfStock();
                    break;
                case 'in_stock':
                    $query->inStock();
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $logistics = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $logistics->items(),
                'pagination' => [
                    'total' => $logistics->total(),
                    'per_page' => $logistics->perPage(),
                    'current_page' => $logistics->currentPage(),
                    'last_page' => $logistics->lastPage(),
                ]
            ]);
        }

        return view('logistics.index', compact('logistics'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'quality_control_id' => 'required|exists:quality_control,id',
            'warehouse_name' => 'required|string|max:255',
            'warehouse_location' => 'nullable|string|max:500',
            'minimum_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $qc = QualityControl::with('product')->findOrFail($validated['quality_control_id']);

            if ($qc->status !== 'completed') {
                $message = 'Hanya Quality Control dengan status "Selesai" yang dapat ditambahkan. Status saat ini: ' . $qc->status_label;
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            if ($qc->synced_to_logistics) {
                $message = 'Quality Control ini sudah pernah disinkronkan ke gudang pada ' . $qc->synced_at->format('d/m/Y H:i');
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            if ($qc->passed_quantity <= 0) {
                $message = 'Tidak ada produk yang lulus inspeksi (passed_quantity = 0)';
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            $existingLogistics = Logistics::where('product_id', $qc->product_id)
                ->where('warehouse_name', $validated['warehouse_name'])
                ->first();

            if ($existingLogistics) {
                $existingLogistics->stock += $qc->passed_quantity;
                $existingLogistics->last_updated_by = Auth::id();
                $existingLogistics->notes = "Stock ditambahkan dari QC #{$qc->id} - Ditambahkan {$qc->passed_quantity} unit (Total: {$existingLogistics->stock})";
                $existingLogistics->save();
                $existingLogistics->load(['product', 'updater']);

                $qc->synced_to_logistics = true;
                $qc->synced_at = now();
                $qc->save();

                DB::commit();

                $message = "Stock berhasil ditambahkan ke gudang yang sudah ada. Ditambahkan {$qc->passed_quantity} unit.";

                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => $existingLogistics
                    ], 200);
                }

                return redirect()->route('logistics.index')->with('success', $message);
            }

            $logistics = Logistics::create([
                'product_id' => $qc->product_id,
                'warehouse_name' => $validated['warehouse_name'],
                'warehouse_location' => $validated['warehouse_location'],
                'stock' => $qc->passed_quantity,
                'minimum_stock' => $validated['minimum_stock'],
                'last_updated_by' => Auth::id(),
                'notes' => "Stock awal dari QC #{$qc->id} - {$qc->passed_quantity} unit lulus inspeksi",
            ]);

            $qc->synced_to_logistics = true;
            $qc->synced_at = now();
            $qc->save();

            DB::commit();

            $logistics->load(['product', 'updater']);

            $message = "Data gudang berhasil ditambahkan dengan {$qc->passed_quantity} unit dari Quality Control.";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $logistics
                ], 201);
            }

            return redirect()->route('logistics.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified logistics entry.
     */
    public function show(Logistics $logistics)
    {
        $logistics->load(['product', 'updater']);
        
        $qcHistory = QualityControl::where('product_id', $logistics->product_id)
            ->with(['inspector', 'productionPlan'])
            ->orderBy('inspection_date', 'desc')
            ->limit(10)
            ->get();

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $logistics,
                'qc_history' => $qcHistory
            ]);
        }

        return view('logistics.show', compact('logistics', 'qcHistory'));
    }

    /**
     * Update the specified logistics entry in storage.
     */
    public function update(Request $request, Logistics $logistics)
    {
        $validated = $request->validate([
            'warehouse_name' => 'required|string|max:255',
            'warehouse_location' => 'nullable|string|max:500',
            'minimum_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['last_updated_by'] = Auth::id();

        $logistics->update($validated);
        $logistics->load(['product', 'updater']);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Data gudang berhasil diperbarui.',
                'data' => $logistics
            ]);
        }

        return redirect()->route('logistics.index')
            ->with('success', 'Data gudang berhasil diperbarui.');
    }

    /**
     * Remove the specified logistics entry from storage.
     */
    public function destroy(Logistics $logistics)
    {
        $logistics->delete();

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Data gudang berhasil dihapus.'
            ]);
        }

        return redirect()->route('logistics.index')
            ->with('success', 'Data gudang berhasil dihapus.');
    }

    /**
     * Adjust stock (add or reduce).
     */
    public function adjustStock(Request $request, Logistics $logistics)
    {
        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        $oldStock = $logistics->stock;
        $newStock = $logistics->stock + $validated['adjustment'];
        
        if ($newStock < 0) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock tidak boleh negatif. Stock saat ini: ' . $oldStock . ', adjustment: ' . $validated['adjustment']
                ], 422);
            }
            return back()->with('error', 'Stock tidak boleh negatif.');
        }

        $logistics->stock = $newStock;
        $logistics->last_updated_by = Auth::id();
        $logistics->notes = "Stock adjusted: {$oldStock} → {$newStock}. Reason: {$validated['reason']}";
        $logistics->save();
        $logistics->load(['product', 'updater']);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Stock berhasil disesuaikan.',
                'data' => $logistics
            ]);
        }

        return back()->with('success', 'Stock berhasil disesuaikan.');
    }

    /**
     * Sync stock from Quality Control passed_quantity.
     * FIXED: Sudah benar, dengan pengecekan synced_to_logistics
     */
    public function syncFromQualityControl(Request $request)
    {
        $validated = $request->validate([
            'quality_control_id' => 'required|exists:quality_control,id',
            'warehouse_name' => 'required|string|max:255',
            'warehouse_location' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $qc = QualityControl::with('product')->findOrFail($validated['quality_control_id']);

            if ($qc->status !== 'completed') {
                $message = 'Hanya Quality Control dengan status "Selesai" yang dapat disinkronkan. Status saat ini: ' . $qc->status_label;
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            if ($qc->synced_to_logistics) {
                $message = 'Quality Control ini sudah pernah disinkronkan ke gudang pada ' . $qc->synced_at->format('d/m/Y H:i');
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            if ($qc->passed_quantity <= 0) {
                $message = 'Tidak ada produk yang lulus inspeksi untuk disinkronkan.';
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('error', $message);
            }

            $logistics = Logistics::firstOrCreate(
                [
                    'product_id' => $qc->product_id,
                    'warehouse_name' => $validated['warehouse_name'],
                ],
                [
                    'warehouse_location' => $validated['warehouse_location'],
                    'stock' => 0,
                    'minimum_stock' => 10,
                    'last_updated_by' => Auth::id(),
                    'notes' => 'Auto-created from QC sync',
                ]
            );

            $logistics->stock += $qc->passed_quantity;
            $logistics->last_updated_by = Auth::id();
            $logistics->notes = "Stock updated from QC #{$qc->id} - Added {$qc->passed_quantity} units (Inspection: {$qc->inspection_date->format('d/m/Y')})";
            $logistics->save();

            $qc->synced_to_logistics = true;
            $qc->synced_at = now();
            $qc->save();

            DB::commit();
            
            $logistics->load(['product', 'updater']);

            $message = "Stock berhasil disinkronkan. Ditambahkan {$qc->passed_quantity} unit ke gudang.";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $logistics
                ]);
            }

            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    public function bulkSyncFromQualityControl(Request $request)
    {
        $validated = $request->validate([
            'warehouse_name' => 'required|string|max:255',
            'warehouse_location' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        
        try {
            $completedQCs = QualityControl::readyToSync()
                ->with('product')
                ->get();

            if ($completedQCs->isEmpty()) {
                $message = 'Tidak ada Quality Control yang belum disinkronkan.';
                
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                return back()->with('warning', $message);
            }

            $syncedCount = 0;
            $syncedItems = [];

            foreach ($completedQCs as $qc) {
                $logistics = Logistics::firstOrCreate(
                    [
                        'product_id' => $qc->product_id,
                        'warehouse_name' => $validated['warehouse_name'],
                    ],
                    [
                        'warehouse_location' => $validated['warehouse_location'],
                        'stock' => 0,
                        'minimum_stock' => 10,
                        'last_updated_by' => Auth::id(),
                    ]
                );

                $oldStock = $logistics->stock;
                $logistics->stock += $qc->passed_quantity;
                $logistics->last_updated_by = Auth::id();
                $logistics->notes = "Bulk sync from QC #{$qc->id} - Added {$qc->passed_quantity} units (Date: {$qc->inspection_date->format('d/m/Y')})";
                $logistics->save();

                $qc->synced_to_logistics = true;
                $qc->synced_at = now();
                $qc->save();

                $syncedItems[] = [
                    'qc_id' => $qc->id,
                    'product_name' => $qc->product->name,
                    'product_sku' => $qc->product->sku,
                    'added_quantity' => $qc->passed_quantity,
                    'old_stock' => $oldStock,
                    'new_stock' => $logistics->stock,
                    'inspection_date' => $qc->inspection_date->format('d/m/Y'),
                ];

                $syncedCount++;
            }

            DB::commit();

            $message = "Berhasil menyinkronkan {$syncedCount} produk dari Quality Control yang belum disinkronkan.";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'synced_count' => $syncedCount,
                    'synced_items' => $syncedItems
                ]);
            }

            return redirect()->route('logistics.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage();

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Show low stock alert page.
     */
    public function lowStockAlert()
    {
        $lowStockItems = Logistics::with(['product', 'updater'])
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->get();

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $lowStockItems,
                'count' => $lowStockItems->count()
            ]);
        }

        return view('logistics.low-stock-alert', compact('lowStockItems'));
    }

    /**
     * Get warehouse list.
     */
    public function warehouses()
    {
        $warehouses = Logistics::select('warehouse_name', 'warehouse_location')
            ->distinct()
            ->orderBy('warehouse_name')
            ->get();

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json($warehouses);
        }

        return view('logistics.warehouses', compact('warehouses'));
    }

    public function getAvailableQC()
    {
        $availableQCs = QualityControl::readyToSync()
            ->with(['product', 'inspector', 'productionPlan'])
            ->orderBy('inspection_date', 'desc')
            ->get()
            ->map(function($qc) {
                return [
                    'id' => $qc->id,
                    'product_name' => $qc->product->name,
                    'product_sku' => $qc->product->sku,
                    'passed_quantity' => $qc->passed_quantity,
                    'total_quantity' => $qc->total_quantity,
                    'pass_rate' => $qc->pass_rate,
                    'inspection_date' => $qc->inspection_date->format('d/m/Y'),
                    'inspector_name' => $qc->inspector_name,
                    'sync_status' => $qc->sync_status_label,
                ];
            });

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $availableQCs,
                'count' => $availableQCs->count()
            ]);
        }

        return response()->json($availableQCs);
    }
}