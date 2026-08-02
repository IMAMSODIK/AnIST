<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Models\AuditTrail;
use App\Models\Measurement;
use Illuminate\Http\Request;

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
        $measurement = Measurement::create($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create_measurement',
            'model_type' => Measurement::class,
            'model_id' => $measurement->id,
            'new_values' => $measurement->toArray(),
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
        return view('measurements.edit', compact('measurement'));
    }

    public function update(UpdateMeasurementRequest $request, Measurement $measurement)
    {
        $oldValues = $measurement->toArray();
        $measurement->update($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update_measurement',
            'model_type' => Measurement::class,
            'model_id' => $measurement->id,
            'old_values' => $oldValues,
            'new_values' => $measurement->fresh()->toArray(),
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
}
