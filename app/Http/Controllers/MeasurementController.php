<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Models\AuditTrail;
use App\Models\Measurement;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasurementController extends Controller
{
    public function index(Request $request)
    {
        $query = Measurement::withCount(['targets', 'initiatives', 'uploads']);

        if ($request->filled('perspective')) {
            $query->where('perspective', $request->perspective);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('measurement', 'like', "%{$request->search}%")
                  ->orWhere('objective', 'like', "%{$request->search}%");
            });
        }

        $measurements = $query->orderBy('perspective')->orderBy('objective')->paginate(15);

        return view('measurements.index', compact('measurements'));
    }

    public function create()
    {
        return view('measurements.create');
    }

    public function store(StoreMeasurementRequest $request)
    {
        $validated = $request->validated();

        // Pull targets aside — they belong to the Target model, not Measurement.
        $targets = $validated['targets'] ?? [];
        $year = $validated['target_year'] ?? null;
        unset($validated['targets'], $validated['target_year']);

        $measurement = DB::transaction(function () use ($validated, $targets, $year) {
            $measurement = Measurement::create($validated);
            $this->syncTargets($measurement, $targets, $year);

            return $measurement;
        });

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create_measurement',
            'model_type' => Measurement::class,
            'model_id' => $measurement->id,
            'new_values' => $measurement->fresh(['targets'])->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('measurements.index')
            ->with('success', 'Measurement created successfully.');
    }

    public function show(Measurement $measurement)
    {
        $measurement->load(['targets', 'initiatives', 'uploads.aiResult', 'realisasis', 'scores']);

        return view('measurements.show', compact('measurement'));
    }

    public function edit(Measurement $measurement)
    {
        $measurement->load('targets');

        return view('measurements.edit', compact('measurement'));
    }

    public function update(UpdateMeasurementRequest $request, Measurement $measurement)
    {
        $validated = $request->validated();

        // Pull targets aside — they belong to the Target model, not Measurement.
        $targets = $validated['targets'] ?? [];
        $year = $validated['target_year'] ?? null;
        unset($validated['targets'], $validated['target_year']);

        $oldValues = $measurement->fresh(['targets'])->toArray();

        $newValues = DB::transaction(function () use ($validated, $measurement, $targets, $year) {
            $measurement->update($validated);
            $this->syncTargets($measurement, $targets, $year);

            return $measurement->fresh(['targets'])->toArray();
        });

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update_measurement',
            'model_type' => Measurement::class,
            'model_id' => $measurement->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('measurements.index')
            ->with('success', 'Measurement updated successfully.');
    }

    public function destroy(Measurement $measurement)
    {
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete_measurement',
            'model_type' => Measurement::class,
            'model_id' => $measurement->id,
            'old_values' => $measurement->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $measurement->delete();

        return redirect()->route('measurements.index')
            ->with('success', 'Measurement deleted successfully.');
    }

    /**
     * Persist the inline Q1–Q4 targets for a measurement.
     *
     * Quarters that are left blank on the form are skipped entirely (no row
     * is created and any existing row for that quarter/year is kept). Filled
     * quarters are stored via updateOrCreate so re-submitting the edit form
     * updates the existing value instead of triggering the composite unique
     * constraint (measurement_id, year, quarter).
     *
     * If no year was provided, nothing happens.
     */
    private function syncTargets(Measurement $measurement, array $targets, ?int $year): void
    {
        if (blank($year)) {
            return;
        }

        $quarterMap = [
            'q1' => 'Q1',
            'q2' => 'Q2',
            'q3' => 'Q3',
            'q4' => 'Q4',
        ];

        foreach ($quarterMap as $key => $quarter) {
            if (!array_key_exists($key, $targets) || blank($targets[$key])) {
                continue;
            }

            Target::updateOrCreate(
                [
                    'measurement_id' => $measurement->id,
                    'year' => $year,
                    'quarter' => $quarter,
                ],
                [
                    'target' => $targets[$key],
                ]
            );
        }
    }
}
