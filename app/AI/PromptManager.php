<?php

namespace App\AI;

use App\Models\Measurement;
use App\Models\Initiative;

class PromptManager
{
    /**
     * Generate prompt for evidence analysis based on measurement type
     */
    public function generatePrompt(Measurement $measurement, array $initiatives = []): string
    {
        $basePrompt = $this->getBasePrompt($measurement);
        $initiativeList = $this->formatInitiatives($initiatives);
        $outputFormat = $this->getOutputFormat();

        return <<<PROMPT
{$basePrompt}

## Measurement Details
- **Measurement**: {$measurement->measurement}
- **Perspective**: {$measurement->perspective}
- **Objective**: {$measurement->objective}
- **Definition**: {$measurement->definition}
- **Formula**: {$measurement->formula}
- **Unit**: {$measurement->unit}

## Available Initiatives
{$initiativeList}

## Your Tasks
1. Read and understand the uploaded evidence document thoroughly
2. Validate whether the evidence is relevant to the measurement described above
3. Match the evidence to the most relevant initiative from the list above
4. Determine the realisasi (actual achievement value) based on the evidence content
5. Provide detailed analysis of the evidence
6. Give actionable recommendations

## Important Rules
- You must ONLY determine the realisasi value. Do NOT calculate any KPI score.
- The realisasi value should be a number that represents the actual achievement.
- If the measurement is about counting incidents, events, or occurrences (e.g. formula "lower is better" or name contains "incident"), the realisasi is the COUNT (integer: 0, 1, 2, 3, …), NOT a completion percentage.
- If the measurement is about completion/implementation (default), realisasi = 1 if fully completed, or a proportional value (e.g., 0.5 for 50%)
- If the evidence is not valid or not relevant, set evidence_valid to false and realisasi to 0
- Confidence should be between 0-100, reflecting how confident you are in your analysis
- Recommendations should be actionable and specific

        {$outputFormat}
PROMPT;
    }

