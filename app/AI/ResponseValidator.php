<?php

namespace App\AI;

use Illuminate\Support\Facades\Log;

class ResponseValidator
{
    /**
     * Required fields in the AI response
     */
    protected array $requiredFields = [
        'measurement',
        'evidence_valid',
        'realisasi',
        'analysis',
    ];

    /**
     * Validate the parsed AI response
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Check required fields
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'data' => $data,
            ];
        }

        // Validate field types and values
        if (!is_bool($data['evidence_valid']) && !in_array($data['evidence_valid'], [0, 1, '0', '1', 'true', 'false'], true)) {
            $errors[] = "evidence_valid must be a boolean value";
        }

        if (!is_numeric($data['realisasi'])) {
            $errors[] = "realisasi must be a numeric value";
        }

        if (!is_string($data['analysis']) || empty(trim($data['analysis']))) {
            $errors[] = "analysis must be a non-empty string";
        }

        // Validate matched_initiative if present
        if (isset($data['matched_initiative'])) {
            if (is_array($data['matched_initiative'])) {
                if (isset($data['matched_initiative']['confidence'])) {
                    $confidence = $data['matched_initiative']['confidence'];
                    if (!is_numeric($confidence) || $confidence < 0 || $confidence > 100) {
                        $errors[] = "matched_initiative.confidence must be between 0 and 100";
                    }
                }
            }
        }

        // Validate recommendations if present
        if (isset($data['recommendations'])) {
            if (!is_array($data['recommendations'])) {
                $errors[] = "recommendations must be an array";
            }
        }

        // Validate applications if present (used by Implementasi Sistem to
        // de-duplicate the same application across multiple evidence files).
        if (isset($data['applications'])) {
            if (!is_array($data['applications'])) {
                $errors[] = "applications must be an array";
            } else {
                foreach ($data['applications'] as $index => $app) {
                    if (!is_string($app) || trim($app) === '') {
                        $errors[] = "applications[{$index}] must be a non-empty string";
                        break;
                    }
                }
            }
        }

        // Validate go_live_applications if present. This is the SUBSET of
        // applications whose stage has reached Go Live / production in this
        // evidence. Only these contribute to the realisasi count.
        if (isset($data['go_live_applications'])) {
            if (!is_array($data['go_live_applications'])) {
                $errors[] = "go_live_applications must be an array";
            } else {
                foreach ($data['go_live_applications'] as $index => $app) {
                    if (!is_string($app) || trim($app) === '') {
                        $errors[] = "go_live_applications[{$index}] must be a non-empty string";
                        break;
                    }
                }
            }
        }

        // Validate sla_targets if present. Used by SLA / availability KPIs to
        // capture the per-target uptime breakdown (e.g. each application's
        // uptime from a PRTG report). Each entry must be an object with a
        // non-empty string `name` and a numeric `uptime`.
        if (isset($data['sla_targets'])) {
            if (!is_array($data['sla_targets'])) {
                $errors[] = "sla_targets must be an array";
            } else {
                foreach ($data['sla_targets'] as $index => $target) {
                    if (!is_array($target)) {
                        $errors[] = "sla_targets[{$index}] must be an object";
                        break;
                    }
                    if (!isset($target['name']) || !is_string($target['name']) || trim($target['name']) === '') {
                        $errors[] = "sla_targets[{$index}].name must be a non-empty string";
                        break;
                    }
                    if (!isset($target['uptime']) || !is_numeric($target['uptime'])) {
                        $errors[] = "sla_targets[{$index}].uptime must be numeric";
                        break;
                    }
                }
            }
        }

        // Validate investment_items if present. Used by Capex realization /
        // Realisasi Nilai Investasi KPIs to capture the per-item budget vs
        // realized breakdown. Each entry must be an object with a non-empty
        // string `name`, numeric `budget`, `realized`, and `percentage`.
        if (isset($data['investment_items'])) {
            if (!is_array($data['investment_items'])) {
                $errors[] = "investment_items must be an array";
            } else {
                foreach ($data['investment_items'] as $index => $item) {
                    if (!is_array($item)) {
                        $errors[] = "investment_items[{$index}] must be an object";
                        break;
                    }
                    if (!isset($item['name']) || !is_string($item['name']) || trim($item['name']) === '') {
                        $errors[] = "investment_items[{$index}].name must be a non-empty string";
                        break;
                    }
                    if (isset($item['budget']) && !is_numeric($item['budget'])) {
                        $errors[] = "investment_items[{$index}].budget must be numeric";
                        break;
                    }
                    if (isset($item['realized']) && !is_numeric($item['realized'])) {
                        $errors[] = "investment_items[{$index}].realized must be numeric";
                        break;
                    }
                    if (isset($item['percentage']) && !is_numeric($item['percentage'])) {
                        $errors[] = "investment_items[{$index}].percentage must be numeric";
                        break;
                    }
                }
            }
        }

        // Validate traceability_items if present. Used by Project Management:
        // Traceability KPI to capture per-project lifecycle stage breakdown so
        // Laravel can aggregate max stage per project across evidence files.
        // Each entry must be an object with a non-empty string `name`, a valid
        // `stage` (one of: Kajian, TOR, SPK, Implementasi, BAST), and a numeric
        // `achievement_pct` (0-100).
        if (isset($data['traceability_items'])) {
            if (!is_array($data['traceability_items'])) {
                $errors[] = "traceability_items must be an array";
            } else {
                $validStages = ['Kajian', 'TOR', 'SPK', 'Implementasi', 'BAST', 'Perencanaan', 'Development'];

                foreach ($data['traceability_items'] as $index => $item) {
                    if (!is_array($item)) {
                        $errors[] = "traceability_items[{$index}] must be an object";
                        break;
                    }
                    if (!isset($item['name']) || !is_string($item['name']) || trim($item['name']) === '') {
                        $errors[] = "traceability_items[{$index}].name must be a non-empty string";
                        break;
                    }
                    if (!isset($item['stage']) || !is_string($item['stage'])) {
                        $errors[] = "traceability_items[{$index}].stage must be a string";
                        break;
                    }
                    // Stage matching is case-insensitive but must equal one
                    // of the canonical lifecycle stages.
                    $stageLower = strtolower(trim($item['stage']));
                    $canonicalStages = array_map('strtolower', $validStages);
                    if (!in_array($stageLower, $canonicalStages, true)) {
                        $errors[] = "traceability_items[{$index}].stage must be one of: " . implode(', ', $validStages);
                        break;
                    }
                    if (!isset($item['achievement_pct']) || !is_numeric($item['achievement_pct'])) {
                        $errors[] = "traceability_items[{$index}].achievement_pct must be numeric";
                        break;
                    }
                    $pct = (float) $item['achievement_pct'];
                    if ($pct < 0 || $pct > 100) {
                        $errors[] = "traceability_items[{$index}].achievement_pct must be between 0 and 100";
                        break;
                    }
                }
            }
        }

        // Validate rsti_items if present. Used by Implementasi Inisiatif RSTI
        // KPI to capture the per-registered-initiative roadmap status
        // breakdown (code + name + status) so Laravel can count unique
        // initiatives with status "Selesai" across evidence files. Each
        // entry must be an object with a non-empty string `name` and a valid
        // `status` (one of: Selesai, In Progress, Belum Berjalan, Drop,
        // Tidak Ditemukan). `code` is optional but recommended.
        if (isset($data['rsti_items'])) {
            if (!is_array($data['rsti_items'])) {
                $errors[] = 'rsti_items must be an array';
            } else {
                $validStatuses = ['Selesai', 'In Progress', 'Belum Berjalan', 'Drop', 'Tidak Ditemukan'];

                foreach ($data['rsti_items'] as $index => $item) {
                    if (!is_array($item)) {
                        $errors[] = "rsti_items[{$index}] must be an object";
                        break;
                    }
                    if (!isset($item['name']) || !is_string($item['name']) || trim($item['name']) === '') {
                        $errors[] = "rsti_items[{$index}].name must be a non-empty string";
                        break;
                    }
                    if (isset($item['code']) && !is_string($item['code'])) {
                        $errors[] = "rsti_items[{$index}].code must be a string";
                        break;
                    }
                    if (!isset($item['status']) || !is_string($item['status'])) {
                        $errors[] = "rsti_items[{$index}].status must be a string";
                        break;
                    }
                    // Status matching is case-insensitive but must equal one
                    // of the canonical roadmap statuses.
                    $statusLower = strtolower(trim($item['status']));
                    $canonicalStatuses = array_map('strtolower', $validStatuses);
                    if (!in_array($statusLower, $canonicalStatuses, true)) {
                        $errors[] = 'rsti_items[' . $index . '].status must be one of: ' . implode(', ', $validStatuses);
                        break;
                    }
                }
            }
        }

        if (!empty($errors)) {
            Log::warning('AI response validation errors', [
                'errors' => $errors,
                'data' => $data,
            ]);

            return [
                'valid' => false,
                'errors' => $errors,
                'data' => $data,
            ];
        }

        // Normalize data
        $normalized = $this->normalize($data);

        return [
            'valid' => true,
            'errors' => [],
            'data' => $normalized,
        ];
    }

    /**
     * Validate the KPI insight response (second AI call) that explains WHY a
     * KPI is achieved or not. Kept separate from validate() so the evidence
     * analysis contract is not affected.
     *
     * Expected shape: { achieved_reason, not_achieved_reason, recommendations[] }
     */
    public function validateInsight(array $data): array
    {
        $errors = [];

        if (!array_key_exists('achieved_reason', $data)) {
            $errors[] = "Missing required field: achieved_reason";
        } elseif (!is_string($data['achieved_reason'])) {
            $errors[] = "achieved_reason must be a string";
        }

        if (!array_key_exists('not_achieved_reason', $data)) {
            $errors[] = "Missing required field: not_achieved_reason";
        } elseif (!is_string($data['not_achieved_reason'])) {
            $errors[] = "not_achieved_reason must be a string";
        }

        if (isset($data['recommendations']) && !is_array($data['recommendations'])) {
            $errors[] = "recommendations must be an array";
        }

        if (!empty($errors)) {
            Log::warning('AI insight validation errors', [
                'errors' => $errors,
                'data' => $data,
            ]);

            return [
                'valid' => false,
                'errors' => $errors,
                'data' => $data,
            ];
        }

        $normalized = $this->normalizeInsight($data);

        return [
            'valid' => true,
            'errors' => [],
            'data' => $normalized,
        ];
    }

