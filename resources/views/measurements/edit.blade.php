@extends('layouts.app')
@section('title', 'Edit Measurement')
@section('page-title', 'Edit Measurement')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('measurements.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Measurements
        </a>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6">Edit: {{ $measurement->measurement }}</h3>
        <form method="POST" action="{{ route('measurements.update', $measurement) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Perspective</label>
                <select name="perspective" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                    @foreach(['Financial', 'Customer', 'Internal Process', 'Learning & Growth'] as $p)
                    <option value="{{ $p }}" {{ old('perspective', $measurement->perspective) == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                @error('perspective')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Objective</label>
                <input type="text" name="objective" value="{{ old('objective', $measurement->objective) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                @error('objective')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Measurement</label>
                <input type="text" name="measurement" value="{{ old('measurement', $measurement->measurement) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                @error('measurement')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Definition</label>
                <textarea name="definition" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">{{ old('definition', $measurement->definition) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Formula</label>
                    <select name="formula" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                        <option value="">Select</option>
                        @foreach(['Higher is Better', 'Lower is Better', 'Exact Target'] as $f)
                        <option value="{{ $f }}" {{ old('formula', $measurement->formula) == $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Unit</label>
                    <input type="text" name="unit" value="{{ old('unit', $measurement->unit) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                </div>
            </div>

            {{-- Inline quarterly targets --}}
            @php
                // Map existing targets (keyed by year+quarter) so the form can
                // pre-fill the selected year's quarters and let the user switch
                // years via the dropdown without losing data server-side.
                $targetsByYear = $measurement->targets->groupBy('year')->sortKeysDesc();
                $selectedYear = (int) old('target_year', $targetsByYear->keys()->first() ?? date('Y'));
                $yearTargets = $targetsByYear->get($selectedYear, collect());
            @endphp
            <div class="border-t border-slate-200 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-white">Quarterly Targets</h4>
                    <span class="text-xs text-slate-400">Optional</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Pilih tahun untuk melihat dan mengatur target per quarter. Quarter yang sudah tersimpan tidak akan terhapus saat update (form hanya menambah / memperbarui quarter yang diisi).</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Year</label>
                    <select name="target_year" id="target_year" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                        @if($targetsByYear->isEmpty())
                            <option value="{{ $selectedYear }}" selected>{{ $selectedYear }}</option>
                        @else
                            @foreach($targetsByYear as $year => $rows)
                                <option value="{{ $year }}" {{ $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('target_year')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach(['q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'] as $key => $label)
                        @php
                            $existing = $yearTargets->firstWhere('quarter', $label);
                            $qValue = old("targets.{$key}", $existing->target ?? null);
                        @endphp
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">{{ $label }} Target</label>
                            <input type="number" name="targets[{{ $key }}]" value="{{ $qValue !== null ? $qValue : '' }}" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                            @error("targets.{$key}")<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-2">Tip: untuk menambah tahun baru, isi kolom Year dengan tahun baru lalu isi quarter yang diperlukan, kemudian klik Update.</p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('measurements.index') }}" class="px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">Update Measurement</button>
            </div>
        </form>
    </div>
</div>
@endsection
