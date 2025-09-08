<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Settings;

class SettingsController extends Controller
{
    /**
     * Get public organization settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicSettings()
    {
        try {
            // Get from ENV first, then fall back to database settings
            $settings = [
                'name' => env('ORG_NAME') ?: Settings::get('org_name', config('app.name')),
                'type' => env('ORG_TYPE') ?: Settings::get('org_type', 'provinsi'),
                'region_code' => env('ORG_REGION_CODE') ?: Settings::get('org_region_code', 'default'),
                'logo_url' => env('ORG_LOGO_URL') ?: Settings::get('org_logo_url', '/storage/branding/default.png'),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Public settings retrieved successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update organization settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'org_name' => 'nullable|string|max:255',
                'org_type' => 'nullable|string|in:provinsi,kabupaten,kota',
                'org_region_code' => 'nullable|string|max:20',
                'org_logo_url' => 'nullable|string|max:500',
            ]);

            foreach ($validatedData as $key => $value) {
                if ($value !== null) {
                    Settings::set($key, $value, 'string', "Organization {$key} setting");
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => Settings::getOrganizationSettings()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
