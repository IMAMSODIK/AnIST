<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Initiative;
use App\Models\Measurement;
use App\Models\Upload;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen(trim($query)) < 2) {
            return response()->json([]);
        }

        $results = [];

        // Search measurements
        $measurements = Measurement::where('measurement', 'like', "%{$query}%")
            ->orWhere('objective', 'like', "%{$query}%")
            ->orWhere('perspective', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($measurements as $m) {
            $results[] = [
                'type' => 'Measurement',
                'title' => $m->measurement,
                'subtitle' => $m->perspective . ' — ' . $m->objective,
                'url' => route('measurements.show', $m),
                'color' => 'indigo',
            ];
        }

        // Search initiatives
        $initiatives = Initiative::where('initiative', 'like', "%{$query}%")
            ->with('measurement')
            ->limit(5)
            ->get();

        foreach ($initiatives as $i) {
            $results[] = [
                'type' => 'Initiative',
                'title' => $i->initiative,
                'subtitle' => $i->measurement?->measurement ?? 'N/A',
                'url' => route('initiatives.index'),
                'color' => 'amber',
            ];
        }

        // Search uploads
        $uploads = Upload::where('file_name', 'like', "%{$query}%")
            ->with('measurement')
            ->limit(5)
            ->get();

        foreach ($uploads as $u) {
            $results[] = [
                'type' => 'Evidence',
                'title' => $u->file_name,
                'subtitle' => $u->measurement?->measurement ?? 'N/A' . ' — ' . ucfirst($u->status),
                'url' => route('uploads.show', $u),
                'color' => 'emerald',
            ];
        }

        return response()->json(array_slice($results, 0, 10));
    }
}
