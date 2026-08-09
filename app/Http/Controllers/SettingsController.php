<?php

namespace App\Http\Controllers;

use App\AI\GeminiService;
use App\Http\Requests\UpdateGeminiKeyRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AppSetting;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function __construct(
        protected GeminiService $gemini,
    ) {}

    public function index()
    {
        $user = auth()->user();

        // Resolve the currently active key + its source. Priority:
        //   1. AppSetting::get('gemini_api_key') — UI-managed (DB override)
        //   2. config('services.gemini.api_key') — value from .env (or
        //      config:cache compiled config)
        //
        // We surface the source to the UI so the admin can tell whether the
        // running system is actually using the key they just typed (Hostinger
        // note: when config:cache is on, .env edits are ignored, so the DB
        // override is the only way to update at runtime).
        $envKey   = (string) (config('services.gemini.api_key') ?? '');
        $dbKey    = AppSetting::get('gemini_api_key');
        $activeKey = is_string($dbKey) && $dbKey !== '' ? $dbKey : $envKey;

        $keySource = is_string($dbKey) && $dbKey !== '' ? 'database' : ($envKey !== '' ? 'env' : 'none');
        $maskedKey = $activeKey !== ''
            ? substr($activeKey, 0, 6) . str_repeat('*', max(0, strlen($activeKey) - 10)) . substr($activeKey, -4)
            : '';

        // Show the admin when the DB override was last updated so they can
        // tell whether the queue worker has refreshed it yet.
        $dbKeyRow = AppSetting::query()->where('key', 'gemini_api_key')->first();
        $keyUpdatedAt = $dbKeyRow?->updated_at;

        return view('settings.index', compact('user', 'maskedKey', 'keySource', 'keyUpdatedAt'));
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

        // Persist the key to the AppSetting table (encrypted at rest via the
        // model's `encrypted` cast). We intentionally do NOT touch .env here:
        //   - On shared hosting (Hostinger) .env is often read-only.
        //   - When config:cache is enabled, .env edits are ignored at runtime
        //     anyway, so writing it would not take effect.
        // AppSetting::set() also calls Cache::forget() so the new value is
        // visible immediately to the current process and (after the 60s TTL
        // at most) to other processes such as the queue worker.
        AppSetting::set('gemini_api_key', $key);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action'  => 'update_gemini_key',
            'model_type' => 'AppSetting',
            'model_id'   => null,
            'new_values' => ['gemini_api_key' => 'updated', 'source' => 'database'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Gemini API Key berhasil diperbarui. Key aktif langsung untuk request dan paling lambat 60 detik untuk queue worker.');
    }

    /**
     * Restore the active key to the value present in .env (or compiled config
     * cache) by deleting the DB override. Useful when the admin typed a wrong
     * key or wants to fall back to the original deployment key.
     */
    public function resetGeminiKey(Request $request)
    {
        AppSetting::query()->where('key', 'gemini_api_key')->delete();
        AppSetting::forget('gemini_api_key');

        AuditTrail::create([
            'user_id'     => auth()->id(),
            'action'      => 'reset_gemini_key',
            'model_type'  => 'AppSetting',
            'model_id'    => null,
            'new_values'  => ['gemini_api_key' => 'reset to .env'],
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return back()->with('success', 'Override Gemini API Key dihapus. Sistem kembali memakai key dari .env / config cache.');
    }

    /**
     * Test that the currently configured API key actually works against the
     * Gemini API. Returns JSON so the /settings page can show the result
     * inline (no page reload). Optionally accepts `gemini_api_key` in the
     * request body so the admin can validate a key BEFORE saving it.
     */
    public function testGeminiKey(Request $request)
    {
        $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'min:10'],
        ]);

        $override = $request->input('gemini_api_key');
        $override = is_string($override) && trim($override) !== '' ? trim($override) : null;

        $result = $this->gemini->testApiKey($override);

        return response()->json($result);
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
