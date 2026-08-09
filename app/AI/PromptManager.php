<?php

namespace App\AI;

use App\DTO\DocumentExtractionDTO;
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
     * Generate the prompt for the Strategic Advisor feature.
     *
     * Unlike the evidence-analysis prompt (which reads one specific upload
     * against a known KPI), this prompt tells Gemini to:
     *   1. Analyze a strategic reference document (RJPP / MPTI / research
     *      paper) uploaded by the user — structure provided as DocumentExtractionDTO.
     *   2. Recommend strategic actions, grounded both in the document AND in
     *      the broader industry best-practice (via Google Search grounding
     *      enabled separately in GeminiService::analyzeWithSearch()).
     *   3. Surface current internet trends relevant to the document's domain.
     *
     * The text-only prompt is sent without `responseMimeType: application/json`
     * because google_search_retrieval is incompatible with structured-output
     * mode. The prompt therefore ORDER the model to emit pure JSON; the
     * service parses it out of the resulting text via `extractJson()`.
     */
    public function generateStrategicAdvisorPrompt(DocumentExtractionDTO $dto): string
    {
        $documentType = $dto->documentType ?: 'tidak diketahui';
        $company      = $dto->company ?: 'tidak terdeteksi';
        $period       = $dto->period ?: 'tidak terdeteksi';
        $totalPages   = $dto->totalPages > 0 ? (string) $dto->totalPages : 'tidak diketahui';
        $execSummary  = $dto->executiveSummary ?: '(ringkasan eksekutif tidak dapat diekstrak)';

        // Cap list size supaya prompt tidak bengkak pada dokumen besar (RJPP bisa
        // punya 100+ initiative). AI tidak butuh seluruh list — cukup sample
        // yang representatif untuk konteks analisis strategis. Pengurangan ini
        // sekaligus menurunkan risiko `MALFORMED_FUNCTION_CALL` yang dipicu
        // oleh model kesulitan menyusun JSON di tengah prompt panjang.
        $kpiList        = $this->formatExtractedKpis(array_slice($dto->kpis, 0, 15));
        $initiativeList = $this->formatExtractedInitiatives(array_slice($dto->initiatives, 0, 20));
        $soList         = $this->formatExtractedStrategicObjectives($dto->strategicObjectives);

        $errorBlock = $dto->errorMessage
            ? "\n\n## PERINGATAN EKSTRAKSI\nProses ekstraksi PDF melaporkan error non-fatal: {$dto->errorMessage}\nGunakan saja informasi struktur yang berhasil diekstrak, dan jangan mengarang data yang tidak ada."
            : '';

        // NOTE: PHP heredoc `<<<PROMPT` does NOT allow function calls inside
        // `{...}` interpolation — only expressions starting with `$` are
        // parsed. `{count($arr)}` would be interpreted as literal text plus
        // `$arr` array interpolation, raising "Array to string conversion".
        // Compute the counts BEFORE the heredoc and use plain variables.
        $soCount        = count($dto->strategicObjectives);
        $kpiCount       = count($dto->kpis);
        $initiativeCnt  = count($dto->initiatives);

        return <<<PROMPT
Anda adalah Senior Strategic Advisor AI untuk BUMN Indonesia, spesialis pada perencanaan strategis jangka panjang (RJPP) dan master plan teknologi informasi (MPTI). Anda memiliki akses ke:
1. Ringkasan struktur dokumen strategis yang di-upload user (ditampilkan di bawah — bersifat non-sensitif, hanya struktur).
2. Google Search grounding untuk mengambil tren industri, perkembangan regulasi, dan best-practice terkini dari internet.

## Ringkasan Dokumen yang Di-upload
- **Tipe dokumen**: {$documentType}
- **Perusahaan**: {$company}
- **Periode**: {$period}
- **Jumlah halaman**: {$totalPages}
- **File sumber**: {$dto->sourceFile}

### Ringkasan Eksekutif
{$execSummary}

### Sasaran Strategis Terdeteksi ({$soCount} item)
{$soList}

### KPI Terdeteksi ({$kpiCount} item)
{$kpiList}

### Inisiatif Strategis Terdeteksi ({$initiativeCnt} item)
{$initiativeList}
{$errorBlock}

## Tugas Anda
1. **Analysis** — Analisis kritis dokumen ini dalam 2-3 paragraf naratif. Bahas:
   - Kekuatan struktur strategisnya (alignment visi→sasaran→KPI→inisiatif).
   - Gap / kelemahan yang mencolok (mis. perspective BSC yang under-represented, KPI ambigious, formula tidak konsisten, inisiatif yang tidak terhubung ke KPI manapun).
   - Konsistensi dengan praktik RJPP/MPTI BUMN Indonesia pada umumnya.

2. **Recommendations** — Berikan 4-8 rekomendasi strategis konkret. Setiap rekomendasi WAJIB grounded pada apa yang ADA di dokumen ATAU apa yang MISSING tapi lazim ada pada RJPP/MPTI best-in-class BUMN sebanding. Untuk setiap rekomendasi, sertakan:
   - `title` (ringkas, action-oriented)
   - `rationale` (1-2 kalimat, mengaitkan ke dokumen atau best-practice)
   - `priority` ("high" | "medium" | "low")
   - `suggested_perspective` (perspective BSC yang relevan: "Financial", "Customer", "Internal Process", atau "Learning & Growth")
   - `suggested_initiative` (saran nama inisiatif konkret yang bisa diadopsi, jangan generik)

3. **Popular Trends** — Berikan 3-6 tren terkini yang relevan dengan domain dokumen tersebut (mis. AI, cybersecurity, ESG, digital banking, payment, regulatory compliance, Industry 4.0, sustainability reporting). JIKA Anda memiliki akses live web (Google Search grounding aktif), MANFAATKAN itu untuk MENGUTIP perkembangan nyata terkini (regulasi, laporan industri, luncuran produk). JIKA TIDAK (mode non-grounded), gunakan knowledge dari training cut-off Anda dan tambahkan disclaimer singkat pada `source_hint`: "estimasi berdasarkan knowledge cutoff, bukan live web". Jangan mengarang angka/tanggal. Untuk setiap trend:
   - `trend` (nama tren, ringkas)
   - `relevance_to_document` (1 kalimat: kenapa tren ini penting bagi dokumen/perusahaan ini)
   - `source_hint` (sumber atau kata kunci pencarian, mis. "OJK regulasi AI 2025", "Gartner Hype Cycle 2025", atau nama publikasi — HINDARI URL palsu)

## Aturan Ketat
- Dilarang mengarang angka / tanggal / nama yang tidak ada di dokumen.
- Dilarang mengarang URL. `source_hint` cukup berupa kata kunci / nama publikasi.
- Hindari platitude generik ("tingkatkan performa", "lakukan monitoring"). Tiap rekomendasi harus spesifik dan actionable.
- Untuk tren: jika live grounding tersedia, kutip perkembangan nyata. Jika tidak, gunakan knowledge cutoff dan beri disclaimer di `source_hint` ("estimasi berdasarkan knowledge cutoff").
- Output WAJIB berupa satu JSON object VALID, tanpa pembungkus markdown ` ```json ` dan tanpa teks tambahan di luar JSON.

## Format Output
{
  "analysis": "string 2-3 paragraf",
  "recommendations": [
    {
      "title": "",
      "rationale": "",
      "priority": "high|medium|low",
      "suggested_perspective": "Financial|Customer|Internal Process|Learning & Growth",
      "suggested_initiative": ""
    }
  ],
  "popular_trends": [
    {
      "trend": "",
      "relevance_to_document": "",
      "source_hint": ""
    }
  ]
}

PENTING: Kembalikan HANYA JSON object, tanpa markdown wrapper dan tanpa teks di luar JSON.
PROMPT;
    }

    /**
     * Format list of KPIs extracted from a strategic document as a compact
     * bulleted block for embedding inside the strategic-advisor prompt.
     */
    protected function formatExtractedKpis(array $kpis): string
    {
        if (empty($kpis)) {
            return "(tidak ada KPI yang berhasil diekstrak dari dokumen)";
        }
        $lines = [];
        foreach ($kpis as $k) {
            $code = $k['code'] ?? '—';
            $meas = $k['measurement'] ?? '';
            $unit = $k['unit'] ?? '';
            $tgt  = $k['target'] ?? '';
            $w    = $k['weight'] ?? '';
            $lines[] = "- [{$code}] {$meas} | unit: {$unit} | target: {$tgt} | weight: {$w}";
        }
        return implode("\n", $lines);
    }

    /** Format initiative list as numbered bullet block. */
    protected function formatExtractedInitiatives(array $initiatives): string
    {
        if (empty($initiatives)) {
            return "(tidak ada inisiatif yang berhasil diekstrak dari dokumen)";
        }
        $lines = [];
        $i = 1;
        foreach ($initiatives as $init) {
            $code = $init['code'] ?? '';
            $name = $init['name'] ?? '';
            $prefix = $code !== '' ? "[{$code}] " : '';
            $lines[] = "{$i}. {$prefix}{$name}";
            $i++;
        }
        return implode("\n", $lines);
    }

    /** Format strategic objectives as bullet block. */
    protected function formatExtractedStrategicObjectives(array $sos): string
    {
        if (empty($sos)) {
            return "(tidak ada sasaran strategis yang berhasil diekstrak dari dokumen)";
        }
        $lines = [];
        foreach ($sos as $s) {
            $code = $s['code'] ?? '';
            $name = $s['name'] ?? '';
            $persp = $s['perspective'] ?? '';
            $prefix = $code !== '' ? "[{$code}] " : '';
            $suffix = $persp !== '' ? " — perspective: {$persp}" : '';
            $lines[] = "- {$prefix}{$name}{$suffix}";
        }
        return implode("\n", $lines);
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
        $definition = strtolower($measurement->definition ?? '');

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

        // SLA / Infrastructure Availability (must be checked before generic
        // payment because the sharing-KPI name contains "pembayaran").
        if ($this->isSlaAvailability($name) || $this->isSlaAvailability($definition)) {
            return <<<'HINT'
## Recommendation Domain Hint — SLA / Infrastructure Availability
For availability/uptime KPIs (realisasi is a %), recommendations should draw
from these concrete categories (pick those that fit, skip those that don't):

**Root Cause of Downtime (if any application is below target):**
- RCA untuk aplikasi dengan uptime < target SLA (identifikasi penyebab dominan: network, server, app crash, maintenance)
- Patch / hotfix untuk bug yang menyebabkan crash berulang (SLA 72 jam)
- Failover / high-availability review untuk aplikasi single-point-of-failure

**Monitoring & Alerting:**
- Tuning threshold alert PRTG (warning < 99.5%, critical < 99%) agar incident terdeteksi sebelum SLA breached
- Tambah sensor monitoring untuk aplikasi yang belum ter-cover 100% (target coverage 100%)
- Dashboard availability real-time untuk stakeholder bisnis (refresh tiap 5 menit)

**Capacity & Resilience:**
- Capacity planning review tiap kuartal (CPU, memory, network bandwidth) target utilization < 70%
- Failover test / DR drill tiap kuartal (target RTO ≤ 4 jam, RPO ≤ 15 menit)
- Redundansi link network / power supply untuk eliminasi single point of failure

**SLA Governance:**
- Review SLA report mingguan dengan vendor / tim infrastruktur, fokus aplikasi yang trend-nya menurun
- SLA penalty / incentive clause review pada kontrak vendor
- Standardisasi window maintenance di luar jam produktif untuk minimisasi planned downtime
HINT;
        }

        // Capex Realization / Realisasi Nilai Investasi
        if ($this->isInvestmentRealisasiName($name, $definition)) {
            return <<<'HINT'
## Recommendation Domain Hint — Capex Realization
For Capex realization KPIs (realisasi is a %), recommendations should draw
from these concrete categories (pick those that fit the situation, skip
those that don't):

**Procurement Acceleration (if items are behind schedule):**
- Percepatan proses tender/SPK untuk item Capex yang masih pada tahap Kajian/TOR
  (target SPK terbit ≤ 30 hari dari evaluasi teknis selesai)
- Klarifikasi teknis dengan vendor untuk item yang tertunda (SLA klarifikasi
  ≤ 14 hari)
- Eskalasi ke manajemen untuk item dengan penyimpangan > 30 hari dari jadwal
  RKAP

**Budget Execution & Realization:**
- Review item Capex dengan realization < 50% — identifikasi bottleneck
  (approval, vendor, teknis) dengan owner + due date
- Realokasi budget dari item underspent ke item yang mendesak (melalui RKAP
  revision/Q1)
- Tracking progress payment per SPK (target payment ≤ 30 hari setelah
  progress verification)

**Technical Evaluation & Klarifikasi:**
- Standardisasi template Kajian/TOR untuk mempercepat evaluasi teknis
  (target waktu evaluasi ≤ 7 hari kerja per dokumen)
- Schedule klarifikasi teknis proaktif untuk menghindari revisi berulang
  (target klarifikasi 1 cycle, tidak lebih dari 2 cycles)

**Vendor & Contract Management:**
- Review SLA vendor untuk item yang sudah SPK — target delivery sesuai
  kontrak dengan penalty clause
- Pre-qualification vendor pool untuk mengurangi waktu tender (target
  cycle time tender ≤ 45 hari)

**RKAP Alignment & Governance:**
- Monthly review Capex realization vs RKAP dengan status per item (green
  ≥ 80%, amber 50-79%, red < 50%)
- Update RKAP quarterly berdasarkan actual realization dan revised forecast
- Post-investment review untuk item yang sudah deployed (ROI verification
  dalam 90 hari)
HINT;
        }

        // Investment (generic)
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

        // Project Management: Traceability — lifecycle documentation completeness
        // for IT projects (Kajian → TOR → SPK → Implementasi → BAST). Must be
        // checked BEFORE the generic fallback.
        if (str_contains($name, 'project management') || str_contains($name, 'traceability')) {
            // 3-stage Enterprise Architecture Project Management KPI uses a
            // specific OMTI 2026 lifecycle (Perencanaan/Development/BAST).
            if (
                str_contains($name, 'pilar security')
                && str_contains($name, 'pilar spbe')
            ) {
                return $this->getProjectManagementEARecommendationHint();
            }

            return <<<'HINT'
## Recommendation Domain Hint — Project Management Traceability
For Project Management: Traceability KPIs (lifecycle doc completeness),
recommendations should draw from these concrete categories (pick those that
ACTUALLY fit the situation, skip those that don't):

**Lifecycle Gap Closure (if some projects are stuck at an early stage):**
- Push project yang masih Kajian ke tahap TOR/KAK dalam ≤ 30 hari (target SPK
  terbit ≤ 60 hari dari TOR)
- Identifikasi proyek yang belum punya SPK dan eskalasi ke PJM/PMO (SLA
  kontrak terbit ≤ 45 hari dari TOR)
- Schedule klarifikasi teknis untuk proyek tertunda (target klarifikasi ≤ 14 hari)
- Targetkan BAST/Go Live sebelum akhir Q untuk menutup gap lifecycle

**PMO Governance & Cadence:**
- Pertemuan PMO mingguan dengan status per proyek (red/amber/green per stage)
- RACI matrix per proyek — owner untuk setiap tahap lifecycle (Kajian → BAST)
- Risk register per proyek dengan SLA mitigasi ≤ 7 hari untuk blocker
- Standar naming convention proyek supaya traceability cross-evidence akurat

**Vendor & Contract Management:**
- Review SLA vendor untuk proyek yang sudah SPK (target delivery sesuai
  kontrak, penalty clause untuk delay)
- Pre-qualification vendor pool untuk mempercepat procurement (target
  cycle time tender ≤ 45 hari)
- Kick-off meeting ≤ 14 hari setelah SPK terbit untuk mulai Implementasi

**Adoption / Stabilization (if any proyek sudah BAST/Go Live):**
- Post-implementation review 90 hari pasca BAST (target uptime ≥ 99.5%)
- User adoption survey NPS bulanan (target ≥ 80)
- Knowledge transfer + runbook update ≤ 30 hari pasca Go Live

**Lifecycle Documentation Discipline:**
- Centralized Project Charter repository (single source of truth per proyek)
- Setiap proyek wajib punya 5 dokumen lifecycle lengkap sebelum dianggap done
- Audit traceability tiap kuartal per proyek aktif
HINT;
        }

        // Jumlah proses supporting unit yang menggunakan AI (OMTI 2026 #8).
        // Must be checked BEFORE generic AI thanks to the 'supporting unit'
        // keyword so the count-based guidance applies.
        if (str_contains($name, 'supporting unit')) {
            return <<<'HINT'
## Recommendation Domain Hint — Supporting Unit AI Adoption
For count-based "supporting unit AI" KPIs (realisasi = number of unique
supporting-unit processes live on AI), recommendations should draw from these
concrete categories (pick those that ACTUALLY fit the situation, skip those
that don't):

**Process Adoption / Stabilization (if an AI process has Go-Live):**
- Monitor penggunaan AI oleh end user pada process [X] selama 30 hari pasca
  Go-Live (target adoption rate ≥ 80% user aktif)
- Ukur metrik bisnis impact: error rate / throughput / cycle time (target
  improvement ≥ X% dalam 60 hari)
- Stability review: jumlah incident / bug pasca Go-Live (target ≤ Y
  incident / minggu dalam 30 hari pertama)
- User training / change-management wajib ≤ 14 hari pasca Go-Live

**POC → Production Gap Closure (if AI process masih POC / UAT / development):**
- Targetkan Go-Live [X] sebelum akhir Q berikutnya untuk menutup gap
- Schedule UAT sign-off ≤ 30 hari dari development selesai
- Risk register untuk blocker UAT (data quality, model accuracy, integrasi)
  dengan SLA mitigasi ≤ 7 hari
- Owner jelas per AI process: product owner + tech lead + business owner

**Pipeline Scale-up (jika realisasi < target tahunan):**
- Identifikasi supporting-unit process candidate berikutnya (prioritas pada
  proses dengan volume tinggi + repetitive: Mid Year Survey, Recruitment,
  E-invoice, Helpdesk Ticketing, Contract Review)
- RACI per process & kickoff POC + SPK ≤ 45 hari
- Target Go-Live process berikutnya dalam Q berikutnya

**Governance & Quality:**
- AI Governance review bulanan: model performance, drift, fairness, security
  (target model accuracy ≥ threshold; drift ≤ X% per bulan)
- Audit trail & explainability: log prediksi AI ≥ 90% untuk audit (e.g.      
  resume screening decisions log)
- Security review AI: data privacy / model poisoning / prompt injection
  (target 0 major incident)

**Business Outcome:**
- ROI tracking per AI process: cost saving / time saving / accuracy gain
  (laporan ≤ 90 hari pasca Go-Live)
- Adoption NPS per supporting unit (target ≥ 70)
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
        // Also check the definition so measurements whose name doesn't
        // contain SLA keywords but whose definition does (e.g. "Percepatan
        // proses pembayaran (sharing KPI)" whose definition mentions SLA)
        // still route to the correct prompt.
        $definition = strtolower($measurement->definition ?? '');

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

        // SLA / infrastructure availability must be checked BEFORE payment,
        // because the measurement name "Percepatan proses pembayaran (sharing
        // KPI)" contains the word "pembayaran" and would otherwise be
        // misrouted to the generic payment prompt.  We check BOTH the name
        // and the definition so that definition-level SLA keywords are
        // sufficient to route correctly.
        if ($this->isSlaAvailability($name) || $this->isSlaAvailability($definition)) {
            return $this->getSlaAvailabilityPrompt();
        }

        if (str_contains($name, 'payment') || str_contains($name, 'pembayaran')) {
            return $this->getPaymentPrompt();
        }

        // Capex realization / Realisasi Nilai Investasi must be checked
        // BEFORE the generic investment routing so that this specific KPI
        // (which extracts line-items with budget vs realized) gets the
        // specialised prompt, while generic investment KPIs keep the old
        // prompt.
        if ($this->isInvestmentRealisasiName($name, $definition)) {
            return $this->getInvestmentRealisasiPrompt();
        }

        if (str_contains($name, 'investment') || str_contains($name, 'investasi')) {
            return $this->getInvestmentPrompt();
        }

        // Jumlah proses supporting unit yang menggunakan AI (OMTI 2026 #8).
        // Must be routed BEFORE the generic AI prompt, because this is a
        // COUNT-style KPI (realisasi = count of unique AI supporting unit
        // processes that reached Go Live in THIS evidence, reusing the
        // applications + go_live_applications structure) — NOT the generic
        // "AI implementation" KPI which focuses on technology/model metrics.
        if (str_contains($name, 'supporting unit')) {
            return $this->getAISupportingUnitPrompt();
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
            // The 3-stage EA Project Management KPI uses a different
            // lifecycle than the generic EA prompt. Detect it from the
            // very specific OMTI 2026 wording "Pilar Security ... Pilar
            // SPBE" so it never collides with a plain EA measurement.
            if (
                str_contains($name, 'pilar security')
                && str_contains($name, 'pilar spbe')
            ) {
                return $this->getProjectManagementEAPrompt();
            }

            return $this->getEnterpriseArchitecturePrompt();
        }

        if (str_contains($name, 'project management') || str_contains($name, 'traceability')) {
            return $this->getProjectManagementTraceabilityPrompt();
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

    /**
     * Detect SLA / infrastructure availability measurements.
     *
     * These measurements rely on PRTG / monitoring reports that show uptime
     * percentages for applications and/or network devices. We match on
     * keyword combinations rather than a single token so that a name like
     * "Percepatan proses pembayaran (sharing KPI)" — whose definition is about
     * SLA pemenuhan 98%/92% — still routes here once the definition fields are
     * visible in the prompt. The keyword set covers both Indonesian and
     * English phrasings observed in real evidence.
     */
    protected function isSlaAvailability(string $name): bool
    {
        $keywords = ['sla', 'uptime', 'availability', 'ketersediaan', 'infrastruktur', 'infrastructure'];

        foreach ($keywords as $kw) {
            if (str_contains($name, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Specialised prompt for SLA / infrastructure availability KPIs.
     *
     * Unlike completion-style KPIs (realisasi = count of systems), this KPI is
     * a PERCENTAGE: the realisasi is the average uptime across all applications
     * (or network devices) documented in the evidence, for the period covered.
     *
     * Example: an SLA report covering 10 applications with uptime values of
     * 99.9%, 99.5%, 100%, ... yields realisasi = mean of all those values.
     */
    protected function getSlaAvailabilityPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: SLA / Infrastructure Availability

You are an IT operations auditor analyzing a Service Level Agreement (SLA)
report (typically a PRTG Network Monitor export) for an Availability KPI.

This KPI measures the AVAILABILITY (uptime %) of applications and/or network
infrastructure. The realisasi is a PERCENTAGE, not a count.

## CRITICAL: What to Extract
Read the evidence and extract the UPTIME percentage for EVERY monitored target
(application, server, or network device) you can identify. SLA reports usually
present each target with an "Uptime Stats" block that looks like:

    Up:    99.95 % [179d 23h 50m 02s]
    Down:  0.05 % [00s]

The "Up" value is the uptime percentage for that target. Extract it for every
target in the document.

## Aggregation Rules
1. List every distinct monitored target and its uptime value in `sla_targets`.
2. `realisasi` = the simple ARITHMETIC MEAN (average) of all extracted uptime
   values, rounded to 2 decimal places.
   - Example: if 10 applications report uptimes of 99.9, 100, 98.5, 92.1,
     100, 99.8, 100, 95.0, 99.9, 100 → mean = 98.52 → realisasi = 98.52.
3. If the evidence groups data by month within the reporting period, average
   ALL monthly values across ALL targets together (do not average per-month
   first unless the report only contains one month).
4. If a target shows "100 %" uptime, use exactly 100.
5. If a target shows "<1 %" packet loss with no explicit uptime, infer uptime
   as 100 (since <1% loss rounds to 100% availability on a 60s ping interval).

## Evidence Validation
- Set `evidence_valid` to false if the document is not an SLA / availability
  report (e.g. it is an invoice, a contract, or an unrelated document).
- Set `evidence_valid` to false if NO uptime value can be extracted at all.
- A report covering only a SUBSET of the period (e.g. one month instead of the
  full quarter) is still VALID — process it and average whatever is present.

## Naming Rules for sla_targets
- Use the CANONICAL application / device name as written (e.g. "Aplikasi ESS",
  "DC Core Karawang Gateway", "SAP ERP S/4HANA").
- Strip document-type prefixes ("SLA APLIKASI", "Report", "Ping") from the name.
- Each row in `sla_targets` must be an object: { "name": "...", "uptime": 99.95 }.

## realisasi Value
- `realisasi` MUST be the MEAN uptime across all `sla_targets`, as a number
  between 0 and 100 (NOT 0–1). Example: 98.52, not 0.9852.
- It represents the achieved availability percentage for the period.
PROMPT;
    }

    /**
     * Detect Capex-realization / investment-realization measurements.
     *
     * These measurements track the percentage of Capex procurement program
     * achievement against RKAP. They produce `investment_items` line-item
     * data (budget vs realized) that Laravel aggregates into a single
     * overall realization percentage. Keyed on "realisasi"+"investasi",
     * "capex", "pengadaan capex", or "RKAP" in the name or definition.
     */
    protected function isInvestmentRealisasiName(string $name, string $definition): bool
    {
        $combined = $name . ' ' . $definition;

        if (str_contains($combined, 'capex')) {
            return true;
        }

        if (str_contains($combined, 'pengadaan')) {
            return true;
        }

        if (str_contains($combined, 'rkap')) {
            return true;
        }

        if (str_contains($name, 'realisasi') && str_contains($name, 'investasi')) {
            return true;
        }

        return false;
    }

    /**
     * Specialised prompt for Capex Realization / Realisasi Nilai Investasi KPI.
     *
     * Unlike the generic investment prompt (which focuses on ROI / efficiency
     * metrics), this prompt instructs Gemini to extract individual investment
     * line-items with their budget, realized amount, and realization
     * percentage, then compute the overall Capex realization percentage.
     */
    protected function getInvestmentRealisasiPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Capex Realization / Realisasi Nilai Investasi

You are a financial auditor analyzing evidence for a Capex Realization KPI.
This KPI measures the **percentage of Capex procurement program achievement**
against the company's RKAP (Rencana Kerja dan Anggaran Perusahaan).

Focus on:
- Investment line-items with budget and realized/spent amounts
- Per-item realization percentage
- Overall Capex realization percentage
- Project status per investment item (proposal, evaluation, procurement,
  implementation, deployment)
- Evidence of action plan execution: pengajuan investasi, evaluasi dan
  klarifikasi teknis tepat waktu

## CRITICAL: Investment Item Extraction

Each evidence file is typically one of two categories:

### 1. Investment Monitoring Report (Laporan Monitoring Investasi)
This is the PRIMARY evidence. It contains a summary table with ALL Capex
investment items for the period, including:
- Item name / project description (often with FA number)
- Budget allocated (Rupiah)
- Amount realized/spent (Rupiah)
- Realization percentage per item
- Status per item

For monitoring reports:
- Extract EVERY investment line-item you can identify → `investment_items`
- `realisasi` = the OVERALL Capex realization percentage shown in the report
  (if explicitly stated), otherwise compute it as
  sum(realized) / sum(budget) × 100

### 2. Individual Investment Project Documents
These are SUPPORTING evidence — each documents ONE specific investment item
at a particular stage:
- **Kajian / Study** (Technical Assessment): pengajuan investasi / evaluasi
  teknis stage
- **TOR** (Terms of Reference): klarifikasi teknis / specification stage
- **SPK** (Surat Perintah Kerja): procurement / work order stage
- **Downtime notification / deployment notice**: implementation / deployment
- **Invoice / payment evidence**: payment completed

For individual project documents:
- Extract the specific investment item as ONE entry → `investment_items`
  - `name`: the project / FA number
  - `budget`: the budget allocated to this item (if stated)
  - `realized`: the amount realized/spent so far for this item (if stated)
  - `percentage`: the realization percentage for this item
    (if SPK/procurement has started but no payment yet, estimate: Kajian/TOR
    = 10-20%, SPK issued = 30-50%, implementation in progress = 50-80%,
    deployment/paid = 90-100%)
  - `status`: current stage (e.g. "Kajian", "TOR", "SPK", "Implementasi",
    "Deploy/Selesai")
- `realisasi` = the realization percentage for THIS item
  (NOT the overall Capex percentage — Laravel will aggregate across all
  evidence files).

## Evidence Validation
- Set `evidence_valid` to false if the document is NOT related to IT
  investment / Capex procurement (e.g. an unrelated invoice, a contract
  for services, or a general company memo).
- Set `evidence_valid` to false if NO investment item can be identified.

## Naming Rules for investment_items
- Use the CANONICAL project name as written in the document, including the
  FA number when available (e.g. "Core Switch Karawang (FA3299 & FA3669)").
- Strip document-type prefixes ("Kajian", "TOR", "SPK", "NDE", "Laporan")
  from the item name — those are document types, not project names.
- Two evidence files for the SAME investment item (even if descriptions
  differ slightly) MUST return the SAME canonical name so Laravel can
  de-duplicate them.
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

    /**
     * Specialised prompt for "Jumlah proses supporting unit yang menggunakan
     * AI" KPI (OMTI 2026 #8). This is a COUNT-style KPI: the realisasi is
     * the number of UNIQUE supporting-unit processes that use AI and have
     * reached Go-Live in THIS evidence. It reuses the
     * `applications` + `go_live_applications` structure from the
     * Implementasi Sistem prompt, where each "application" is a
     * supporting-unit process which has been augmented with AI.
     *
     * Known supporting-unit processes to look for (per OMTI 2026
     * initiatives): Mid Year Survey, Proses Recruitment, E-invoice — but
     * any supporting-unit process implementing AI should be captured.
     */
    protected function getAISupportingUnitPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Supporting Unit AI Count

You are an IT auditor analyzing evidence for the KPI
"Jumlah proses supporting unit yang menggunakan AI" (count of supporting-unit
processes that have adopted Artificial Intelligence).

This KPI counts the number of UNIQUE supporting-unit processes (business
processes in supporting units such as HR, Finance, Procurement, Risk, etc.)
that have implemented AI to actively support automation, analysis,
decision-making, efficiency, or service quality — and have reached Go-Live
in production within this period.

## CRITICAL: Process Identification + Stage

Each evidence file documents ONE or more supporting-unit processes at a
specific stage of AI adoption:

  - **Development / Testing / UAT** — the AI feature is being built or tested,
    but NOT yet live in production → stage = NOT go live.
  - **Go-Live / BAST / Production deployment** — the AI has gone live and is
    actively used by the supporting unit → stage = go live.
  - **Proof of Concept (POC)** — exploratory, NOT counted as adoption.
  - **Mid Year Survey** — survey-distribution + AI-driven analysis.
  - **Recruitment (Portal Rekrutmen, AI recruitment features)** — recruitment
    process using AI (e.g. resume screening, ranking, candidate scoring).
  - **E-invoice** — invoicing process using AI (e.g. OCR, validation,
    anomaly detection, auto-classification).

You MUST return TWO arrays:

1. `applications` — every distinct supporting-unit process that uses AI
   identified in this evidence (regardless of stage). Used for display and
   audit trail. Use the CANONICAL name of the supporting-unit process
   (e.g. "Portal Rekrutmen AI", "Mid Year Survey", "E-invoice"), NOT the
   AI technique ("OCR", "NLP") or vendor name.
2. `go_live_applications` — the SUBSET of `applications` whose stage in
   THIS evidence is **Go-Live / BAST / production deployment**. ONLY these
   contribute to the realisasi count.

### Stage detection rules
- Evidence containing a "Berita Acara Go Live", "BAST", "Go-Live",
  "Production Deployment", "deployed to production", "live in production",
  "siap digunakan", "sudah beroperasi", or similar wording → stage = go
  live → put the process in BOTH `applications` AND `go_live_applications`.
- Evidence that is only UAT sign-off, test scripts, test results,
  development status, POC report, or screenshots of testing → stage =
  UAT/testing/POC → put the process in `applications` ONLY. Do NOT add it
  to `go_live_applications`.
- A documented POC that explicitly transitions to production in the same
  document may be counted as go-live. When in doubt, do NOT add to
  `go_live_applications` (prefer under-counting over over-counting).

### Naming rules
- Use the CANONICAL process name as written in the document. The supporting
  unit is the business process that uses AI — e.g. "Proses Recruitment",
  "Portal Rekrutmen", "Mid Year Survey", "E-invoice".
- Document-stage prefixes/wrappers ("Berita Acara", "Go Live", "BA",
  "Laporan", "Notulen") are document types, NOT part of the process name.
  Strip them.
- The AI technique, vendor, or product name is NOT the process — e.g. do
  NOT use "PT ATD Solution", "Azure Cognitive Services", "OCR Engine",
  "Chatbot GPT" as the canonical name. Use the business process these serve
  (e.g. "Recruitment", "Customer Service"). You MAY combine the process with
  the AI descriptor in a brief way, e.g. "Portal Rekrutmen AI" if the
  document calls the system that way.
- Two evidence files for the SAME supporting-unit process MUST return the
  SAME canonical name so Laravel de-duplicates them across uploads.
- If no specific supporting-unit process using AI can be identified, return
  empty arrays for both `applications` and `go_live_applications`.

### realisasi value
- `realisasi` MUST equal `count(go_live_applications)` — the number of unique
  supporting-unit processes that use AI and reached Go-Live / production
  deployment in THIS evidence.
- A UAT-only / development-only evidence therefore has `realisasi: 0` and
  `go_live_applications: []`, even though `applications` may list AI
  processes under development.
- An evidence covering a single Go-Live (e.g. Recruitment AI Go-Live) has
  `realisasi: 1` and `go_live_applications: ["Proses Recruitment"]`
  (or similar canonical name).

### Evidence validation
- Set `evidence_valid` to false if the document is NOT about AI adoption in a
  supporting-unit process (e.g. unrelated HR letter, vendor marketing,
  business memo, payment confirmation — none mention AI implementation).
- A document covering only a SUBSET of stages (UAT, POC) is still VALID —
  process it; just exclude it from `go_live_applications`.
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

    /**
     * Specialised prompt for Project Management: Traceability KPI.
     *
     * This KPI measures the completeness of an IT project's lifecycle
     * documentation against its Project Charter timeline. Each evidence
     * file is typically ONE lifecycle document (Kajian / TOR / SPK /
     * Implementasi / BAST) for a SPECIFIC project. Gemini must:
     *   1. Identify the project's CANONICAL name.
     *   2. Classify which lifecycle stage the evidence documents.
     *   3. Map the stage to a fixed achievement percentage so Laravel can
     *      aggregate across multiple evidence files for the same project.
     *
     * Stage → achievement_pct mapping (per OMTI 2026 KPI #7 guidance):
     *   - Kajian (Technical Assessment)         = 20%
     *   - TOR / KAK (Terms of Reference)        = 40%
     *   - SPK (Surat Perintah Kerja)            = 60%
     *   - Implementasi / UAT / Deployment       = 80%
     *   - BAST / Go Live / Production           = 100%
     */
    protected function getProjectManagementTraceabilityPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: Project Management Traceability

You are a senior IT project management auditor analyzing evidence for the
"Pencapaian Project Management: Traceability" KPI.

This KPI measures the completeness of an IT project's lifecycle documentation
against the timeline defined in its Project Charter. Realisasi is a PERCENTAGE
ranging from 0 to 100, NOT a count.

## CRITICAL: Project Identification + Lifecycle Stage

Each evidence file documents ONE lifecycle stage of ONE specific IT project.
Your task is to:

1. Identify the CANONICAL PROJECT NAME written in the document.
2. Classify which LIFECYCLE STAGE the evidence represents:
   - **Kajian** (Technical Assessment / Feasibility Study / Kajian Studi): the
     preliminary analysis document proposing the project. Usually contains "Kajian",
     "Studi", "Assessment".
   - **TOR** (Terms of Reference / KAK / Kerangka Acuan Kerja): the
     specification/procurement document defining scope, deliverables, BoQ.
     Usually titled "TOR", "KAK", "Kerangka Acuan Kerja".
   - **SPK** (Surat Perintah Kerja / Purchase Order / Contract): the work order
     or contract signed with the vendor after procurement. Usually contains
     "SPK", "Purchase Order", "Kontrak", "PO".
   - **Implementasi** (Development / UAT / Deployment / Testing): evidence that
     the project is being built or tested. Includes UAT sign-off, test scripts,
     deployment notes, "development", "testing".
   - **BAST** (Berita Acara Serah Terima / Go Live / Production Deployment):
     the final handover showing the system is live in production. Contains
     "BAST", "Berita Acara Serah Terima", "Go Live", "Production".

### Stage → Achievement Percentage (FIXED, do NOT deviate)
| Stage         | achievement_pct |
|---------------|-----------------|
| Kajian        | 20              |
| TOR           | 40              |
| SPK           | 60              |
| Implementasi  | 80              |
| BAST          | 100             |

### Naming rules
- Use the CANONICAL PROJECT NAME as written in the document (e.g. "SBUCS",
  "ID Palet Pengiriman & Manajemen Serial Number").
- Strip document-type prefixes ("Kajian", "TOR", "KAK", "SPK", "Berita Acara",
  "BAST", "Go Live") from the project name — these are document types, not
  project names.
- Two evidence files for the SAME project (even if one is a Kajian and the other
  is a TOR) MUST return the SAME canonical project name so Laravel de-duplicates
  them and tracks lifecycle progression across the period.
- If multiple distinct projects are covered in one document, list each separately
  in `traceability_items`.
- If no specific project name is identifiable, return an empty
  `traceability_items` array.

### Realisasi value (per evidence file)
- `realisasi` MUST equal the `achievement_pct` of the stage identified.
- A Kajian-only evidence therefore has `realisasi: 20`.
- A TOR-only evidence has `realisasi: 40`.
- If the document covers multiple stages of one project (rare — e.g. a completed
  BAST that also summarizes the TOR), use the HIGHEST stage reached.
- If the evidence is invalid or unrelated to IT project lifecycle, set
  `evidence_valid` to false, `realisasi` to 0, and `traceability_items` to [].

### Evidence validation
- Set `evidence_valid` to false if the document is NOT an IT project lifecycle
  document (e.g. an invoice for services, a general company memo, an HR letter).
- A document covering only a SUBSET of stages is still VALID — process it and
  return the highest stage it represents.
PROMPT;
    }

    /**
     * Specialised prompt for the Enterprise Architecture Project Management KPI
     * (OMTI 2026 #7). Unlike the 5-stage Traceability prompt, this KPI uses a
     * 3-stage lifecycle:
     *   - Tahap Perencanaan (TOR, EE)        = 25
     *   - Tahap Development (SPK, FGD)       = 80
     *   - Tahap Implementasi (BAST)          = 100
     *
     * Important: SPK maps to "Development" (80), NOT "SPK" (60) as in the
     * 5-stage prompt — a SPK here means development work has been kicked off,
     * which is further along than just a procurement contract. Make sure the
     * achievement_pct returned matches the 3-stage table below, not the
     * generic one.
     */
    protected function getProjectManagementEAPrompt(): string
    {
        return <<<'PROMPT'
# Evidence Analysis: EA Project Management (3-stage Lifecycle)

You are a senior IT project management auditor analyzing evidence for the
"Pencapaian Project Management: Implementasi Enterprise Architecture guna
Mendukung Pilar Security Solutions dan Pilar SPBE dalam Pemenuhan Strategic
Initiative Digital Platform dan Technology Capabilities" KPI (OMTI 2026 #7).

This KPI measures the achievement of project milestones against the timeline
defined in its Project Charter, using a 3-TAHAP (stage) lifecycle. Realisasi
is a PERCENTAGE ranging from 0 to 100, NOT a count.

## CRITICAL: 3-Stage Mapping (FIXED, do NOT deviate)

| Tahap (stage)           | Document evidence            | achievement_pct |
|-------------------------|------------------------------|-----------------|
| Perencanaan             | TOR, EE (Enterprise          | 25              |
|                         |  Environment), Kajian,       |                 |
|                         |  awareness/assessment        |                 |
| Development             | SPK (Surat Perintah Kerja),  | 80              |
|                         |  FGD (Focus Group            |                 |
|                         |  Discussion), PO, contract/  |                 |
|                         |  work-order, kickoff         |                 |
| Implementasi            | BAST (Berita Acara Serah     | 100             |
|                         |  Terima), Go Live,         |                 |
|                         |  Production Deployment,     |                 |
|                         |  UAT sign-off, Go-Live notice|                 |

### Stage detection rules
- Evidence mentions/contains "TOR", "EE" (Enterprise Environment), "Kajian",
  "Assessment", "Pelin tingan", "Pra-perencanaan", "Awareness" →
  stage = "Perencanaan", achievement_pct = 25.
- Evidence titled "SPK", "Surat Perintah Kerja", "PO", "Kontrak", "Work
  Order", "FGD", "Kick-Off", "Contract Award" → stage = "Development",
  achievement_pct = 80. NOTE: an SPK document is DEVELOPMENT (80), NOT
  "SPK=60" — the 3-stage lifecycle collapses procurement + early execution
  into one stage.
- Evidence mentions "BAST", "Berita Acara Serah Terima", "Go Live",
  "Production", "Deployment", "UAT Sign-Off", "Live" → stage =
  "Implementasi", achievement_pct = 100.
- When in doubt about whether something is "Perencanaan" vs "Development",
  SPK/contract-style documents are ALWAYS Development.
- When in doubt between "Development" and "Implementasi", evidence must
  explicitly state Go-Live / BAST / production deployment to qualify as
  Implementasi; otherwise stay at Development.

### Project identification
- Identify the CANONICAL project name written in the document (e.g.
  "Enterprise Architecture", "EA Security Solutions", "EA SPBE Platform",
  "Digital Platform EA"). Use the project name as written in the header /
  title; strip document-type prefixes ("SPK", "TOR", "EE", "Berita Acara",
  "BAST", "NDE", "Laporan").
- Two evidence files for the SAME project (e.g. a TOR and an SPK) MUST
  return the SAME canonical project name so Laravel de-duplicates them
  and tracks lifecycle progression across the period.
- If multiple distinct projects are covered in one document, list each
  separately in `traceability_items`.
- If no specific project name is identifiable, return an empty
  `traceability_items` array.

### realisasi value (per evidence file)
- `realisasi` MUST equal the MAXIMUM `achievement_pct` in
  `traceability_items` for this evidence file. A TOR-only evidence →
  realisasi = 25. A SPK-only evidence → realisasi = 80. A BAST-only
  evidence → realisasi = 100.
- If the evidence is invalid or unrelated to this EA project lifecycle,
  set `evidence_valid` to false, `realisasi` to 0, and traceability_items
  to [].

### Evidence validation
- Set `evidence_valid` to false if the document is NOT an EA / IT project
  lifecycle document for this KPI's scope (Enterprise Architecture,
  Security Solutions, Pilar SPBE, Digital Platform, Technology Capabilities).
  Examples of invalid: unrelated invoices, general HR letters, vendor marketing.
- A document covering a SUBSET of stages is still VALID — process it and
  return the highest stage it represents (use the 3-stage mapping).
- The 3-stage mapping is FIXED; do NOT use the 5-stage Kajian/TOR/SPK/
  Implementasi/BAST mapping.
PROMPT;
    }

    /**
     * Specialised prompt for the 3-stage Enterprise Architecture Project
     * Management KPI (OMTI 2026 #7). Differs from the generic 5-stage
     * Traceability prompt:
     *   - Perencanaan (TOR, EE) = 25
     *   - Development (SPK, FGD) = 80
     *   - Implementasi (BAST)   = 100
     */
    protected function getProjectManagementEARecommendationHint(): string
    {
        return <<<'HINT'
## Recommendation Domain Hint — EA Project Management (3-stage)
For the Enterprise Architecture Project Management KPI (lifecycle progress
against Project Charter), recommendations should draw from concrete
categories relevant to the EA / Security Solutions / SPBE scope:

**Lifecycle Gap Closure (match the 3-stage mapping):**
- Tahap Perencanaan (TOR/EE) → push ke tahap Development (SPK/FGD) ≤ 30 hari
  dari TOR terbit (target SPK terbit ≤ 30 hari dari TOR)
- Tahap Development (SPK) → target Implementasi (BAST/Go Live) sesuai jadwal
  Project Charter; eskalasi ke PMO bila delay > 14 hari dari jadwal
- Tahap Implementasi (BAST) → kambuh / deviation analisis ≤ 14 hari setelah
  Go Live

**EA / Security / SPBE Alignment:**
- Gap analysis EA framework terhadap dokumen TOR/EE — pastikan seluruh
  komponen Security Solutions dan SPBE ter-cover (target coverage 100%)
- Mapping SPBE ke EA reference architecture — verify pilar SPBE yang
  belum diadopsi (target adoption ≥ 100% Pilar SPBE yang relevan)
- Mapping Strategic Initiative Digital Platform ke EA domain — target
  delivery per domain sesuai Project Charter

**PMO Governance:**
- Pertemuan PMO mingguan dengan status per tahap (Perencanaan / Development /
  Implementasi) — RAG status (Red < 25%, Amber 25–79%, Green ≥ 80%)
- RACI per tahap lifecycle (EE → TOR → SPK → FGD → BAST)
- Risk register untuk blocker SPBE / Security Solutions (SLA mitigasi
  ≤ 7 hari)

**Vendor & Contract:**
- SLA vendor untuk Security Solutions / SPBE platform (target delivery sesuai
  kontrak, penalty clause untuk delay)
- Kick-off (FGD) ≤ 14 hari setelah SPK terbit untuk mulai Development

**Post-BAST Stabilization (jika sudah BAST):**
- Post-implementation review 90 hari pasca BAST (uptime ≥ 99.5%, security
  incident ≤ 0 major)
- Adoption survey SPBE / Digital Platform (NPS ≥ 80 dalam Q setelah Go Live)
- Runbook + knowledge transfer ≤ 30 hari pasca Go Live
HINT;
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
                // "Jumlah proses supporting unit yang menggunakan AI" reuses
                // the applications + go_live_applications structure (count
                // of unique go-live processes), so the same JSON format and
                // the same aggregation logic apply.
                || str_contains(strtolower($measurement->measurement), 'supporting unit')
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

        $isSla = $measurement && (
            $this->isSlaAvailability(strtolower($measurement->measurement))
            || $this->isSlaAvailability(strtolower($measurement->definition ?? ''))
        );

        if ($isSla) {
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
    "realisasi": 98.52,
    "sla_targets": [
        { "name": "Aplikasi ESS", "uptime": 99.95 },
        { "name": "Aplikasi SAP ERP S/4HANA", "uptime": 100 }
    ],
    "analysis": "Detailed analysis of the evidence (which targets were monitored, period covered, which fell below SLA)...",
    "recommendations": [
        "First actionable recommendation",
        "Second actionable recommendation",
        "Third actionable recommendation"
    ]
}
```

IMPORTANT NOTES:
- `realisasi` is the MEAN of every `sla_targets[].uptime` value, expressed as
  a number between 0 and 100 (NOT 0–1). Example: 98.52 means 98.52%.
- `sla_targets` is REQUIRED — list every distinct monitored target with its
  extracted uptime percentage. Each entry is an object with `name` and `uptime`.
- If no uptime can be extracted, return `"evidence_valid": false`,
  `realisasi: 0`, and `"sla_targets": []`.
- Return ONLY the JSON object, no additional text.
PROMPT;
        }

        $isInvestmentRealisasi = $measurement && $this->isInvestmentRealisasiName(
            strtolower($measurement->measurement),
            strtolower($measurement->definition ?? ''),
        );

        if ($isInvestmentRealisasi) {
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
    "realisasi": 45.5,
    "investment_items": [
        {
            "name": "Core Switch Karawang (FA3299 & FA3669)",
            "budget": 500000000,
            "realized": 300000000,
            "percentage": 60.0,
            "status": "SPK"
        },
        {
            "name": "Server Virtualisasi DRC (FA5793)",
            "budget": 800000000,
            "realized": 160000000,
            "percentage": 20.0,
            "status": "Kajian"
        }
    ],
    "analysis": "Detailed analysis of the evidence (which investment items were identified, total budget, total realized, overall percentage, items behind schedule)...",
    "recommendations": [
        "First actionable recommendation",
        "Second actionable recommendation",
        "Third actionable recommendation"
    ]
}
```

IMPORTANT NOTES:
- `investment_items` is REQUIRED — list every distinct investment item
  identified in this evidence. Each entry is an object with `name` (string),
  `budget` (number, in Rupiah), `realized` (number, in Rupiah),
  `percentage` (number 0-100), and `status` (string: e.g. "Kajian", "TOR",
  "SPK", "Implementasi", "Deploy/Selesai").
- `budget` and `realized` should be numbers (not strings). Use 0 if the
  amount is not stated in the document.
- `percentage` = realized / budget × 100 when budget > 0; otherwise use the
  stage-based estimate described in the prompt (Kajian/TOR = 10-20%, SPK =
  30-50%, Implementasi = 50-80%, Deploy/Selesai = 90-100%).
- `realisasi`:
  - For monitoring reports: the OVERALL Capex realization percentage
    (= sum(realized) / sum(budget) × 100, or the explicitly stated total).
  - For individual project documents: the realization percentage for THIS
    specific item (same as `investment_items[0].percentage`).
  - Must be a number between 0 and 100 (NOT 0-1). Example: 45.5 = 45.5%.
- If no investment item can be identified, return `"evidence_valid": false`,
  `realisasi: 0`, and `"investment_items": []`.
- Return ONLY the JSON object, no additional text.
PROMPT;
        }

        $isTraceability = $measurement && (
            str_contains(strtolower($measurement->measurement), 'project management')
            || str_contains(strtolower($measurement->measurement), 'traceability')
        );

        // 3-stage Enterprise Architecture variant produces traceability_items
        // too — but with stages Perencanaan / Development / Implementasi and
        // a different achievement_pct mapping (25/80/100). Branch BEFORE the
        // generic 5-stage block so the JSON example reflects the right table.
        $isEAThreeStage = $isTraceability
            && str_contains(strtolower($measurement->measurement), 'pilar security')
            && str_contains(strtolower($measurement->measurement), 'pilar spbe');

        if ($isEAThreeStage) {
            return <<<'PROMPT'
## Required Output Format
You MUST respond with a valid JSON object in exactly this format:

```json
{
    "measurement": "Pencapaian Project Management: Implementasi Enterprise Architecture guna Mendukung Pilar Security Solutions dan Pilar SPBE dalam Pemenuhan Strategic Initiative Digital Platform dan Technology Capabilities",
    "matched_initiative": {
        "name": "Name of the matched initiative from the list",
        "confidence": 95
    },
    "evidence_valid": true,
    "realisasi": 80,
    "traceability_items": [
        {
            "name": "Enterprise Architecture",
            "stage": "Development",
            "achievement_pct": 80
        }
    ],
    "analysis": "Detailed analysis of the evidence (which EA project was identified, which 3-stage lifecycle stage it documents, what is the milestone progression per Project Charter)...",
    "recommendations": [
        "First actionable recommendation with concrete scope + timeframe",
        "Second actionable recommendation with concrete scope + timeframe",
        "Third actionable recommendation with concrete scope + timeframe"
    ]
}
```

IMPORTANT NOTES:
- `traceability_items` is REQUIRED — list every distinct project identified
  in this evidence with its lifecycle stage and the corresponding
  achievement_pct.
- `stage` MUST be one of: "Perencanaan", "Development", "Implementasi"
  (case-sensitive). Use the EXACT values as written here. Do NOT use the
  5-stage names (Kajian/TOR/SPK/BAST) — that is a DIFFERENT KPI.
- `achievement_pct` MUST match the 3-stage mapping:
  Perencanaan=25, Development=80, Implementasi=100. Do NOT invent other
  numbers.
- `realisasi` MUST equal the MAXIMUM `achievement_pct` in
  `traceability_items`. A Perencanaan-only evidence (TOR/EE) has
  realisasi=25. A Development-only evidence (SPK/FGD) has realisasi=80.
  An Implementasi-only evidence (BAST/Go Live) has realisasi=100.
- If multiple projects are documented in one evidence, list each separately
  and set realisasi to the highest achievement_pct among them.
- If no project can be identified or the document is NOT an EA project
  lifecycle document for this KPI's scope (Enterprise Architecture,
  Security Solutions, Pilar SPBE, Digital Platform, Technology
  Capabilities), return `"evidence_valid": false`, `realisasi: 0`,
  and `"traceability_items": []`.
- Return ONLY the JSON object, no additional text.
PROMPT;
        }

        if ($isTraceability) {
            return <<<'PROMPT'
## Required Output Format
You MUST respond with a valid JSON object in exactly this format:

```json
{
    "measurement": "Pencapaian Project Management: Traceability",
    "matched_initiative": {
        "name": "Name of the matched initiative from the list",
        "confidence": 95
    },
    "evidence_valid": true,
    "realisasi": 20,
    "traceability_items": [
        {
            "name": "SBUCS",
            "stage": "Kajian",
            "achievement_pct": 20
        }
    ],
    "analysis": "Detailed analysis of the evidence (which project was identified, which lifecycle stage it documents, what is the milestone progression)...",
    "recommendations": [
        "First actionable recommendation with concrete scope + timeframe",
        "Second actionable recommendation with concrete scope + timeframe",
        "Third actionable recommendation with concrete scope + timeframe"
    ]
}
```

IMPORTANT NOTES:
- `traceability_items` is REQUIRED — list every distinct project identified
  in this evidence with its lifecycle stage and the corresponding
  achievement_pct.
- `stage` MUST be one of: "Kajian", "TOR", "SPK", "Implementasi", "BAST"
  (case-sensitive). Use the EXACT values as written here.
- `achievement_pct` MUST match the stage: Kajian=20, TOR=40, SPK=60,
  Implementasi=80, BAST=100. Do NOT invent other numbers.
- `realisasi` MUST equal the MAXIMUM `achievement_pct` in
  `traceability_items`. A Kajian-only evidence has realisasi=20. A BAST-only
  evidence has realisasi=100. An evidence covering a single project has
  realisasi equal to that project's achievement_pct.
- If multiple projects are documented in one evidence, list each separately
  and set realisasi to the highest achievement_pct among them.
- If no project can be identified or the document is not an IT project
  lifecycle document, return `"evidence_valid": false`, `realisasi: 0`,
  and `"traceability_items": []`.
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
