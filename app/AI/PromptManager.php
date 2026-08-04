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
        $outputFormat = $this->getOutputFormat($measurement);

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

        // Measurement-type-specific recommendation domain hints. These give
        // Gemini concrete categories of action items to draw from instead of
        // falling back to generic platitudes ("monitoring", "improve", "review").
        $recommendationDomain = $this->getRecommendationDomainHint($measurement);

        // KPI lifecycle stage. Tells Gemini whether to recommend SUSTAIN
        // actions (post-achievement) or CORRECTIVE actions (gap closing),
        // eliminating the vague "improve" advice that doesn't fit either.
        $lifecycleStage = $achievement >= 100
            ? 'SUSTAIN (post-achievement) — focus on stability, adoption, monitoring, continuous improvement'
            : 'CLOSE THE GAP (corrective) — focus on unblocking bottleneck, accelerating remaining milestones, mitigation';

        $isFullyAchieved = $achievement >= 100;

        return <<<PROMPT
# KPI Achievement Reasoning — Insight, NOT Summary

You are a senior executive KPI advisor to the CIO/CEO. Laravel has ALREADY
calculated every metric below using the company formula. Your job is NOT to
summarize the evidence — a clerical assistant could do that. Your job is to
produce executive-grade INSIGHT: explain the underlying business drivers,
assess operational implications, and prescribe concrete next actions.

## Hard Rules (do NOT break these)
- Do NOT recalculate, recompute, or question the achievement, score, target, realisasi, or status. They are final.
- Do NOT invent numbers. Use only the figures given below.
- Focus on the WHY (business causation), not the WHAT (evidence summary).
- Write in Indonesian (Bahasa Indonesia), professional, executive-friendly, analytical tone.
- This is an INSIGHT call, not an EVIDENCE-SUMMARY call. The user has read the evidence;
  they want your interpretation of what it MEANS, not a recap of what it SAYS.

## The Difference Between Summary and Insight (CRITICAL)
- ❌ SUMMARY (FORBIDDEN): "KPI tercapai karena UAT selesai dan Go-Live berhasil."
  This merely repeats facts already visible in the evidence.
- ✅ INSIGHT (REQUIRED): "Target KPI tercapai karena seluruh milestone kritikal
  (UAT dan Go-Live) selesai sesuai jadwal tanpa outstanding issue. Hal ini
  menunjukkan kesiapan implementasi telah memenuhi target operasional. Fokus
  berikutnya perlu diarahkan pada stabilitas layanan dan adopsi pengguna untuk
  memastikan manfaat bisnis tetap berkelanjutan."
- Insight answers: WHY did it happen, WHAT does it imply for the business,
  WHAT is the risk/opportunity, WHAT must leadership do next. Summary only
  restates WHAT happened.

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
- **Lifecycle stage for recommendations**: {$lifecycleStage}

{$formulaHint}

{$evidenceBlock}

{$recommendationDomain}

## Your Tasks

### 1. achieved_reason (INSIGHT, not summary)
Explain WHY this KPI reached (or is on track to reach) its target at the
BUSINESS and OPERATIONAL level — not just "evidence X was uploaded".

