<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\DocumentExtractorService;
use App\AI\PromptManager;
use App\AI\GeminiService;

foreach (['MPTI_PT_PERURI_2025-2029_DUMMY.pdf', 'RJPP_PT_PERURI_2025-2029_DUMMY.pdf'] as $f) {
    $path = base_path('docs/strategic-reference/'.$f);
    echo "=== $f ===\n";
    $ext = app(DocumentExtractorService::class);
    $dto = $ext->extract($path, maxPages: 200);
    echo "kpi=".count($dto->kpis)." init=".count($dto->initiatives)." so=".count($dto->strategicObjectives)."\n";
    $prompt = app(PromptManager::class)->generateStrategicAdvisorPrompt($dto);
    echo "prompt=".strlen($prompt)." bytes\n";
    $res = app(GeminiService::class)->analyzeWithSearch($prompt);
    echo "success=".($res['success']?'YES':'NO')." grounded=".($res['grounded']?'YES':'NO')." proc=".$res['processing_time']."s\n";
    if (! empty($res['error'])) echo "error: ".substr($res['error'], 0, 200)."\n";
    if (! empty($res['data'])) echo "recs=".count($res['data']['recommendations'] ?? [])." trends=".count($res['data']['popular_trends'] ?? [])."\n";
    echo "\n";
}