    /**
     * Generate the prompt for the SECOND AI call that explains WHY a KPI is
     * achieved or not. Unlike the first call (evidence analysis), Laravel has
     * already computed the target, realisasi, achievement percentage and final
     * status — they are passed in via $context so Gemini only needs to narrate
     * the reasons. Gemini must NOT recalculate anything.
     *
     * Expected $context keys:
     *   target, realisasi, achievement, status, gap, gap_direction,
     *   evidence_analysis, matched_initiative
     */
    public function generateInsightPrompt(Measurement $measurement, array $context): string
    {
        $target = $context['target'] ?? 0;
        $realisasi = $context['realisasi'] ?? 0;
        $achievement = $context['achievement'] ?? 0;
        $status = $context['status'] ?? 'Unknown';
        $gap = $context['gap'] ?? 0;
        $gapDirection = $context['gap_direction'] ?? '';
        $evidenceAnalysis = $context['evidence_analysis'] ?? '';
        $matchedInitiative = $context['matched_initiative'] ?? '';

        $evidenceBlock = $evidenceAnalysis
            ? "### Evidence Analysis (from previous AI reading)\n{$evidenceAnalysis}"
            : "### Evidence Analysis (from previous AI reading)\nNo prior analysis available.";

        $initiativeBlock = $matchedInitiative
            ? "Matched initiative: {$matchedInitiative}"
            : 'Matched initiative: none';

        // Add formula-specific interpretation guidance so Gemini writes a
        // contextually accurate narrative instead of generic filler.
        $formulaHint = $this->getFormulaInterpretationHint($measurement->formula, $target, $realisasi);

        return <<<PROMPT
# KPI Achievement Reasoning

You are an executive KPI advisor. Laravel has ALREADY calculated every metric below using the company formula. Your ONLY job is to explain, in clear business language, the REASONS the KPI is achieved or not achieved, and give actionable recommendations.

## Hard Rules (do NOT break these)
- Do NOT recalculate, recompute, or question the achievement percentage, score, target, realisasi, or status. They are final and provided by Laravel.
- Do NOT invent numbers. Use only the figures given below.
- Focus on the WHY (narrative explanation), not the math.
- Write in Indonesian (Bahasa Indonesia), professional and executive-friendly tone.

## Measurement
- **Measurement**: {$measurement->measurement}
- **Perspective**: {$measurement->perspective}
- **Objective**: {$measurement->objective}
- **Definition**: {$measurement->definition}
- **Formula**: {$measurement->formula}
- **Unit**: {$measurement->unit}

## Final KPI Result (computed by Laravel — treat as ground truth)
- **Target**: {$target} {$measurement->unit}
- **Realisasi (actual)**: {$realisasi} {$measurement->unit}
- **Achievement**: {$achievement}%
- **Status**: {$status}
- **Gap**: {$gap} {$measurement->unit} ({$gapDirection})
- {$initiativeBlock}

{$formulaHint}

{$evidenceBlock}

## Your Tasks
1. **achieved_reason**: Explain WHY this KPI reached (or is on track to reach) its target. Reference concrete factors visible in the evidence analysis (e.g. system go-live, delivered modules, completed audits, transaction volumes). If the KPI is fully achieved, explain the success drivers. If it is NOT achieved, you may still note partial progress factors — but keep this field focused on what went right.
2. **not_achieved_reason**: Explain WHY this KPI has NOT yet reached its target (the gap). Be specific about what is missing, delayed, incomplete, or blocking. If the KPI IS fully achieved (status = Achieved), set this to an empty string "".
3. **recommendations**: Provide 2-4 actionable, specific recommendations to either sustain success (if achieved) or close the gap (if not achieved). Each recommendation must be a concrete next step, not a generic platitude.

## Required Output Format
Respond with ONLY a valid JSON object in exactly this format:

```json
{
    "achieved_reason": "Penjelasan mengapa KPI tercapai / on track...",
    "not_achieved_reason": "Penjelasan mengapa KPI belum tercapai (kosongkan jika sudah Achieved)",
    "recommendations": [
        "Rekomendasi pertama yang konkret",
        "Rekomendasi kedua",
        "Rekomendasi ketiga"
    ]
}
```

IMPORTANT: Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Get base prompt based on measurement category
     */
    protected function getBasePrompt(Measurement $measurement): string
    {
        $name = strtolower($measurement->measurement);

        if (str_contains($name, 'implementasi sistem') || str_contains($name, 'system implementation')) {
            return $this->getImplementasiSistemPrompt();
        }

        if (str_contains($name, 'cybersecurity') || str_contains($name, 'cyber security') || str_contains($name, 'keamanan')) {
            // More specific: if it's about counting incidents, route to
            // the incident-count prompt which instructs Gemini to tally rows.
            if (str_contains($name, 'incident')) {
                return $this->getCybersecurityIncidentPrompt();
            }

            return $this->getCybersecurityPrompt();
        }

        if (str_contains($name, 'payment') || str_contains($name, 'pembayaran')) {
            return $this->getPaymentPrompt();
        }

        if (str_contains($name, 'investment') || str_contains($name, 'investasi')) {
            return $this->getInvestmentPrompt();
        }

        // Match "ai" as a whole word using word boundaries, so a measurement
        // named "AI Implementation" (no leading space) still routes correctly.
        // The previous `str_contains($name, ' ai ')` missed names that started
        // or ended with the token.
        if (
            str_contains($name, 'artificial intelligence')
            || str_contains($name, 'machine learning')
            || preg_match('/\bai\b/', $name)
        ) {
            return $this->getAIPrompt();
        }

        if (str_contains($name, 'enterprise architecture') || str_contains($name, 'arsitektur')) {
            return $this->getEnterpriseArchitecturePrompt();
        }

        return $this->getDefaultPrompt();
    }

    protected function getImplementasiSistemPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Implementasi Sistem

You are an expert IT auditor analyzing evidence for System Implementation KPI.
Focus on:
- Whether the system has been deployed/implemented
- Go-live dates and deployment evidence
- User acceptance testing (UAT) results
- System functionality and features implemented
- Number of modules/features delivered vs planned
PROMPT;
    }

    protected function getCybersecurityPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Cybersecurity

You are a cybersecurity expert analyzing evidence for Cybersecurity KPI.
Focus on:
- Security audit results
- Vulnerability assessment findings
- Incident reports and resolution
- Compliance certifications (ISO 27001, etc.)
- Security awareness training completion
- Penetration testing results
PROMPT;
    }

    /**
     * Specialised prompt for cybersecurity incident-count KPIs.
     *
     * Unlike the generic cybersecurity prompt (which focuses on audit
     * reports and compliance), this prompt instructs Gemini to read a network
     * detection log table and COUNT the rows whose "action" column is
     * NOT "blocked". Each non-blocked row = 1 successful attack = 1 incident.
     */
    protected function getCybersecurityIncidentPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Cybersecurity Incident Count

You are a cybersecurity incident analyst. Your task is to COUNT the number of successful security incidents from a network detection log.

## What Counts as an "Incident"
An incident is counted when a network event's **"action" column value is NOT "blocked"** (case-insensitive). This means the attack successfully penetrated the system.

## Instructions
1. Read the uploaded evidence (network detection log) carefully
2. Look at every row/entry in the log table
3. For EACH row, check the "action" column value
4. If action = "blocked" → NOT an incident (attack was stopped)
5. If action = anything OTHER than "blocked" (e.g., "allowed", "passed", "detected", "logged", "no action", "alert", "quarantined", etc.) → this IS an incident (attack succeeded)
6. Count ALL rows where action ≠ "blocked"
7. The total count is your realisasi value

