@extends('layouts.app')
@section('title', 'Create Initiative')
@section('page-title', 'Create Initiative')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('initiatives.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Initiatives
        </a>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6">New Initiative</h3>
        <form method="POST" action="{{ route('initiatives.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Measurement</label>
                <select name="measurement_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                    <option value="">Select Measurement</option>
                    @foreach($measurements as $m)<option value="{{ $m->id }}" {{ old('measurement_id') == $m->id ? 'selected' : '' }}>{{ $m->measurement }}</option>@endforeach
                </select>
                @error('measurement_id')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Initiative Description</label>
                <textarea name="initiative" rows="4" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">{{ old('initiative') }}</textarea>
                @error('initiative')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('initiatives.index') }}" class="px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">Create Initiative</button>
            </div>
        </form>
    </div>
</div>
@endsection
