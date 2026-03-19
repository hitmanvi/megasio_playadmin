<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;

class SeedTestTagsCommand extends Command
{
    protected $signature = 'tags:seed-test
                            {--users= : Comma-separated user IDs to attach tags to (default: first 5 users)}
                            {--force : Recreate tags if they exist}';

    protected $description = 'Create a few test tags and attach them to users';

    public function handle(): int
    {
        $tags = $this->ensureTags();
        if ($tags->isEmpty()) {
            $this->error('No tags created or found.');
            return self::FAILURE;
        }

        $users = $this->getUsers();
        if ($users->isEmpty()) {
            $this->warn('No users found. Create users first.');
            return self::SUCCESS;
        }

        $this->attachTagsToUsers($tags, $users);

        $this->info('Done. Tags: ' . $tags->pluck('name')->join(', '));
        $this->info('Users with tags: ' . $users->count());

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tag>
     */
    private function ensureTags(): \Illuminate\Support\Collection
    {
        $definitions = [
            ['name' => 'vip', 'display_name' => 'VIP', 'color' => '#FFD700', 'sort_id' => 1],
            ['name' => 'new_user', 'display_name' => '新用户', 'color' => '#4CAF50', 'sort_id' => 2],
            ['name' => 'high_roller', 'display_name' => '高额玩家', 'color' => '#9C27B0', 'sort_id' => 3],
        ];

        $tags = collect();
        foreach ($definitions as $def) {
            if ($this->option('force')) {
                $tag = Tag::updateOrCreate(
                    ['name' => $def['name']],
                    array_merge($def, ['enabled' => true])
                );
            } else {
                $tag = Tag::firstOrCreate(
                    ['name' => $def['name']],
                    array_merge($def, ['enabled' => true])
                );
            }
            $tags->push($tag);
            $this->line("Tag: <fg=green>{$tag->name}</> (id={$tag->id})");
        }

        return $tags;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function getUsers(): \Illuminate\Support\Collection
    {
        $idsOption = $this->option('users');
        if ($idsOption !== null && $idsOption !== '') {
            $ids = array_map('intval', array_filter(explode(',', $idsOption)));
            $users = User::query()->whereIn('id', $ids)->orderBy('id')->get();
        } else {
            $users = User::query()->orderBy('id')->limit(5)->get();
        }

        return $users;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Tag>  $tags
     * @param  \Illuminate\Support\Collection<int, User>  $users
     */
    private function attachTagsToUsers(\Illuminate\Support\Collection $tags, \Illuminate\Support\Collection $users): void
    {
        $tagIds = $tags->pluck('id')->all();
        foreach ($users as $index => $user) {
            // Assign first (index+1) tags to this user so we have variety
            $userTagIds = array_slice($tagIds, 0, min($index + 1, count($tagIds)));
            $user->tags()->syncWithoutDetaching($userTagIds);
            $names = $tags->whereIn('id', $userTagIds)->pluck('name')->join(', ');
            $this->line("  User id={$user->id} (" . ($user->email ?? $user->uid ?? '') . ") → tags: {$names}");
        }
    }
}
