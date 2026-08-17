<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorDocument extends Model
{
    protected $table = 'advisor_documents';

    protected $fillable = [
        'user_id',
        'name',
        'file_path',
        'document_type',
        'company',
        'period',
        'total_pages',
        'char_count',
        'pages_json',
        'status',
        'error_message',
        'processing_time',
    ];

    protected function casts(): array
    {
        return [
            'pages_json'      => 'array',
            'total_pages'     => 'integer',
            'char_count'      => 'integer',
            'processing_time' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Teks halaman ke-$page (1-based). Null bila halaman kosong/tidak ada. */
    public function pageText(int $page): ?string
    {
        $pages = $this->pages_json ?? [];

        return $pages[$page - 1] ?? null;
    }
}