    /**
     * Normalize the insight response data.
     */
    protected function normalizeInsight(array $data): array
    {
        $data['achieved_reason'] = trim((string) ($data['achieved_reason'] ?? ''));
        $data['not_achieved_reason'] = trim((string) ($data['not_achieved_reason'] ?? ''));

        if (!isset($data['recommendations'])) {
            $data['recommendations'] = [];
        }

        if (is_string($data['recommendations'])) {
            $data['recommendations'] = [$data['recommendations']];
        }

        // Drop any non-string / empty entries.
        $data['recommendations'] = array_values(array_filter(
            array_map(fn ($r) => is_string($r) ? trim($r) : '', $data['recommendations']),
            fn ($r) => $r !== '',
        ));

        return $data;
    }

    /**
     * Normalize the AI response data
     */
    protected function normalize(array $data): array
    {
        // Normalize evidence_valid to boolean
        $data['evidence_valid'] = filter_var($data['evidence_valid'], FILTER_VALIDATE_BOOLEAN);

        // Normalize realisasi to float
        $data['realisasi'] = (float) $data['realisasi'];

        // Ensure matched_initiative structure
        if (!isset($data['matched_initiative'])) {
            $data['matched_initiative'] = [
                'name' => null,
                'confidence' => 0,
            ];
        } elseif (is_string($data['matched_initiative'])) {
            $data['matched_initiative'] = [
                'name' => $data['matched_initiative'],
                'confidence' => 0,
            ];
        }

        // Ensure confidence is numeric
        if (isset($data['matched_initiative']['confidence'])) {
            $data['matched_initiative']['confidence'] = (float) $data['matched_initiative']['confidence'];
        }

        // Ensure recommendations is an array
        if (!isset($data['recommendations'])) {
            $data['recommendations'] = [];
        }

        if (is_string($data['recommendations'])) {
            $data['recommendations'] = [$data['recommendations']];
        }

        // Ensure applications is an array of trimmed, non-empty strings.
        // Used by Implementasi Sistem measurements so Laravel can count UNIQUE
        // application names across multiple evidence files for the same period.
        if (!isset($data['applications'])) {
            $data['applications'] = [];
        }

        if (is_string($data['applications'])) {
            $data['applications'] = [$data['applications']];
        }

        if (is_array($data['applications'])) {
            $data['applications'] = array_values(array_filter(
                array_map(fn ($a) => is_string($a) ? trim($a) : '', $data['applications']),
                fn ($a) => $a !== '',
            ));
        }

        // Normalize go_live_applications the same way. Legacy responses that
        // do not include this field get an empty array (no go-live contribution).
        if (!isset($data['go_live_applications'])) {
            $data['go_live_applications'] = [];
        }

        if (is_string($data['go_live_applications'])) {
            $data['go_live_applications'] = [$data['go_live_applications']];
        }

        if (is_array($data['go_live_applications'])) {
            $data['go_live_applications'] = array_values(array_filter(
                array_map(fn ($a) => is_string($a) ? trim($a) : '', $data['go_live_applications']),
                fn ($a) => $a !== '',
            ));
        }

        // Normalize sla_targets — array of {name, uptime} objects. Drop any
        // malformed entries and coerce uptime to float. Used by SLA /
        // availability KPIs so the breakdown is preserved in the audit trail.
        if (!isset($data['sla_targets'])) {
            $data['sla_targets'] = [];
        }

        if (!is_array($data['sla_targets'])) {
            $data['sla_targets'] = [];
        }

        $data['sla_targets'] = array_values(array_filter(
            array_map(function ($t) {
                if (!is_array($t)) {
                    return null;
                }
                $name = is_string($t['name'] ?? null) ? trim($t['name']) : '';
                $uptime = isset($t['uptime']) && is_numeric($t['uptime']) ? (float) $t['uptime'] : null;
                if ($name === '' || $uptime === null) {
                    return null;
                }
                return ['name' => $name, 'uptime' => $uptime];
            }, $data['sla_targets']),
            fn ($t) => $t !== null,
        ));

        // Normalize investment_items — array of {name, budget, realized,
        // percentage, status} objects. Drop any malformed entries and coerce
        // numeric fields to float. Used by Capex realization KPIs so the
        // breakdown is preserved in the audit trail and aggregated by
        // EvidenceService.
        if (!isset($data['investment_items'])) {
            $data['investment_items'] = [];
        }

        if (!is_array($data['investment_items'])) {
            $data['investment_items'] = [];
        }

        $data['investment_items'] = array_values(array_filter(
            array_map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }
                $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
                if ($name === '') {
                    return null;
                }
                return [
                    'name' => $name,
                    'budget' => isset($item['budget']) && is_numeric($item['budget']) ? (float) $item['budget'] : 0,
                    'realized' => isset($item['realized']) && is_numeric($item['realized']) ? (float) $item['realized'] : 0,
                    'percentage' => isset($item['percentage']) && is_numeric($item['percentage']) ? (float) $item['percentage'] : 0,
                    'status' => is_string($item['status'] ?? null) ? trim($item['status']) : '',
                ];
            }, $data['investment_items']),
            fn ($item) => $item !== null,
        ));

        // Normalize traceability_items — array of {name, stage,
        // achievement_pct} objects. Drop malformed entries, coerce pct to
        // float, and canonicalize the stage to its title-case form so the
        // aggregation in EvidenceService can switch on exact strings.
        // Used by Project Management: Traceability KPI so the per-project
        // lifecycle progression is preserved in the audit trail and
        // aggregated (MAX stage per project) by EvidenceService.
        if (!isset($data['traceability_items'])) {
            $data['traceability_items'] = [];
        }

        if (!is_array($data['traceability_items'])) {
            $data['traceability_items'] = [];
        }

        $stageMap = [
            'kajian' => 'Kajian',
            'tor' => 'TOR',
            'kak' => 'TOR',
            'spk' => 'SPK',
            'implementasi' => 'Implementasi',
            'bast' => 'BAST',
            'go live' => 'BAST',
            'go-live' => 'BAST',
            'golive' => 'BAST',
            'production' => 'BAST',
            // 3-stage Enterprise Architecture lifecycle (OMTI 2026 #7):
            // Perencanaan (TOR/EE) = 25, Development (SPK/FGD) = 80,
            // Implementasi (BAST) = 100. The 3-stage "Implementasi" maps to
            // "BAST" canonical stage because the OMTI lifecycle uses the
            // label "Tahap Implementasi (BAST)" — i.e. it IS the BAST/Go-Live
            // stage, achievement_pct provided by the AI distinguishes it from
            // the 5-stage "Implementasi" (80).
            'perencanaan' => 'Perencanaan',
            'tahap perencanaan' => 'Perencanaan',
            'planning' => 'Perencanaan',
            'development' => 'Development',
            'tahap development' => 'Development',
        ];

        $data['traceability_items'] = array_values(array_filter(
            array_map(function ($item) use ($stageMap) {
                if (!is_array($item)) {
                    return null;
                }
                $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
                if ($name === '') {
                    return null;
                }
                $stageRaw = is_string($item['stage'] ?? null) ? strtolower(trim($item['stage'])) : '';
                $stage = $stageMap[$stageRaw] ?? null;
                if ($stage === null) {
                    return null;
                }
                $pct = isset($item['achievement_pct']) && is_numeric($item['achievement_pct'])
                    ? (float) $item['achievement_pct']
                    : 0;
                // Clamp + round to 2 decimals so the audit trail is tidy.
                $pct = max(0, min(100, round($pct, 2)));
                return [
                    'name' => $name,
                    'stage' => $stage,
                    'achievement_pct' => $pct,
                ];
            }, $data['traceability_items']),
            fn ($item) => $item !== null,
        ));

        // Normalize rsti_items — array of {code, name, status} objects. Drop
        // malformed entries, canonicalize the status to its exact title form
        // (Selesai / In Progress / Belum Berjalan / Drop / Tidak Ditemukan)
        // and uppercase-normalize the roadmap code so the aggregation in
        // EvidenceService can cluster initiatives by code. Used by
        // Implementasi Inisiatif RSTI KPI so the per-initiative roadmap
        // status breakdown is preserved in the audit trail and aggregated
        // (count of unique "Selesai" initiatives) by EvidenceService.
        if (!isset($data['rsti_items'])) {
            $data['rsti_items'] = [];
        }

        if (!is_array($data['rsti_items'])) {
            $data['rsti_items'] = [];
        }

        $statusMap = [
            'selesai' => 'Selesai',
            'done' => 'Selesai',
            'complete' => 'Selesai',
            'completed' => 'Selesai',
            'finished' => 'Selesai',
            'in progress' => 'In Progress',
            'on progress' => 'In Progress',
            'berjalan' => 'In Progress',
            'sedang berjalan' => 'In Progress',
            'belum berjalan' => 'Belum Berjalan',
            'not started' => 'Belum Berjalan',
            'belum dimulai' => 'Belum Berjalan',
            'drop' => 'Drop',
            'dropped' => 'Drop',
            'dibatalkan' => 'Drop',
            'tidak ditemukan' => 'Tidak Ditemukan',
            'not found' => 'Tidak Ditemukan',
        ];

        $data['rsti_items'] = array_values(array_filter(
            array_map(function ($item) use ($statusMap) {
                if (!is_array($item)) {
                    return null;
                }
                $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
                if ($name === '') {
                    return null;
                }
                $statusRaw = is_string($item['status'] ?? null) ? strtolower(trim($item['status'])) : '';
                $status = $statusMap[$statusRaw] ?? null;
                if ($status === null) {
                    return null;
                }
                $code = is_string($item['code'] ?? null)
                    ? strtoupper(preg_replace('/\s+/', '', trim($item['code'])))
                    : '';
                return [
                    'code' => $code,
                    'name' => $name,
                    'status' => $status,
                ];
            }, $data['rsti_items']),
            fn ($item) => $item !== null,
        ));

        // Trim analysis
        $data['analysis'] = trim($data['analysis']);

        // Trim measurement
        if (isset($data['measurement'])) {
            $data['measurement'] = trim($data['measurement']);
        }

        return $data;
    }
}
