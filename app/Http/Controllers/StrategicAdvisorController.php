<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStrategicAdvisorRequest;
use App\Models\StrategicRecommendation;
use App\Services\StrategicAdvisorService;
use Illuminate\Http\Request;

class StrategicAdvisorController extends Controller
{
    public function __construct(
        protected StrategicAdvisorService $service,
    ) {}

    /**
     * Upload form + 5 most recent recommendations (for "Recent Activity").
     */
    public function index()
    {
        $recent = StrategicRecommendation::with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('strategic-advisor.index', compact('recent'));
    }

    /**
     * Handle upload → extraction → grounded Gemini call (synchronous) →
     * redirect to detail page so the user sees the result immediately.
     */
    public function store(StoreStrategicAdvisorRequest $request)
    {
        // Synchronous: ekstraksi + Gemini grounding bisa makan 60-120s.
        set_time_limit(300);

        $record = $this->service->process(
            $request->file('file'),
            $request->user()->id,
        );

        return redirect()
            ->route('strategic-advisor.show', $record)
            ->with('success', $record->status === 'completed'
                ? 'Strategic Advisor selesai menganalisis dokumen.'
                : 'Dokumen terproses, namun AI melaporkan masalah — lihat detail.');
    }

    /**
     * JSON endpoint untuk upload multi-file via AJAX. Menerima satu file
     * per-request (frontend meng-upload file satu-per-satu secara berurutan
     * agar progress per-file bisa diperbarui di modal). Mengembalikan record
     * yang baru diproses beserta URL detail-nya supaya frontend bisa langsung
     * menampilkan link "Lihat hasil".
     */
    public function uploadAjax(StoreStrategicAdvisorRequest $request): \Illuminate\Http\JsonResponse
    {
        // Ekstraksi PDF (~20s) + grounded Gemini call (~40s) seringkali
        // melebihi default PHP max_execution_time=60s. Naikkan limit untuk
        // request ini saja agar tidak timeout di tengah jalan.
        set_time_limit(300);

        $record = $this->service->process(
            $request->file('file'),
            $request->user()->id,
        );

        return response()->json([
            'success'           => $record->status === 'completed',
            'status'             => $record->status,
            'recommendation_id'  => $record->id,
            'source_file'        => $record->source_file,
            'document_type'      => $record->document_type,
            'error_message'      => $record->error_message,
            'show_url'           => route('strategic-advisor.show', $record),
            'processing_time'    => $record->processing_time !== null
                                        ? (float) $record->processing_time
                                        : null,
        ], 200);
    }

    /**
     * Full history with simple status filter.
     */
    public function history(Request $request)
    {
        $query = StrategicRecommendation::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->paginate(15)->withQueryString();

        return view('strategic-advisor.history', compact('records'));
    }

    public function show(StrategicRecommendation $strategicRecommendation)
    {
        $strategicRecommendation->load('user');
        return view('strategic-advisor.show', ['recommendation' => $strategicRecommendation]);
    }

    public function destroy(StrategicRecommendation $strategicRecommendation)
    {
        $this->service->delete($strategicRecommendation);

        return redirect()
            ->route('strategic-advisor.history')
            ->with('success', 'Catatan Strategic Advisor dihapus.');
    }
}