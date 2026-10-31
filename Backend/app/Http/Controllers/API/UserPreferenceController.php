<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPreferenceController extends Controller
{
    /**
     * Get user preferences.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $preferences = $request->user()->preferences;

        if (! $preferences) {
            $preferences = $request->user()->preferences()->create([
                'settings' => [],
            ]);
        }

        return response()->json($preferences);
    }

    /**
     * Update user preferences.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'sometimes|array',
            'settings.*' => 'nullable',
        ]);

        $preferences = $request->user()->preferences;

        if (! $preferences) {
            $preferences = $request->user()->preferences()->create([
                'settings' => $request->input('settings', []),
            ]);
        } else {
            $currentSettings = $preferences->settings ?? [];
            $newSettings = array_merge($currentSettings, $request->input('settings', []));
            $preferences->update(['settings' => $newSettings]);
        }

        return response()->json($preferences->fresh());
    }
}