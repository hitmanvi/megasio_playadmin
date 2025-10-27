<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameGroup;
use App\Models\GameGroupGame;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GameGroupController extends Controller
{
    /**
     * Get game groups list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = GameGroup::with('games');

        // Apply filters
        if ($request->has('category') && $request->category) {
            $query->byCategory($request->category);
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $gameGroups = $query->paginate($perPage);

        return $this->responseListWithPaginator($gameGroups, null);
    }

    /**
     * Display the specified game group.
     */
    public function show(GameGroup $gameGroup): JsonResponse
    {
        $gameGroup->load('games');
        return $this->responseItem($gameGroup);
    }

    /**
     * Store a newly created game group in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'app_limit' => 'nullable|integer|min:0',
            'web_limit' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $gameGroup = GameGroup::create($request->all());

        return $this->responseItem($gameGroup);
    }

    /**
     * Update the specified game group in storage.
     */
    public function update(Request $request, GameGroup $gameGroup): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'app_limit' => 'nullable|integer|min:0',
            'web_limit' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $gameGroup->update($request->all());

        return $this->responseItem($gameGroup);
    }

    /**
     * Remove the specified game group from storage.
     */
    public function destroy(GameGroup $gameGroup): JsonResponse
    {
        $gameGroup->delete();

        return $this->responseItem([
            'message' => 'Game group deleted successfully'
        ]);
    }

    /**
     * Attach a game to a game group
     */
    public function attachGame(Request $request, GameGroup $gameGroup): JsonResponse
    {
        $request->validate([
            'game_id' => 'required|integer|exists:megasio_play_api.games,id',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        $sortId = $request->get('sort_id', 0);

        // Check if game is already attached
        $exists = $gameGroup->games()->where('game_id', $request->game_id)->exists();
        
        if ($exists) {
            return $this->error(['code' => 422, 'message' => 'Game is already attached to this group']);
        }

        // Attach game with pivot data
        $gameGroup->games()->attach($request->game_id, [
            'sort_id' => $sortId
        ]);

        $gameGroup->load('games');

        return $this->responseItem($gameGroup);
    }

    /**
     * Attach multiple games to a game group
     */
    public function attachGames(Request $request, GameGroup $gameGroup): JsonResponse
    {
        $request->validate([
            'games' => 'required|array|min:1',
            'games.*' => 'required|integer|exists:megasio_play_api.games,id',
        ]);

        $gameIds = $request->games;

        // Get existing game IDs to avoid duplicates
        $existingGameIds = $gameGroup->games()->pluck('games.id')->toArray();
        
        // Filter out already attached games
        $newGameIds = array_diff($gameIds, $existingGameIds);

        if (empty($newGameIds)) {
            return $this->error(['code' => 422, 'message' => 'All games are already attached to this group']);
        }

        // Prepare pivot data with default sort_id
        $pivotData = [];
        foreach ($newGameIds as $index => $gameId) {
            $pivotData[$gameId] = ['sort_id' => $index];
        }

        // Attach multiple games with pivot data
        $gameGroup->games()->attach($pivotData);

        $gameGroup->load('games');

        return $this->responseItem($gameGroup);
    }

    /**
     * Detach a game from a game group
     */
    public function detachGame(GameGroup $gameGroup, Game $game): JsonResponse
    {
        $gameGroup->games()->detach($game->id);

        $gameGroup->load('games');

        return $this->responseItem($gameGroup);
    }

    /**
     * Update the game order in a game group
     */
    public function updateGameOrder(Request $request, GameGroup $gameGroup): JsonResponse
    {
        $request->validate([
            'games' => 'required|array',
            'games.*.game_id' => 'required|integer|exists:megasio_play_api.games,id',
            'games.*.sort_id' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($gameGroup, $request) {
            foreach ($request->games as $gameData) {
                GameGroupGame::where('game_group_id', $gameGroup->id)
                    ->where('game_id', $gameData['game_id'])
                    ->update(['sort_id' => $gameData['sort_id']]);
            }
        });

        $gameGroup->load('games');

        return $this->responseItem($gameGroup);
    }
}

