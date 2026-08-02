<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadEvidenceRequest;
use App\Jobs\BatchProcessEvidenceJob;
use App\Jobs\ProcessEvidenceJob;
use App\Models\Measurement;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(
        protected UploadService $uploadService,
    ) {}

    public function index(Request $request)
    {
        $query = Upload::with(['measurement', 'user', 'aiResult']);

        if ($request->filled('measurement_id')) {
            $query->where('measurement_id', $request->measurement_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $uploads = $query->orderBy('created_at', 'desc')->paginate(15);
        $measurements = Measurement::orderBy('measurement')->get();

        return view('uploads.index', compact('uploads', 'measurements'));
    }

    public function create()
    {
        $measurements = Measurement::orderBy('perspective')->orderBy('measurement')->get();
        return view('uploads.create', compact('measurements'));
    }

    public function store(UploadEvidenceRequest $request)
    {
        try {
            $upload = $this->uploadService->handleUpload(
                $request->file('file'),
                $request->measurement_id,
                $request->quarter,
                $request->year,
            );

            // Dispatch AI processing job
            ProcessEvidenceJob::dispatch($upload)->onQueue('evidence');

            return redirect()->route('uploads.index')
                ->with('success', 'Evidence uploaded successfully. AI analysis is being processed.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }
    }

    public function show(Upload $upload)
    {
        $upload->load(['measurement', 'user', 'aiResult']);
        return view('uploads.show', compact('upload'));
    }

    public function destroy(Upload $upload)
    {
        $this->uploadService->deleteUpload($upload);

        return redirect()->route('uploads.index')
            ->with('success', 'Evidence deleted successfully.');
    }

    public function retry(Upload $upload)
    {
        $upload->update(['status' => 'pending']);
        ProcessEvidenceJob::dispatch($upload)->onQueue('evidence');

        return redirect()->route('uploads.show', $upload)
            ->with('success', 'AI analysis has been re-queued.');
    }

    public function batchProcess(Request $request)
    {
        $pendingCount = Upload::where('status', 'pending')->count();

        if ($pendingCount === 0) {
            return back()->with('info', 'No pending evidence to process.');
        }

        BatchProcessEvidenceJob::dispatch();

        return back()->with('success', "Batch processing started for {$pendingCount} pending evidence(s).");
    }
}
