<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorMessage extends Model
{
    protected $table = 'advisor_messages';

    protected $fillable = [
        'user_id',
        'advisor_session_id',
        'question',
        'answer',
        'citations_json',
        'trends_json',
        'recommendations_json',
        'context_documents_json',
        'raw_response_json',
        'grounded',
        'status',
        'error_message',
        'processing_time',
    ];

    protected function casts(): array
    {
        return [
            'citations_json'         => 'array',
            'trends_json'            => 'array',
            'recommendations_json'   => 'array',
            'context_documents_json' => 'array',
            'raw_response_json'      => 'array',
            'grounded'               => 'boolean',
            'processing_time'        => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AdvisorSession::class, 'advisor_session_id');
    }

    public function getCitationsArrayAttribute(): array
    {
        return array_values($this->citations_json ?? []);
    }

    public function getTrendsArrayAttribute(): array
    {
        return array_values($this->trends_json ?? []);
    }

    public function getRecommendationsArrayAttribute(): array
    {
        return array_values($this->recommendations_json ?? []);
    }
}
