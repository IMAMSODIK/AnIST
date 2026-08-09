<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategicRecommendation extends Model
{
    use HasFactory;

    protected $table = 'strategic_recommendations';

    protected $fillable = [
        'user_id',
        'source_file',
        'file_path',
        'document_type',
        'company',
        'period',
        'total_pages',
        'extraction_json',
        'matched_kpis_json',
        'matched_initiatives_json',
        'recommendations_json',
        'popular_trends_json',
        'perspective_coverage_json',
        'analysis',
        'raw_response_json',
        'status',
        'error_message',
        'processing_time',
        'grounded',
    ];

    protected function casts(): array
    {
        return [
            'extraction_json'          => 'array',
            'matched_kpis_json'        => 'array',
            'matched_initiatives_json' => 'array',
            'recommendations_json'     => 'array',
            'popular_trends_json'      => 'array',
            'perspective_coverage_json'=> 'array',
            'raw_response_json'        => 'array',
            'processing_time'          => 'decimal:2',
            'total_pages'              => 'integer',
            'grounded'                  => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Convenience accessor used by views: list of recommendations (safe array). */
    public function getRecommendationsArrayAttribute(): array
    {
        if (! is_array($this->recommendations_json)) {
            return [];
        }

        return array_values($this->recommendations_json);
    }

    public function getPopularTrendsArrayAttribute(): array
    {
        if (! is_array($this->popular_trends_json)) {
            return [];
        }

        return array_values($this->popular_trends_json);
    }

    public function getMatchedKpisArrayAttribute(): array
    {
        if (! is_array($this->matched_kpis_json)) {
            return [];
        }

        return array_values($this->matched_kpis_json);
    }

    public function getPerspectiveCoverageArrayAttribute(): array
    {
        if (! is_array($this->perspective_coverage_json)) {
            return [];
        }

        return $this->perspective_coverage_json;
    }
}