## Critical Rules
- The realisasi value must be an INTEGER representing the COUNT of incidents (0, 1, 2, 3, etc.)
- Count EVERY row in the evidence, do not skip any entries
- If the evidence is empty, corrupted, or has no table/log data, set evidence_valid = false and realisasi = 0
- The "action" column check is case-insensitive ("Blocked", "BLOCKED", "blocked" all mean blocked)
- If ALL rows have action = "blocked", then realisasi = 0 (zero incidents — perfect score)
PROMPT;
    }

    protected function getPaymentPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Payment

You are a financial analyst analyzing evidence for Payment KPI.
Focus on:
- Transaction volumes and values
- Payment processing metrics
- Settlement rates and timing
- Error rates and resolution
- Digital payment adoption rates
PROMPT;
    }

    protected function getInvestmentPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Investment

You are an investment analyst analyzing evidence for Investment KPI.
Focus on:
- Return on investment (ROI) figures
- Portfolio performance metrics
- Investment utilization rates
- Budget vs actual spending
- Cost savings achieved
PROMPT;
    }

    protected function getAIPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Artificial Intelligence

You are an AI/ML specialist analyzing evidence for AI Implementation KPI.
Focus on:
- AI model deployment evidence
- Model performance metrics (accuracy, precision, recall)
- Use cases implemented
- Data processing volumes
- Automation rates achieved
PROMPT;
    }

    protected function getEnterpriseArchitecturePrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Enterprise Architecture

You are an enterprise architect analyzing evidence for Enterprise Architecture KPI.
Focus on:
- Architecture compliance assessments
- Technology roadmap progress
- Standards adoption rates
- Integration completeness
- Architecture review outcomes
PROMPT;
    }

    protected function getDefaultPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis

You are a KPI analyst analyzing uploaded evidence.
Focus on:
- Relevance of the evidence to the measurement
- Quantifiable achievements shown in the evidence
- Completeness of the evidence
- Quality and reliability of the data presented
PROMPT;
    }

    /**
     * Format initiatives for prompt
     */
    protected function formatInitiatives(array $initiatives): string
    {
        if (empty($initiatives)) {
            return "No specific initiatives defined for this measurement.";
        }

        $list = "";
        foreach ($initiatives as $index => $initiative) {
            $num = $index + 1;
            $name = is_string($initiative) ? $initiative : ($initiative['initiative'] ?? $initiative['name'] ?? '');
            $list .= "{$num}. {$name}\n";
        }

        return $list;
    }

    /**
     * Get expected output format
     */
    protected function getOutputFormat(): string
    {
        return <<<'PROMPT'
## Required Output Format
You MUST respond with a valid JSON object in exactly this format:

```json
{
    "measurement": "Name of the measurement being analyzed",
    "matched_initiative": {
        "name": "Name of the matched initiative from the list",
        "confidence": 95
    },
    "evidence_valid": true,
    "realisasi": 1,
    "analysis": "Detailed analysis of the evidence...",
    "recommendations": [
        "First actionable recommendation",
        "Second actionable recommendation",
        "Third actionable recommendation"
    ]
}
```

IMPORTANT: Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Return a short explanation block that tells Gemini HOW to interpret
     * the formula in human terms, so its narrative is contextually accurate.
     */
    protected function getFormulaInterpretationHint(?string $formula, float $target, float $realisasi): string
    {
        $formula = strtolower($formula ?? 'higher is better');

        if ($formula === 'lower is better') {
            if ($target <= 0) {
                // Zero-tolerance: target is 0, any positive realisasi = failure
                if ($realisasi <= 0) {
                    return "## Formula Interpretation\nThis is a **zero-tolerance** KPI (lower is better, target = 0). Zero incidents = perfect. The target has been fully met — no incidents were recorded.";
                }
                return "## Formula Interpretation\nThis is a **zero-tolerance** KPI (lower is better, target = 0). This means {$realisasi} incident(s) successfully breached the system, which is a complete failure against the zero-tolerance target. Explain which attacks got through and why.";
            }

            return "## Formula Interpretation\nThis is a **lower is better** KPI — fewer is better. Realisasi below target is good; realisasi above target means the limit was exceeded. Interpret the gap accordingly.";
        }

        if ($formula === 'exact target') {
            return "## Formula Interpretation\nThis is an **exact target** KPI — the realisasi must match the target precisely. Any deviation (above or below) reduces achievement. Interpret the gap accordingly.";
        }

        // Default: higher is better
        return "## Formula Interpretation\nThis is a **higher is better** KPI — exceeding the target is good, falling short is not. Interpret the gap accordingly.";
    }
}
