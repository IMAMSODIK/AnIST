<?php

namespace App\DTO;

class AiResultDTO
{
    public function __construct(
        public readonly ?string $matchedInitiative,
        public readonly float $confidence,
        public readonly bool $evidenceValid,
        public readonly float $realisasi,
        public readonly string $analysis,
        public readonly array $recommendations,
        public readonly ?array $rawJson,
        public readonly float $processingTime,
        public readonly ?string $errorMessage = null,
        public readonly array $applications = [],
        public readonly array $goLiveApplications = [],
        public readonly array $slaTargets = [],
        public readonly array $investmentItems = [],
        public readonly array $traceabilityItems = [],
        public readonly array $rstiItems = [],
    ) {}

    public static function fromAiResponse(array $data, array $response): self
    {
        return new self(
            matchedInitiative: $data['matched_initiative']['name'] ?? null,
            confidence: (float) ($data['matched_initiative']['confidence'] ?? 0),
            evidenceValid: (bool) $data['evidence_valid'],
            realisasi: (float) $data['realisasi'],
            analysis: $data['analysis'] ?? '',
            recommendations: $data['recommendations'] ?? [],
            rawJson: $response['raw_response'] ?? null,
            processingTime: (float) ($response['processing_time'] ?? 0),
            applications: $data['applications'] ?? [],
            goLiveApplications: $data['go_live_applications'] ?? [],
            slaTargets: $data['sla_targets'] ?? [],
            investmentItems: $data['investment_items'] ?? [],
            traceabilityItems: $data['traceability_items'] ?? [],
            rstiItems: $data['rsti_items'] ?? [],
        );
    }

    public static function fromError(string $error, float $processingTime = 0, mixed $rawResponse = null): self
    {
        // rawResponse may arrive as string, array, or null from GeminiService.
        // Ensure it is always stored as an array (or null).
        $normalizedRaw = is_array($rawResponse) ? $rawResponse : (is_string($rawResponse) ? json_decode($rawResponse, true) : null);

        return new self(
            matchedInitiative: null,
            confidence: 0,
            evidenceValid: false,
            realisasi: 0,
            analysis: '',
            recommendations: [],
            rawJson: $normalizedRaw,
            processingTime: $processingTime,
            errorMessage: $error,
            applications: [],
            goLiveApplications: [],
            slaTargets: [],
            investmentItems: [],
            traceabilityItems: [],
            rstiItems: [],
        );
    }

    public function toArray(): array
    {
        return [
            'matched_initiative' => $this->matchedInitiative,
            'confidence' => $this->confidence,
            'evidence_valid' => $this->evidenceValid,
            'realisasi' => $this->realisasi,
            'applications' => $this->applications,
            'go_live_applications' => $this->goLiveApplications,
            'sla_targets' => $this->slaTargets,
            'investment_items' => $this->investmentItems,
            'traceability_items' => $this->traceabilityItems,
            'rsti_items' => $this->rstiItems,
            'analysis' => $this->analysis,
            'recommendation' => json_encode($this->recommendations),
            'raw_json' => $this->rawJson,
            'processing_time' => $this->processingTime,
            'error_message' => $this->errorMessage,
        ];
    }
}
