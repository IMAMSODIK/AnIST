<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResult extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'upload_id',
        'matched_initiative',
        'confidence',
        'evidence_valid',
        'realisasi',
        'analysis',
        'recommendation',
        'raw_json',
        'error_message',
        'processing_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'evidence_valid' => 'boolean',
            'realisasi' => 'decimal:2',
            'raw_json' => 'array',
            'processing_time' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Get the recommendation field as an array.
     */
    public function getRecommendationsArrayAttribute(): array
    {
        if (empty($this->recommendation)) {
            return [];
        }

        $decoded = json_decode($this->recommendation, true);

        return is_array($decoded) ? $decoded : [];
    }
}
