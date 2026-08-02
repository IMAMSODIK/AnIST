<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeminiKeyRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $geminiKey = config('services.gemini.api_key', '');
        $maskedKey = $geminiKey
            ? substr($geminiKey, 0, 6) . str_repeat('*', strlen($geminiKey) - 10) . substr($geminiKey, -4)
            : '';

        return view('settings.index', compact('user', 'maskedKey'));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'update_profile',
            'model_type' => get_class($user),
            'model_id' => $user->id,
            'old_values' => ['name' => $user->name, 'email' => $user->email],
            'new_values' => ['name' => $request->name, 'email' => $request->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user->update($request->only('name', 'email'));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = auth()->user();

        AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'update_password',
            'model_type' => get_class($user),
            'model_id' => $user->id,
            'new_values' => ['password_changed' => true],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateGeminiKey(UpdateGeminiKeyRequest $request)
    {
        $key = $request->gemini_api_key;

        // Update .env file
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'GEMINI_API_KEY=')) {
            $envContent = preg_replace('/GEMINI_API_KEY=.*/', 'GEMINI_API_KEY=' . $key, $envContent);
        } else {
            $envContent .= "\nGEMINI_API_KEY=" . $key;
        }

        file_put_contents($envPath, $envContent);

        // Update runtime config
        config(['services.gemini.api_key' => $key]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'update_gemini_key',
            'model_type' => 'config',
            'model_id' => null,
            'new_values' => ['gemini_api_key' => 'updated'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Gemini API Key updated successfully.');
    }

    public function systemInfo()
    {
        try {
            $dbConnection = DB::connection()->getPdo() ? 'Connected' : 'Disconnected';
        } catch (\Exception) {
            $dbConnection = 'Disconnected';
        }

        try {
            $queueStatus = DB::table('jobs')->count() === 0 ? 'Empty' : DB::table('jobs')->count() . ' pending';
        } catch (\Exception) {
            $queueStatus = 'N/A';
        }

        return response()->json([
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'db_connection' => $dbConnection,
            'db_driver' => config('database.default'),
            'queue_connection' => config('queue.default'),
            'queue_status' => $queueStatus,
            'gemini_model' => config('services.gemini.model'),
            'storage_writable' => is_writable(storage_path()),
        ]);
    }
}
