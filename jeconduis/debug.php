<?php
// Charger le .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

header('Content-Type: application/json');

$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';

// Simuler exactement ce que fait recommend.php
require_once __DIR__ . '/api/src/PromptBuilder.php';
require_once __DIR__ . '/api/src/AIService.php';
require_once __DIR__ . '/api/src/Database.php';
require_once __DIR__ . '/api/src/LeadRepository.php';

// Profil de test
$profile = [
    'delai'        => 'mois',
    'type_achat'   => 'occasion',
    'usage'        => 'travail',
    'priorite'     => 'economie',
    'kilometrage'  => '10-20k',
    'motorisation' => 'hybride',
    'carrosserie'  => 'suv',
    'marque'       => '',
    'modele'       => '',
    'budget'       => '15k-25k',
];

// Test MySQL
try {
    $pdo  = Database::connect();
    $repo = new LeadRepository($pdo);
    $leadId = $repo->insert([
        'prenom'    => 'Test',
        'nom'       => 'Debug',
        'email'     => 'test@debug.fr',
        'telephone' => '0600000000',
    ], $profile);
    echo json_encode(['mysql' => 'OK', 'lead_id' => $leadId]) . "\n";
} catch (Exception $e) {
    echo json_encode(['mysql_error' => $e->getMessage()]) . "\n";
    exit;
}

// Test IA
try {
    $builder = new PromptBuilder();
    $prompt  = $builder->build($profile);
    $ai      = new AIService($apiKey);
    $raw     = $ai->ask($prompt);

    // Nettoyer
    $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
    $raw = preg_replace('/\s*```$/', '', $raw);

    $result = json_decode($raw, true);

    if (isset($result['recommendations'])) {
        $repo->updateWithAI($leadId, $result['recommendations'], $result['conseil_global'] ?? '');
        echo json_encode(['ia' => 'OK', 'nb_recommandations' => count($result['recommendations'])]) . "\n";
    } else {
        echo json_encode(['ia_error' => 'JSON invalide', 'raw' => substr($raw, 0, 500)]) . "\n";
    }
} catch (Exception $e) {
    echo json_encode(['ia_error' => $e->getMessage()]) . "\n";
}

echo json_encode(['status' => 'TERMINE - supprimer debug.php']) . "\n";
