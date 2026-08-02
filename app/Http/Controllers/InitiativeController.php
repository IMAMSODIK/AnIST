<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInitiativeRequest;
use App\Http\Requests\UpdateInitiativeRequest;
use App\Models\AuditTrail;
use App\Models\Initiative;
use App\Models\Measurement;
use Illuminate\Http\Request;

class InitiativeController extends Controller
{
    public function index(Request $request)
    {
        $query = Initiative::with('measurement');

        if ($request->filled('measurement_id')) {
            $query->where('measurement_id', $request->measurement_id);
        }

        if ($request->filled('search')) {
            $query->where('initiative', 'like', "%{$request->search}%");
        }

        $initiatives = $query->orderBy('measurement_id')->paginate(15);
        $measurements = Measurement::orderBy('measurement')->get();

        return view('initiatives.index', compact('initiatives', 'measurements'));
    }

    public function create()
    {
        $measurements = Measurement::orderBy('measurement')->get();
        return view('initiatives.create', compact('measurements'));
    }

    public function store(StoreInitiativeRequest $request)
    {
        $initiative = Initiative::create($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'create_initiative',
            'model_type' => Initiative::class,
            'model_id' => $initiative->id,
            'new_values' => $initiative->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative created successfully.');
    }

    public function edit(Initiative $initiative)
    {
        $measurements = Measurement::orderBy('measurement')->get();
        return view('initiatives.edit', compact('initiative', 'measurements'));
    }

    public function update(UpdateInitiativeRequest $request, Initiative $initiative)
    {
        $oldValues = $initiative->toArray();
        $initiative->update($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update_initiative',
            'model_type' => Initiative::class,
            'model_id' => $initiative->id,
            'old_values' => $oldValues,
            'new_values' => $initiative->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative updated successfully.');
    }

    public function destroy(Initiative $initiative)
    {
        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'delete_initiative',
            'model_type' => Initiative::class,
            'model_id' => $initiative->id,
            'old_values' => $initiative->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $initiative->delete();

        return redirect()->route('initiatives.index')
            ->with('success', 'Initiative deleted successfully.');
    }
}
