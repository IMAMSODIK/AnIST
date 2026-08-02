<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiInsight extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'kpi_insights';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'measurement_id',
        'quarter',
        'year',
        'achieved_reason',
        'not_achieved_reason',
        'recommendations',
        'raw_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'recommendations' => 'array',
            'raw_json' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Get the recommendations as an array even when the column is empty.
     */
    public function getRecommendationsArrayAttribute(): array
    {
        if (empty($this->recommendations)) {
            return [];
        }

        return is_array($this->recommendations) ? $this->recommendations : [];
    }
}
