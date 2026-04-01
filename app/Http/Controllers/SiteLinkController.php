<?php

namespace App\Http\Controllers;

use App\Models\SiteLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteLinkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = SiteLink::query()->orderBy('key');

        if ($request->filled('key')) {
            $query->where('key', 'like', '%'.$request->string('key').'%');
        }

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        return $this->responseListWithPaginator(
            $query->paginate($request->get('per_page', 15)),
            null
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:64', Rule::unique(SiteLink::class, 'key')],
            'url' => 'nullable|string|max:2048',
            'enabled' => 'nullable|boolean',
            'deletable' => 'nullable|boolean',
        ]);

        $link = SiteLink::create([
            'key' => $validated['key'],
            'url' => $validated['url'] ?? '',
            'enabled' => $validated['enabled'] ?? true,
            'deletable' => $validated['deletable'] ?? true,
        ]);

        return $this->responseItem($link);
    }

    public function show(SiteLink $siteLink): JsonResponse
    {
        return $this->responseItem($siteLink);
    }

    public function update(Request $request, SiteLink $siteLink): JsonResponse
    {
        $validated = $request->validate([
            'key' => [
                'sometimes',
                'string',
                'max:64',
                Rule::unique(SiteLink::class, 'key')->ignore($siteLink->id),
            ],
            'url' => 'sometimes|nullable|string|max:2048',
            'enabled' => 'sometimes|boolean',
            'deletable' => 'sometimes|boolean',
        ]);

        $siteLink->update($validated);

        return $this->responseItem($siteLink->fresh());
    }

    public function destroy(SiteLink $siteLink): JsonResponse
    {
        if (! $siteLink->deletable) {
            return $this->error([422, 'This site link cannot be deleted']);
        }

        $siteLink->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
