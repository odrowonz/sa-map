<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Element extends Model
{
    protected $table = 'sa_elements';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'level',
        'artifact_key',
        'sort_order',
        'content',
        'include_in_export',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'content' => 'array',
            'include_in_export' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function label(): string
    {
        $c = $this->content ?? [];
        $title = is_string($c['title'] ?? null) ? trim($c['title']) : '';
        $body = is_string($c['body'] ?? null) ? trim(strip_tags($c['body'])) : '';
        if ($title !== '') {
            return Str::limit($title, 80);
        }
        if ($body !== '') {
            return Str::limit($body, 80);
        }

        return '('.$this->artifact_key.')';
    }
}
