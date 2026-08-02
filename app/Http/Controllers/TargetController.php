<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTargetRequest;
use App\Http\Requests\UpdateTargetRequest;
use App\Models\AuditTrail;
use App\Models\Measurement;
use App\Models\Target;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function index(Request $request)
    {
        $query = Target::with('measurement');

        if ($request->filled('measurement_id')) {
            $query->where('measurement_id', $request->measurement_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }

        $targets = $query->orderBy('year', 'desc')->orderBy('quarter')->paginate(15);
        $measurements = Measurement::orderBy('measurement')->get();

        return view('targets.index', compact('targets', 'measurements'));
    }

    public function create()
    {
        $measurements = Measurement::orderBy('measurement')->get();
        return view('targets.create', compact('measurements'));
    }

    public function store(StoreTargetRequest $request)
    {
        $target = Target::create($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create_target',
            'model_type' => Target::class,
            'model_id' => $target->id,
            'new_values' => $target->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('targets.index')
            ->with('success', 'Target created successfully.');
    }

    public function edit(Target $target)
    {
        $measurements = Measurement::orderBy('measurement')->get();
        return view('targets.edit', compact('target', 'measurements'));
    }

    public function update(UpdateTargetRequest $request, Target $target)
    {
        $oldValues = $target->toArray();
        $target->update($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update_target',
            'model_type' => Target::class,
            'model_id' => $target->id,
            'old_values' => $oldValues,
            'new_values' => $target->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('targets.index')
            ->with('success', 'Target updated successfully.');
    }

    public function destroy(Target $target)
    {
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete_target',
            'model_type' => Target::class,
            'model_id' => $target->id,
            'old_values' => $target->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $target->delete();

        return redirect()->route('targets.index')
            ->with('success', 'Target deleted successfully.');
    }
}
