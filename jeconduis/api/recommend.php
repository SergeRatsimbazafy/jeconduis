<?php
/**
 * api/recommend.php
 * Point d'entrée AJAX — reçoit le profil + contact, sauvegarde en MySQL,
 * appelle Claude, retourne les recommandations IA.
 */

declare(strict_types=1);

// ── Headers ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$allowedOrigin = $_ENV['ALLOWED_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Autoloader ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/src/PromptBuilder.php';
require_once __DIR__ . '/src/AIService.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/LeadRepository.php';
require_once __DIR__ . '/src/MailService.php';

// ── Chargement .env ───────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Clé API non configurée.']);
    exit;
}

// ── Lecture body JSON ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

// ── Validation champs obligatoires ────────────────────────────────────────────
$required = ['budget', 'usage', 'motorisation', 'prenom', 'nom', 'email', 'telephone'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Champ obligatoire manquant : {$field}"]);
        exit;
    }
}

// ── Séparation : contact (MySQL) vs profil (IA) ───────────────────────────────
$contact = [
    'prenom'    => sanitize($data['prenom']),
    'nom'       => sanitize($data['nom']),
    'email'     => sanitize($data['email']),
    'telephone' => sanitize($data['telephone']),
];

$profile = [
    'delai'        => sanitize($data['delai']        ?? ''),
    'type_achat'   => sanitize($data['type_achat']   ?? ''),
    'usage'        => sanitize($data['usage']         ?? ''),
    'priorite'     => sanitize($data['priorite']      ?? ''),
    'kilometrage'  => sanitize($data['kilometrage']   ?? ''),
    'motorisation' => sanitize($data['motorisation']  ?? ''),
    'carrosserie'  => sanitize($data['carrosserie']   ?? ''),
    'marque'       => sanitize($data['marque']        ?? ''),
    'modele'       => sanitize($data['modele']        ?? ''),
    'budget'       => sanitize($data['budget']        ?? ''),
    // ❌ email / téléphone / nom / prénom : jamais envoyés à l'IA
];

// ── Étape 1 : Sauvegarde MySQL du lead (avant appel IA) ──────────────────────
$leadId = null;
try {
    $pdo        = Database::connect();
    $repository = new LeadRepository($pdo);
    $leadId     = $repository->insert($contact, $profile);
} catch (Exception $e) {
    // On log mais on ne bloque pas — l'IA peut quand même répondre
    error_log('[JeConduis DB] Erreur insertion lead : ' . $e->getMessage());
}

// ── Étape 2 : Appel API Claude ────────────────────────────────────────────────
try {
    $builder  = new PromptBuilder();
    $prompt   = $builder->build($profile);

    $ai       = new AIService($apiKey);
    $rawText  = $ai->ask($prompt);
    file_put_contents(
    __DIR__ . '/logs/last_ai_response.json',
    $rawText
);

    // Nettoyer les backticks markdown éventuels
    $rawText  = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
    $rawText  = preg_replace('/\s*```$/', '', $rawText);

    $result = json_decode($rawText, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
    throw new RuntimeException(
        "JSON invalide : "
        . json_last_error_msg()
        . "\nStop de Claude ou réponse incomplète.\n\n"
        . $rawText
        );
    }

    if (!isset($result['recommendations']) || !is_array($result['recommendations'])) {
        throw new RuntimeException('Structure JSON inattendue');
    }

    // ── Étape 3 : Mise à jour MySQL avec les recommandations IA ──────────────
    if ($leadId && isset($repository)) {
        try {
            $repository->updateWithAI(
                $leadId,
                $result['recommendations'],
                $result['conseil_global'] ?? ''
            );
        } catch (Exception $e) {
            error_log('[JeConduis DB] Erreur update IA : ' . $e->getMessage());
        }
    }
    // ── Étape 4 : Envoi de l'e-mail ───────────────────────────────────────
    try {

    $mail = new MailService();

    $mail->sendRecommendation(
        $contact,
        $profile,
        $result['recommendations'],
        $result['conseil_global'] ?? '',
        $result['budget_analyse'] ?? ''
        );

    } catch (Exception $e) {

    error_log('[MAIL] ' . $e->getMessage());

}

    // ── Retour JSON au frontend ───────────────────────────────────────────────
    http_response_code(200);
    echo json_encode([
        'success'         => true,
        'recommendations' => $result['recommendations'],
        'conseil_global'  => $result['conseil_global']  ?? '',
        'budget_analyse'  => $result['budget_analyse']  ?? '',
    ]);

} catch (RuntimeException $e) {
    error_log('[JeConduis AI] ' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Notre conseiller IA est momentanément indisponible. Votre demande a bien été enregistrée et un conseiller vous contactera sous 24h.',
    ]);
}

// ── Helper ────────────────────────────────────────────────────────────────────
function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}
