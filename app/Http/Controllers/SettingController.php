<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Get settings list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'nullable|string',
            'group' => 'nullable|string',
            'type' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Setting::query();

        // Filter by key
        if ($request->has('key') && $request->key) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        // Filter by group
        if ($request->has('group') && $request->group) {
            $query->byGroup($request->group);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Order by group and key
        $query->orderBy('group')->orderBy('key');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $settings = $query->paginate($perPage);

        return $this->responseListWithPaginator($settings, null);
    }

    /**
     * Get all available groups
     */
    public function groups(): JsonResponse
    {
        $groups = Setting::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return $this->responseItem(['groups' => $groups]);
    }

    /**
     * Get settings by group
     */
    public function getByGroup(Request $request, string $group): JsonResponse
    {
        $settings = Setting::byGroup($group)
            ->orderBy('key')
            ->get();

        return $this->responseItem([
            'group' => $group,
            'settings' => $settings,
        ]);
    }

    /**
     * Get setting by key
     */
    public function getByKey(string $key): JsonResponse
    {
        $setting = Setting::byKey($key)->first();

        if (!$setting) {
            return $this->error(Err::ACCOUNT_NOT_FOUND);
        }

        return $this->responseItem($setting);
    }

    /**
     * Create a new setting
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100|unique:settings,key',
            'value' => 'nullable',
            'type' => ['nullable', 'string', Rule::in(['string', 'integer', 'boolean', 'json', 'array'])],
            'group' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $setting = new Setting();
        $setting->key = $validated['key'];
        $setting->value = $validated['value'] ?? null;
        $setting->type = $validated['type'] ?? 'string';
        $setting->group = $validated['group'] ?? 'general';
        $setting->description = $validated['description'] ?? null;
        $setting->save();

        return $this->responseItem($setting);
    }

    /**
     * Get setting details
     */
    public function show(Setting $setting): JsonResponse
    {
        return $this->responseItem($setting);
    }

    /**
     * Update setting
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'nullable',
            'type' => ['nullable', 'string', Rule::in(['string', 'integer', 'boolean', 'json', 'array'])],
            'group' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('value', $validated)) {
            $setting->value = $validated['value'];
        }

        if (array_key_exists('type', $validated)) {
            $setting->type = $validated['type'];
        }

        if (array_key_exists('group', $validated)) {
            $setting->group = $validated['group'];
        }

        if (array_key_exists('description', $validated)) {
            $setting->description = $validated['description'];
        }

        $setting->save();

        return $this->responseItem($setting);
    }

    /**
     * Update setting by key
     */
    public function updateByKey(Request $request, string $key): JsonResponse
    {
        $setting = Setting::byKey($key)->first();

        if (!$setting) {
            return $this->error(Err::ACCOUNT_NOT_FOUND);
        }

        $validated = $request->validate([
            'value' => 'nullable',
        ]);

        $setting->value = $validated['value'] ?? null;
        $setting->save();

        return $this->responseItem($setting);
    }

    /**
     * Batch update settings
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable',
        ]);

        $updated = [];
        
        foreach ($validated['settings'] as $item) {
            $setting = Setting::byKey($item['key'])->first();
            
            if ($setting) {
                $setting->value = $item['value'] ?? null;
                $setting->save();
                $updated[] = $setting;
            }
        }

        return $this->responseItem(['updated' => $updated]);
    }

    /**
     * Delete setting
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
