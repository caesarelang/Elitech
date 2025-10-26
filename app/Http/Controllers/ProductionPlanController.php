<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\ProductionReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductionPlanExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionPlanController extends Controller
{
    /**
     * Display a listing of production plans
     */
    public function index(Request $request)
    {
        $query = ProductionPlan::query()
            ->with(['product', 'createdBy', 'approvedBy']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('start_date')) {
            $query->whereDate('target_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('target_date', '<=', $request->end_date);
        }
        
        $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
              ->orderBy('target_date', 'asc');
        
        $plans = $query->paginate($request->get('per_page', 15));
        
        return response()->json($plans);
    }

    /**
     * Get approved plans for staff produksi
     */
    public function getApprovedPlans(Request $request)
    {
        $query = ProductionPlan::query()
            ->with(['product', 'createdBy', 'approvedBy'])
            ->whereIn('status', ['approved', 'in_progress', 'completed'])
            ->orderByRaw("FIELD(status, 'approved', 'in_progress', 'completed')")
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('deadline', 'asc');
        
        $plans = $query->paginate($request->get('per_page', 15));
        
        return response()->json($plans);
    }

    /**
     * Store a newly created production plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'target_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);
        
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'draft';
        
        $plan = ProductionPlan::create($validated);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Production plan created successfully',
            'data' => $plan
        ], 201);
    }

    /**
     * Display the specified production plan
     */
    public function show($id)
    {
        $plan = ProductionPlan::with(['product', 'createdBy', 'approvedBy'])
            ->findOrFail($id);
        
        return response()->json($plan);
    }

    /**
     * Update the specified production plan
     */
    public function update(Request $request, $id)
    {
        $plan = ProductionPlan::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string',
            'approved_by' => 'nullable|integer|exists:users,id',
            'approved_at' => 'nullable|date',
            'approval_notes' => 'nullable|string',
        ]);

        if ($data['status'] === 'approved') {
            $data['approved_at'] = $data['approved_at'] ?? now();
            $data['deadline'] = now()->addDays(7);
        }

        $plan->update($data);

        return response()->json([
            'message' => 'Rencana produksi berhasil diperbarui',
            'data' => $plan->load('product', 'createdBy', 'approvedBy'),
        ]);
    }

    /**
     * Remove the specified production plan
     */
    public function destroy($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft plans can be deleted'
            ], 403);
        }
        
        $plan->delete();
        
        return response()->json([
            'message' => 'Production plan deleted successfully'
        ]);
    }

    /**
     * Submit production plan for approval
     */
    public function submitForApproval($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft plans can be submitted'
            ], 403);
        }
        
        $plan->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Production plan submitted for approval',
            'data' => $plan
        ]);
    }

    /**
     * Process approval (approve/reject)
     */
    public function processApproval(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_notes' => 'nullable|string',
            'deadline' => 'required_if:action,approve|date',
        ]);
        
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'pending_approval') {
            return response()->json([
                'message' => 'Only pending plans can be approved/rejected'
            ], 403);
        }
        
        $updateData = [
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ];
        
        if ($validated['action'] === 'approve') {
            $updateData['status'] = 'approved';
            $updateData['deadline'] = $validated['deadline'];
        } else {
            $updateData['status'] = 'rejected';
        }
        
        $plan->update($updateData);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Production plan ' . $validated['action'] . 'd successfully',
            'data' => $plan
        ]);
    }

    /**
     * Start production - Staff Produksi
     */
    public function startProduction($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved plans can be started'
            ], 403);
        }
        
        $staffName = Auth::user()->name;
        
        $plan->update([
            'status' => 'in_progress',
            'production_started_at' => now(),
            'progress_percentage' => 0,
            'produced_quantity' => 0,
            'progress_notes' => "Production started by: {$staffName}",
        ]);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Production started successfully',
            'data' => $plan
        ]);
    }

    /**
     * Update production progress - Staff Produksi
     */
    public function updateProgress(Request $request, $id)
    {
        $validated = $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100',
            'progress_notes' => 'nullable|string',
        ]);
        
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'in_progress') {
            return response()->json([
                'message' => 'Only in-progress plans can be updated'
            ], 403);
        }
        
        $updateData = [
            'progress_percentage' => $validated['progress_percentage'],
        ];
        
        if (isset($validated['progress_notes'])) {
            $updateData['progress_notes'] = $plan->progress_notes . "\n" . now()->format('Y-m-d H:i:s') . " - " . $validated['progress_notes'];
        }
        
        $plan->update($updateData);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Progress updated successfully',
            'data' => $plan
        ]);
    }

    /**
     * Complete production - Staff Produksi
     */
    public function completeProduction(Request $request, $id)
    {
        $validated = $request->validate([
            'produced_quantity' => 'required|integer|min:1',
            'completion_notes' => 'nullable|string',
        ]);
        
        $plan = ProductionPlan::findOrFail($id);
        
        if ($plan->status !== 'in_progress') {
            return response()->json([
                'message' => 'Only in-progress plans can be completed'
            ], 403);
        }
        
        if ($plan->progress_percentage < 100) {
            return response()->json([
                'message' => 'Progress must be 100% before completing'
            ], 403);
        }
        
        $staffName = Auth::user()->name;
        $completionNote = "Completed by: {$staffName}";
        
        if (isset($validated['completion_notes'])) {
            $completionNote .= " - " . $validated['completion_notes'];
        }
        
        $plan->update([
            'status' => 'completed',
            'completed_at' => now(),
            'produced_quantity' => $validated['produced_quantity'],
            'progress_notes' => $plan->progress_notes . "\n" . now()->format('Y-m-d H:i:s') . " - " . $completionNote,
        ]);
        
        $plan->load(['product', 'createdBy', 'approvedBy']);
        
        return response()->json([
            'message' => 'Production completed successfully',
            'data' => $plan
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $stats = [
            'draft' => ProductionPlan::where('status', 'draft')->count(),
            'pending_approval' => ProductionPlan::where('status', 'pending_approval')->count(),
            'in_progress' => ProductionPlan::where('status', 'in_progress')->count(),
            'completed_today' => ProductionPlan::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Get staff dashboard statistics
     */
    public function getStaffDashboardStats()
    {
        $stats = [
            'approved' => ProductionPlan::where('status', 'approved')->count(),
            'in_progress' => ProductionPlan::where('status', 'in_progress')->count(),
            'completed_today' => ProductionPlan::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'overdue' => ProductionPlan::where('status', 'in_progress')
                ->where('deadline', '<', now())
                ->count(),
        ];
        
        return response()->json($stats);
    }

    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:weekly,monthly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
            'additional_info' => 'nullable|string',
        ]);

        $plans = ProductionPlan::with('product')
            ->whereBetween('target_date', [$validated['start_date'], $validated['end_date']])
            ->orderBy('target_date')
            ->get();

        if ($plans->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data produksi pada rentang tanggal tersebut.'
            ], 404);
        }

        if ($validated['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.production', [
                'plans' => $plans,
                'reportType' => ucfirst($validated['report_type']),
                'startDate' => $validated['start_date'],
                'endDate' => $validated['end_date'],
                'additionalInfo' => $validated['additional_info'] ?? '',
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Laporan-Produksi-' . now()->format('Ymd_His') . '.pdf');
        }

        if ($validated['format'] === 'excel') {
            $filename = 'Laporan-Produksi-' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(
                new ProductionReportExport($plans, $validated),
                $filename
            );
        }

        return response()->json(['message' => 'Format laporan tidak valid'], 400);
    }

    public function getReports(Request $request)
    {
        return response()->json([
            'data' => []
        ]);
    }

    public function getReportDetail($id)
    {
        return response()->json([
            'data' => []
        ]);
    }

    public function exportReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
        ]);

        $plans = ProductionPlan::with('product')
            ->whereBetween('target_date', [$validated['start_date'], $validated['end_date']])
            ->orderBy('target_date', 'asc')
            ->get();

        if ($plans->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data dalam rentang tanggal tersebut'], 404);
        }

        if ($validated['format'] === 'excel') {
            return Excel::download(new ProductionPlanExport($plans), 'Laporan-Produksi.xlsx');
        }

        $pdf = PDF::loadView('pdf.production_report', [
            'plans' => $plans,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan-Produksi.pdf');
    }
}