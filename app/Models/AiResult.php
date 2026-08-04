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
        'applications',
        'go_live_applications',
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
            'applications' => 'array',
            'go_live_applications' => 'array',
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

    /**
     * Get the applications field as an array. The `applications` cast already
     * handles normal decoding, but this accessor guards against malformed JSON
     * (e.g. legacy rows or manual DB edits) so callers always receive an array.
     */
    public function getApplicationsArrayAttribute(): array
    {
        $applications = $this->applications;

        if (is_array($applications)) {
            return $applications;
        }

        if (is_string($applications)) {
            $decoded = json_decode($applications, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get the go_live_applications field as an array. Same defensive decoding
     * as getApplicationsArrayAttribute, plus a LEGACY FALLBACK: when the
     * column is null (rows written before this column existed), we infer the
     * go-live state from the evidence's analysis text + file name so old
     * evidence continues to be counted correctly without re-running the AI.
     */
    public function getGoLiveApplicationsArrayAttribute(): array
    {
        $goLive = $this->go_live_applications;

        if (is_string($goLive)) {
            $decoded = json_decode($goLive, true);
            $goLive = is_array($decoded) ? $decoded : null;
        }

        // Explicit AI answer takes precedence.
        if (is_array($goLive)) {
            return $goLive;
        }

        // Legacy fallback: derive go-live status from analysis + file name.
        // We only treat an evidence as Go Live when it explicitly states so.
        $haystack = strtolower(
            (string) ($this->analysis ?? '') . ' ' . (string) ($this->upload?->file_name ?? '')
        );

        if ($haystack === ' ' || $haystack === '') {
            return [];
        }

        $goLiveMarkers = ['go live', 'go-live', 'golive', 'go-live', 'production', 'production deployment', 'deployed to production'];
        $isGoLive = false;
        foreach ($goLiveMarkers as $marker) {
            if (str_contains($haystack, $marker)) {
                $isGoLive = true;
                break;
            }
        }

        return $isGoLive ? $this->applications_array : [];
    }
}
