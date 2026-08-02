<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Measurement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'measurements';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'perspective',
        'objective',
        'measurement',
        'definition',
        'formula',
        'unit',
        'weight',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class);
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class);
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    public function realisasis(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function kpiInsights(): HasMany
    {
        return $this->hasMany(KpiInsight::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope a query to only include measurements of a given perspective.
     */
    public function scopeByPerspective($query, string $perspective)
    {
        return $query->where('perspective', $perspective);
    }
}
