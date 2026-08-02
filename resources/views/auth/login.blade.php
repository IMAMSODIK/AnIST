@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-6">Sign in to your account</h2>

@if($errors->any())
<div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm">
    @foreach($errors->all() as $error)
    <p>{{ $error }}</p>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-slate-600">Remember me</span>
        </label>
    </div>

    <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors duration-200 text-sm">
        Sign In
    </button>
</form>

<p class="mt-6 text-center text-sm text-slate-500">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Create account</a>
</p>
@endsection
