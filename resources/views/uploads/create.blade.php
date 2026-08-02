@extends('layouts.app')
@section('title', 'Upload Evidence')
@section('page-title', 'Upload Evidence')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('uploads.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Uploads
        </a>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Upload New Evidence</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Upload evidence document for AI-powered analysis. Supported formats: PDF, DOCX, XLSX, JPG, JPEG, PNG (max 10MB)</p>

        <form method="POST" action="{{ route('uploads.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">KPI Measurement</label>
                <select name="measurement_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                    <option value="">Select Measurement</option>
                    @foreach($measurements->groupBy('perspective') as $perspective => $items)
                    <optgroup label="{{ $perspective }}">
                        @foreach($items as $m)<option value="{{ $m->id }}" {{ old('measurement_id') == $m->id ? 'selected' : '' }}>{{ $m->measurement }}</option>@endforeach
                    </optgroup>
                    @endforeach
                </select>
                @error('measurement_id')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Year</label>
                    <input type="number" name="year" value="{{ old('year', date('Y')) }}" min="2020" max="2050" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Quarter</label>
                    <select name="quarter" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                        @foreach(['Q1','Q2','Q3','Q4'] as $q)<option value="{{ $q }}" {{ old('quarter') == $q ? 'selected' : '' }}>{{ $q }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div x-data="{ fileName: '' }">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Evidence File</label>
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors cursor-pointer" @click="$refs.fileInput.click()">
                    <input type="file" name="file" required x-ref="fileInput" class="hidden" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" @change="fileName = $event.target.files[0]?.name || ''">
                    <svg class="w-10 h-10 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="text-sm text-slate-600 dark:text-slate-400" x-show="!fileName">Click to select file or drag and drop</p>
                    <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400" x-show="fileName" x-text="fileName"></p>
                    <p class="text-xs text-slate-400 mt-2">PDF, DOCX, XLSX, JPG, JPEG, PNG up to 10MB</p>
                </div>
                @error('file')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-sm text-indigo-700 dark:text-indigo-300">
                        <p class="font-medium mb-1">How it works:</p>
                        <ol class="list-decimal list-inside space-y-1 text-xs">
                            <li>Upload your evidence document</li>
                            <li>Google Gemini AI will analyze the document</li>
                            <li>AI determines the realisasi value automatically</li>
                            <li>KPI score is calculated based on the formula</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('uploads.index') }}" class="px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload & Analyze
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
