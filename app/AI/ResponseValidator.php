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

        // Trim analysis
        $data['analysis'] = trim($data['analysis']);

        // Trim measurement
        if (isset($data['measurement'])) {
            $data['measurement'] = trim($data['measurement']);
        }

        return $data;
    }
}
