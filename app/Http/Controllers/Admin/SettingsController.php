<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\SafeEmail;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit(Request $request, SettingService $settings)
    {
        abort_unless($request->user()->can('settings.view'), 403);
        $keys = ['app_name', 'support_email', 'support_mobile', 'whatsapp_number', 'heartbeat_interval', 'offline_timeout', 'pairing_code_length', 'firebase_project_id', 'firebase_database_url', 'razorpay_key', 'theme_default'];

        return view('admin.settings', ['values' => collect($keys)->mapWithKeys(fn ($key) => [$key => $settings->get($key)])->all()]);
    }

    public function update(Request $request, SettingService $settings)
    {
        abort_unless($request->user()->can('settings.update'), 403);
        $data = $request->validate(['app_name' => 'required|string|max:100', 'support_email' => ['nullable', new SafeEmail], 'support_mobile' => 'nullable|string|max:20', 'whatsapp_number' => 'nullable|string|max:20', 'heartbeat_interval' => 'required|integer|between:1,60', 'offline_timeout' => 'required|integer|between:2,120', 'pairing_code_length' => 'required|integer|between:6,16', 'firebase_project_id' => 'nullable|string|max:200', 'firebase_database_url' => 'nullable|url', 'firebase_service_account' => 'nullable|file|mimes:json,txt|max:512', 'razorpay_key' => 'nullable|string|max:255', 'razorpay_secret' => 'nullable|string|max:255', 'theme_default' => 'required|in:light,dark', 'logo' => 'nullable|image|max:2048', 'favicon' => 'nullable|image|max:512']);
        foreach (['app_name', 'support_email', 'support_mobile', 'whatsapp_number', 'heartbeat_interval', 'offline_timeout', 'pairing_code_length', 'firebase_project_id', 'firebase_database_url', 'razorpay_key', 'theme_default'] as $key) {
            $settings->set(strtok($key, '_'), $key, $data[$key] ?? '', in_array($key, ['heartbeat_interval', 'offline_timeout', 'pairing_code_length'], true) ? 'integer' : 'string');
        }
        if (! empty($data['razorpay_secret'])) {
            $settings->set('payment', 'razorpay_secret', $data['razorpay_secret'], 'string', true);
        }
        if ($request->hasFile('firebase_service_account')) {
            $json = json_decode($request->file('firebase_service_account')->get(), true);
            abort_unless(isset($json['client_email'], $json['private_key']), 422, 'Invalid Firebase service account JSON.');
            Storage::disk('local')->put('secure/firebase-service-account.json', json_encode($json, JSON_PRETTY_PRINT));
            $settings->set('firebase', 'firebase_credentials_path', 'secure/firebase-service-account.json', 'string', true);
            Cache::forget('firebase.access_token');
        }
        foreach (['logo', 'favicon'] as $file) {
            if ($request->hasFile($file)) {
                $settings->set('app', "app_{$file}", $request->file($file)->store('branding', 'public'));
            }
        }

        return back()->with('success', 'Settings saved.');
    }
}
