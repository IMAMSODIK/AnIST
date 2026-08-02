<?php

namespace App\Http\Controllers;

use App\Models\AiResult;
use App\Models\Upload;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $query = AiResult::with(['upload.measurement', 'upload.user']);

        if ($request->filled('evidence_valid')) {
            $query->where('evidence_valid', $request->boolean('evidence_valid'));
        }

        if ($request->filled('confidence_min')) {
            $query->where('confidence', '>=', $request->confidence_min);
        }

        $analyses = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('ai-analysis.index', compact('analyses'));
    }

    public function show(AiResult $aiResult)
    {
        $aiResult->load(['upload.measurement', 'upload.user']);
        return view('ai-analysis.show', compact('aiResult'));
    }
}
