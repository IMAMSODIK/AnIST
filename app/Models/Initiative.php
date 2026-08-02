<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Initiative extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'initiatives';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'measurement_id',
        'initiative',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }
}
