<?php

namespace App\Http\Controllers;

use App\Models\QualityControl;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QualityControlController extends Controller
{
    /**
     * Display a listing of quality control records
     */
    public function index(Request $request)
    {
        $query = QualityControl::query()
            ->with(['product', 'productionPlan', 'inspector']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('inspection_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('inspection_date', '<=', $request->end_date);
        }

        $qualityControls = $query->orderBy('inspection_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($qualityControls);
    }

    /**
     * Get completed production plans ready for QC
     */
    public function getCompletedProductions(Request $request)
    {
        $completedPlans = ProductionPlan::query()
            ->with(['product'])
            ->where('status', 'completed')
            ->whereDoesntHave('qualityControl')
            ->orderBy('completed_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($completedPlans);
    }

    /**
     * Store a newly created quality control record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_plan_id' => 'required|exists:production_plans,id',
            'notes' => 'nullable|string',
        ]);

        $productionPlan = ProductionPlan::with('product')->findOrFail($validated['production_plan_id']);

        if ($productionPlan->status !== 'completed') {
            Log::warning('QC gagal dibuat: produksi belum selesai', [
                'production_plan_id' => $productionPlan->id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Hanya produksi yang sudah selesai yang bisa diinspeksi'
            ], 403);
        }

        $existingQC = QualityControl::where('production_plan_id', $validated['production_plan_id'])->first();
        if ($existingQC) {
            Log::warning('QC gagal dibuat: sudah ada QC untuk production plan', [
                'production_plan_id' => $productionPlan->id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Quality control sudah dibuat untuk rencana produksi ini'
            ], 409);
        }

        $qualityControl = QualityControl::create([
            'production_plan_id' => $validated['production_plan_id'],
            'product_id' => $productionPlan->product_id,
            'total_quantity' => $productionPlan->produced_quantity,
            'passed_quantity' => 0,
            'failed_quantity' => 0,
            'inspector_id' => Auth::id(),
            'inspection_date' => now(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        $qualityControl->load(['product', 'productionPlan', 'inspector']);

        return response()->json([
            'message' => 'Quality control record berhasil dibuat',
            'data' => $qualityControl
        ], 201);
    }

    /**
     * Display the specified quality control record
     */
    public function show($id)
    {
        $qualityControl = QualityControl::with(['product', 'productionPlan', 'inspector'])
            ->findOrFail($id);

        return response()->json($qualityControl);
    }

    /**
     * Start quality control inspection
     */
    public function startInspection($id)
    {
        $qualityControl = QualityControl::findOrFail($id);

        if ($qualityControl->status !== 'pending') {
            Log::warning('QC gagal dimulai: status bukan pending', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Hanya QC dengan status pending yang bisa dimulai'
            ], 403);
        }

        $qualityControl->update([
            'status' => 'in_progress',
            'inspector_id' => Auth::id(),
        ]);

        $qualityControl->load(['product', 'productionPlan', 'inspector']);

        return response()->json([
            'message' => 'Inspeksi quality control dimulai',
            'data' => $qualityControl
        ]);
    }

    /**
     * Update quality control inspection results
     */
    public function updateInspection(Request $request, $id)
    {
        $validated = $request->validate([
            'passed_quantity' => 'required|integer|min:0',
            'failed_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $qualityControl = QualityControl::findOrFail($id);

        if ($qualityControl->status === 'completed') {
            Log::warning('QC gagal diupdate: sudah selesai', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Inspeksi yang sudah selesai tidak bisa diupdate'
            ], 403);
        }

        $totalInspected = $validated['passed_quantity'] + $validated['failed_quantity'];
        if ($totalInspected > $qualityControl->total_quantity) {
            Log::warning('QC gagal diupdate: total inspeksi melebihi produksi', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
                'total_quantity' => $qualityControl->total_quantity,
                'inspected' => $totalInspected,
            ]);
            return response()->json([
                'message' => 'Total inspeksi tidak boleh melebihi jumlah produksi'
            ], 422);
        }

        $updateData = [
            'passed_quantity' => $validated['passed_quantity'],
            'failed_quantity' => $validated['failed_quantity'],
        ];

        if (isset($validated['notes'])) {
            $updateData['notes'] = $validated['notes'];
        }

        if ($totalInspected === $qualityControl->total_quantity) {
            $updateData['status'] = 'completed';
        } else {
            $updateData['status'] = 'in_progress';
        }

        $qualityControl->update($updateData);

        $qualityControl->load(['product', 'productionPlan', 'inspector']);

        return response()->json([
            'message' => 'Hasil inspeksi berhasil diperbarui',
            'data' => $qualityControl
        ]);
    }

    /**
     * Complete quality control inspection
     */
    public function completeInspection(Request $request, $id)
    {
        $validated = $request->validate([
            'passed_quantity' => 'required|integer|min:0',
            'failed_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $qualityControl = QualityControl::findOrFail($id);

        if ($qualityControl->status === 'completed') {
            Log::warning('QC gagal diselesaikan: sudah selesai', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Inspeksi sudah selesai'
            ], 403);
        }

        $totalInspected = $validated['passed_quantity'] + $validated['failed_quantity'];
        if ($totalInspected !== $qualityControl->total_quantity) {
            Log::warning('QC gagal diselesaikan: total inspeksi tidak sama dengan produksi', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
                'total_quantity' => $qualityControl->total_quantity,
                'inspected' => $totalInspected,
            ]);
            return response()->json([
                'message' => 'Total inspeksi harus sama dengan jumlah produksi',
                'total_quantity' => $qualityControl->total_quantity,
                'inspected' => $totalInspected
            ], 422);
        }

        $qualityControl->update([
            'passed_quantity' => $validated['passed_quantity'],
            'failed_quantity' => $validated['failed_quantity'],
            'notes' => $validated['notes'] ?? $qualityControl->notes,
            'status' => 'completed',
            'inspection_date' => now(),
        ]);

        $qualityControl->load(['product', 'productionPlan', 'inspector']);

        return response()->json([
            'message' => 'Inspeksi quality control selesai',
            'data' => $qualityControl
        ]);
    }

    /**
     * Delete quality control record (only if not completed)
     */
    public function destroy($id)
    {
        $qualityControl = QualityControl::findOrFail($id);

        if ($qualityControl->status === 'completed') {
            Log::warning('QC gagal dihapus: sudah selesai', [
                'qc_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'QC yang sudah selesai tidak bisa dihapus'
            ], 403);
        }

        $qualityControl->delete();

        return response()->json([
            'message' => 'Quality control record berhasil dihapus'
        ]);
    }

    /**
     * Get quality control statistics
     */
    public function getStatistics(Request $request)
    {
        $query = QualityControl::query();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('inspection_date', [$request->start_date, $request->end_date]);
        }

        $stats = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'total_inspected' => (clone $query)->where('status', 'completed')->sum('total_quantity'),
            'total_passed' => (clone $query)->where('status', 'completed')->sum('passed_quantity'),
            'total_failed' => (clone $query)->where('status', 'completed')->sum('failed_quantity'),
        ];

        if ($stats['total_inspected'] > 0) {
            $stats['pass_rate'] = round(($stats['total_passed'] / $stats['total_inspected']) * 100, 2);
            $stats['fail_rate'] = round(($stats['total_failed'] / $stats['total_inspected']) * 100, 2);
        } else {
            $stats['pass_rate'] = 0;
            $stats['fail_rate'] = 0;
        }

        return response()->json($stats);
    }

    /**
     * Get quality control report by product
     */
    public function getProductReport(Request $request)
    {
        $query = QualityControl::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) as inspection_count')
            ->selectRaw('SUM(total_quantity) as total_quantity')
            ->selectRaw('SUM(passed_quantity) as total_passed')
            ->selectRaw('SUM(failed_quantity) as total_failed')
            ->selectRaw('ROUND((SUM(passed_quantity) / SUM(total_quantity)) * 100, 2) as pass_rate')
            ->with('product')
            ->where('status', 'completed')
            ->groupBy('product_id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('inspection_date', [$request->start_date, $request->end_date]);
        }

        $report = $query->orderBy('inspection_count', 'desc')->get();

        return response()->json($report);
    }
}
