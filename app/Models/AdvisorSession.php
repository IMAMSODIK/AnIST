<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorSession extends Model
{
    protected $table = 'advisor_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'message_count',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'message_count'    => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AdvisorMessage::class, 'advisor_session_id');
    }
}
