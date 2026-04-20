<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NjkTemplate extends Model
{
    protected $table = 'sa_njk_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'filename',
        'body',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
