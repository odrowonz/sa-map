<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Attachment extends Model
{
    public $timestamps = false;

    protected $table = 'sa_attachments';

    protected $fillable = [
        'project_id',
        'storage_key',
        'original_name',
        'mime_type',
        'kind',
        'size_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(Element::class, 'sa_attachment_element', 'attachment_id', 'element_id');
    }

    /** Предпросмотр в браузере как изображение (растровые и SVG). */
    public function canPreviewInline(): bool
    {
        $m = $this->mime_type ?? '';

        return is_string($m) && str_starts_with($m, 'image/');
    }
}
