<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ResponseTrait;

class GameController extends Controller
{
    use ResponseTrait;

    /**
     * Get games list with various filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'out_id' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'brand_name' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'theme_id' => 'nullable|integer',
            'theme_ready' => 'nullable|boolean',
            'localization_set' => 'nullable|boolean',
            'thumbnail_uploaded' => 'nullable|boolean',
            'memo_present' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'locale' => 'nullable|string',
        ]);

        $query = Game::with(['brand', 'category', 'theme', 'translations']);

        // Apply filters
        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        if ($request->has('out_id') && $request->out_id) {
            $query->byOutId($request->out_id);
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        if ($request->has('brand_name') && $request->brand_name) {
            $query->byBrandName($request->brand_name);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('theme_id') && $request->theme_id) {
            $query->byTheme($request->theme_id);
        }

        if ($request->has('theme_ready')) {
            $query->byThemeReady($request->boolean('theme_ready'));
        }

        if ($request->has('localization_set')) {
            $query->byLocalizationSet($request->boolean('localization_set'));
        }

        if ($request->has('thumbnail_uploaded')) {
            $query->byThumbnailUploaded($request->boolean('thumbnail_uploaded'));
        }

        if ($request->has('memo_present')) {
            $query->byMemoPresent($request->boolean('memo_present'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $games = $query->paginate($perPage);

        // Transform the data
        $games->getCollection()->transform(function ($game) use ($request) {
            return $this->formatGameResponse($game, $request->get('locale'));
        });

        return $this->responseListWithPaginator($games, null);
    }

    /**
     * Display the specified game.
     */
    public function show(Request $request, Game $game): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string',
        ]);

        $game->load(['brand', 'category', 'theme', 'translations']);

        return $this->responseItem($this->formatGameResponse($game, $request->get('locale')));
    }

    /**
     * Update the specified game in storage.
     */
    public function update(Request $request, Game $game): JsonResponse
    {
        $request->validate([
            'brand_id' => 'nullable|integer|exists:brands,id',
            'category_id' => 'nullable|integer|exists:tags,id',
            'theme_id' => 'nullable|integer|exists:tags,id',
            'out_id' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'memo' => 'nullable|string',
            'translations' => 'nullable|array',
            'translations.*' => 'string|max:255',
        ]);

        // Update fields if provided
        $updateData = $request->only([
            'brand_id',
            'category_id',
            'theme_id',
            'out_id',
            'name',
            'thumbnail',
            'sort_id',
            'enabled',
            'memo',
        ]);

        // Remove null values
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        });

        $game->update($updateData);

        // Update translations if provided
        if ($request->has('translations')) {
            $game->setNames($request->translations);
        }

        // Reload with relationships
        $game->load(['brand', 'category', 'theme', 'translations']);

        return $this->responseItem($this->formatGameResponse($game, $request->get('locale')));
    }

    /**
     * Format game response with related data
     */
    protected function formatGameResponse(Game $game, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return [
            'id' => $game->id,
            'brand_id' => $game->brand_id,
            'category_id' => $game->category_id,
            'theme_id' => $game->theme_id,
            'out_id' => $game->out_id,
            'name' => $game->getName($locale),
            'thumbnail' => $game->thumbnail,
            'sort_id' => $game->sort_id,
            'enabled' => $game->enabled,
            'memo' => $game->memo,
            'name_translations' => $game->getAllNames(),
            'brand' => $game->brand ? [
                'id' => $game->brand->id,
                'name' => $game->brand->name,
                'provider' => $game->brand->provider,
            ] : null,
            'category' => $game->category ? [
                'id' => $game->category->id,
                'name' => $game->category->name,
                'type' => $game->category->type,
            ] : null,
            'theme' => $game->theme ? [
                'id' => $game->theme->id,
                'name' => $game->theme->name,
                'type' => $game->theme->type,
                'enabled' => $game->theme->enabled,
            ] : null,
            'created_at' => $game->created_at,
            'updated_at' => $game->updated_at,
        ];
    }
}