Structure your reasoning:
- **Driver**: name the concrete factor visible in the evidence that drove
  success (e.g. "seluruh milestone kritikal UAT dan Go-Live selesai sesuai
  jadwal tanpa outstanding issue", "transaksi tuntas tepat waktu karena
  stabilisasi gateway berhasil").
- **Implication**: state what this MEANS for the business / operation
  (e.g. "menunjukkan kesiapan implementasi telah memenuhi target operasional").
- **Risk/opportunity**: surface the NEXT risk or opportunity the executive
  should now attend to (e.g. "fokus berikutnya adalah stabilitas layanan dan
  adopsi pengguna agar manfaat bisnis berkelanjutan").

Do NOT just list which evidence documents exist. Do NOT repeat the file names
or stage labels verbatim — the user has already seen them. Interpret them.

If the KPI is NOT achieved, still surface any partial progress factors here,
but keep this field focused on what went RIGHT ( however small ).

### 2. not_achieved_reason (only when there is a gap)
Explain WHY this KPI has NOT yet reached its target. Be specific about what
is missing, delayed, incomplete, or blocking. Cite the SPECIFIC application
name / document / milestone that is absent.

If the KPI IS fully achieved, set this to an empty string "".

### 3. recommendations (SPECIFIC & ACTIONABLE, NOT generic)
Provide 3-6 recommendations. Each MUST be a concrete next step, not a platitude.
- ❌ FORBIDDEN: "Lakukan monitoring performa", "Tingkatkan kualitas", "Perbaiki sistem".
- ✅ REQUIRED: Specific action + scope + concrete metric / timeframe / owner
  when applicable.

Examples of SPECIFIC recommendations (use as inspiration, tailor to the
actual KPI and evidence):
- "Tingkatkan monitoring response time selama 30 hari pasca Go-Live untuk
  memastikan P95 < 2 detik."
- "Lakukan evaluasi bug berdasarkan tiket Helpdesk selama 60 hari pertama,
  target resolution rate ≥ 90%."
- "Review SLA dengan vendor setelah implementasi, fokus pada uptime 99.9%."
- "Ukur tingkat penggunaan modul ESS oleh pengguna dengan target adoption
  rate ≥ 80% pada Q berikutnya."
- "Lakukan user adoption measurement dengan survey NPS bulanan."
- "Identifikasi aplikasi [nama] yang masih dalam UAT — targetkan Go-Live
  sebelum akhir Q ini untuk menutup gap sebanyak [X] unit."

Use the recommendation domain hint above as a source of categories. Pick
3-6 concrete items that ACTUALLY fit the situation. Skip categories that
are not applicable.

## Quality Bar (self-check before emitting)
Before you write the JSON, re-read your draft and verify:
- [ ] The `achieved_reason` explains WHY at business level, not just lists
      what evidence was uploaded. If it only restates evidence → rewrite it.
- [ ] Each recommendation names a specific action, not a verb + vague noun.
- [ ] No recommendation is a generic platitude ("monitoring", "improve",
      "review", "optimize") without a concrete scope/metric/timeframe.
- [ ] Application-specific items reference the actual application name from
      the evidence (not "aplikasi X").
- [ ] Lifecycle stage is respected: sustain actions for achieved KPIs,
      corrective actions for unachieved ones.

## Required Output Format
Respond with ONLY a valid JSON object in exactly this format:

```json
{
    "achieved_reason": "Analisis mendalam mengapa KPI tercapai (driver + implication + risk/opportunity)...",
    "not_achieved_reason": "Analisis mengapa KPI belum tercapai; kosongkan jika status Achieved",
    "recommendations": [
        "Rekomendasi spesifik #1 dengan scope + metric/timeframe",
        "Rekomendasi spesifik #2 dengan scope + metric/timeframe",
        "Rekomendasi spesifik #3 dengan scope + metric/timeframe"
    ]
}
```

IMPORTANT: Return ONLY the JSON object, no additional text, no preface.
PROMPT;
    }

    /**
     * Measurement-type-specific hint that tells Gemini what CATEGORIES of
     * concrete actions are typically relevant for this kind of KPI. This is
     * the single most effective lever to push recommendations away from
     * generic platitudes toward actionable executive advice.
     */
    protected function getRecommendationDomainHint(Measurement $measurement): string
    {
        $name = strtolower($measurement->measurement ?? '');

        // Implementasi Sistem / System Implementation
        if (str_contains($name, 'implementasi sistem') || str_contains($name, 'system implementation') || str_contains($name, 'aplikasi')) {
            return <<<'HINT'
## Recommendation Domain Hint — System Implementation
For Implementasi Sistem KPIs, recommendations should draw from these concrete
categories (pick those that fit the situation, skip those that don't):

**Post Go-Live Stabilization (if any application is Go Live):**
- Monitoring response time / latency selama 30 hari pasca Go-Live (target P95 < X detik)
- Evaluasi bug dari tiket Helpdesk selama 60 hari pertama (target resolution rate ≥ 90%)
- Review SLA vendor / internal setelah implementasi (fokus uptime ≥ 99.9%)
- Chaos / load testing untuk mengukur batas kapasitas sebelum traffic puncak
- Patch dan security hardening pada minggu pertama produksi

**User Adoption (if application is Go Live but adoption not yet measured):**
- Ukur tingkat penggunaan modul oleh end user (target adoption rate ≥ 80% dalam Q berikut)
- User adoption survey dengan NPS bulanan
- Training / change-management plan untuk user yang belum adopt
- Identifikasi power user dan champion untuk socialize fitur baru

**Gap Closure (if some applications are still UAT / not yet Go Live):**
- Tentukan target tanggal Go-Live per aplikasi yang masih UAT
- Identifikasi open issues dari UAT dan assign owner + due date
- Planned Go-Live per aplikasi untuk menutup gap [X unit] di Q berikutnya
- Risk register untuk blocker UAT masing-masing aplikasi

**Governance & Continuous Improvement:**
- Post-implementation review (PIR) 90 hari pasca Go-Live
- Dokumentasi lesson learned untuk proyek implementasi berikutnya
- Update knowledge base / runbook operasional
HINT;
        }

        // Cybersecurity Incident
        if (str_contains($name, 'incident')) {
            return <<<'HINT'
## Recommendation Domain Hint — Cybersecurity Incident Count
For incident-count KPIs (lower is better, zero-tolerance):

**Incident Response & Forensics:**
- Post-incident review untuk setiap incident yang berhasil menembus (RCA dalam 7 hari)
- Threat hunting proaktif berdasarkan TTPs dari incident yang terjadi
- Patch prioritas untuk vektor serangan yang berhasil menembus (SLA 72 jam)

**Detection Capability:**
- Tuning rule SIEM / IDS untuk mengurangi false negative (target detection rate ≥ 95%)
- Deployment EDR pada endpoint yang belum tercover (target coverage 100%)
- Integrasi threat intelligence feed untuk meningkatkan detection

**Prevention:**
- Review firewall policy / WAF rule, fokus pada action "allowed" yang seharusnya "blocked"
- Hardening konfigurasi server exposure (close port yang tidak perlu)
- Phishing awareness training (target completion ≥ 95% karyawan)

**Governance:**
- Update IR playbook berdasarkan incident type yang dominan
- SLA review: Mean Time To Detect (MTTD) < 30 menit, Mean Time To Respond (MTTR) < 4 jam
HINT;
        }

        // Cybersecurity general
        if (str_contains($name, 'cybersecurity') || str_contains($name, 'cyber security') || str_contains($name, 'keamanan')) {
            return <<<'HINT'
## Recommendation Domain Hint — Cybersecurity Compliance
**Compliance:**
- Gap analysis against ISO 27001 / regulasi yang relevan (target close 100% major NC)
- Remediation plan untuk setiap non-conformance, owner + due date
- Internal audit follow-up untuk verify effective closure

**Control Effectiveness:**
- Control effectiveness review tiap kontrol (test design + operating effectiveness)
- Remediation plan untuk control yang gap
- Periodic vulnerability scan (monthly) — target critical vuln tuntas ≤ 30 hari
HINT;
        }

        // Payment
        if (str_contains($name, 'payment') || str_contains($name, 'pembayaran')) {
            return <<<'HINT'
## Recommendation Domain Hint — Payment System
**Reliability:**
- Monitoring success rate gateway tiap jam (target ≥ 99.5%)
- Failover test untuk payment gateway tiap kuartal
- Reconcile settlement harian (target variance ≤ 0.01%)

**Adoption / Volume:**
- Promosi channel digital untuk meningkatkan transaction volume target [X%]
- Incentive program untuk merchant sign-up (target net new [X] merchant / Q)
HINT;
        }

        // Investment
        if (str_contains($name, 'investment') || str_contains($name, 'investasi')) {
            return <<<'HINT'
## Recommendation Domain Hint — IT Investment
**Utilization:**
- Review underutilized budget line pada Q berikutnya (target utilization ≥ 90%)
- Capex vs Opex rebalancing untuk tax efficiency
- ROI tracking tiap proyek investasi besar (> Rp X M)

**Pipeline:**
- Pipeline investasi Q berikutnya dengan target allocation [X%]
HINT;
        }

        // Default
        return <<<'HINT'
## Recommendation Domain Hint — Generic
Pick concrete actions from these categories that ACTUALLY fit the KPI:
- Stabilization / monitoring (with concrete metric + timeframe)
- Adoption / utilization (with target %)
- Gap closure (with owner + due date + target delta)
- Governance / review (with cadence: weekly / monthly / quarterly)
- Capability building (with target coverage)
HINT;
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

## CRITICAL: Application Identification + Stage
This KPI counts the number of UNIQUE applications/systems that have reached
**Go Live / production deployment** within the period.

Each evidence file documents one or more applications at a specific stage:
  - UAT (User Acceptance Testing) — NOT yet go live
  - Go Live / Production — already deployed to production
  - Development / Testing — NOT yet go live

You MUST return TWO arrays:

1. `applications` — every distinct application/system identified in this
   evidence (regardless of stage). Used for display and audit trail.
2. `go_live_applications` — the SUBSET of `applications` whose stage in THIS
   evidence is **Go Live / Production deployment**. ONLY these contribute to
   the realisasi count.

### Stage detection rules
- Evidence containing a "Berita Acara Go Live", "Go Live", "Production
  Deployment", "deployed to production", or similar wording → stage = go live
  → put the application in BOTH `applications` AND `go_live_applications`.
- Evidence that is only UAT sign-off, test scripts, test results, development
  status, or screenshots of testing → stage = UAT/testing → put the application
  in `applications` ONLY. Do NOT put it in `go_live_applications`.
- When in doubt, do NOT add to `go_live_applications` (prefer under-counting
  over over-counting).

### Naming rules
- Use the CANONICAL product/application name as written in the document
  (e.g. "Klaim Kacamata", NOT "UAT Klaim Kacamata" or "Berita Acara Klaim Kacamata").
- The document title or filename often contains stage words (UAT, Berita Acara,
  Go Live, Test Script) — those are stages, NOT part of the application name.
- ALWAYS prefer the SHORTEST, most distinctive form of the application name.
  Return "Klaim Kacamata" rather than "Sistem Layanan Reimburse Kesehatan
  (Modul Klaim Kacamata)" when both refer to the same application — the
  canonical short name is what gets de-duplicated across evidence files.
- Do NOT include channel/wrapper words in the canonical name (e.g. "ESS",
  "aplikasi", "sistem", "layanan", "modul", "reimburse", "kesehatan" should
  not be the leading part of the name if a more specific product name exists
  inside the document).
- Two evidence files for the SAME application (even if descriptions differ
  slightly) MUST return the SAME canonical name so Laravel de-duplicates them
  into one application.
- If multiple distinct applications are covered in one document, list each once.
- If no specific application name is identifiable, return empty arrays for both.

### realisasi value
- `realisasi` MUST equal the number of entries in `go_live_applications`
  (i.e. count of applications that reached Go Live in THIS evidence).
- A UAT-only evidence therefore has `realisasi: 0` and `go_live_applications: []`,
  even though `applications` may list the tested application(s).
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
     * Get expected output format.
     *
     * For Implementasi Sistem measurements, the `applications` field is required
     * so Laravel can count UNIQUE applications across multiple evidence files.
     */
    protected function getOutputFormat(?Measurement $measurement = null): string
    {
        $isImplementasiSistem = $measurement
            && (
                str_contains(strtolower($measurement->measurement), 'implementasi sistem')
                || str_contains(strtolower($measurement->measurement), 'system implementation')
            );

        if ($isImplementasiSistem) {
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
    "applications": [
        "Canonical application name #1",
        "Canonical application name #2"
    ],
    "go_live_applications": [
        "Canonical application name #1"
    ],
    "analysis": "Detailed analysis of the evidence...",
    "recommendations": [
        "First actionable recommendation",
        "Second actionable recommendation",
        "Third actionable recommendation"
    ]
}
```

IMPORTANT NOTES:
- `applications` is REQUIRED — list every distinct application/system identified
  in this evidence using its canonical short name.
- `go_live_applications` is REQUIRED — list only the applications whose stage in
  THIS evidence is Go Live / production deployment. It must be a SUBSET of
  `applications`. UAT-only / development / testing evidence must use `[]`.
- `realisasi` MUST equal `count(go_live_applications)` (so a UAT-only evidence
  has realisasi = 0, not 1).
- If no application can be identified, return `"applications": []`,
  `"go_live_applications": []`, and `realisasi: 0`.
- Return ONLY the JSON object, no additional text.
PROMPT;
        }

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
    "applications": [],
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
