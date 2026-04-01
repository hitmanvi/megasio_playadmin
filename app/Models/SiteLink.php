<?php

namespace App\Models;

class SiteLink extends Model
{
    protected $table = 'megasio_play_api.site_links';

    protected $fillable = [
        'key',
        'url',
        'deletable',
    ];

    protected function casts(): array
    {
        return [
            'deletable' => 'boolean',
        ];
    }
}
