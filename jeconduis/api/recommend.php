<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { respond(false, 'Méthode non autorisée.', 405); }

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;
foreach (['Labels','PromptProfile','PromptRules','FranceRules','PromptBuilder','AIService','RecommendationFormatter','RecommendationPdf','MailService','LeadRepository'] as $class) {
    $file = __DIR__ . '/src/' . $class . '.php';
    if (is_file($file)) require_once $file;
}

$apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '');
if ($apiKey === '') respond(false, 'Clé API Anthropic non configurée.', 500);

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) respond(false, 'JSON invalide.', 400);

$contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
$profile = is_array($data['profile'] ?? null) ? $data['profile'] : $data;

$email = trim((string)($contact['email'] ?? $data['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Adresse email invalide.', 422);
$contact['email'] = $email;
$contact['prenom'] = trim((string)($contact['prenom'] ?? ''));
$contact['nom'] = trim((string)($contact['nom'] ?? ''));
$contact['telephone'] = trim((string)($contact['telephone'] ?? ''));

try {
    $profileData = (new PromptProfile())->build($profile);
    $prompt = (new PromptBuilder())->build($profileData);
    $rawText = (new AIService($apiKey))->ask($prompt);
    $result = decodeJson($rawText);
    validateResult($result);

    $formatted = (new RecommendationFormatter())->format($result, $profileData);

    $pdfPath = '';
    if (class_exists('RecommendationPdf')) {
        try { $pdfPath = (new RecommendationPdf())->generate($result, $profileData, $contact); }
        catch (Throwable $e) { error_log('[JeConduis PDF] '.$e->getMessage()); }
    }

    try {
        (new MailService())->sendRecommendation($contact, $formatted, $pdfPath);
    } catch (Throwable $e) {
        error_log('[JeConduis MAIL] '.$e->getMessage());
        respond(false, 'La recommandation a été générée mais l’email n’a pas pu être envoyé.', 502);
    }

    try {
        $repo = new LeadRepository();
        if (method_exists($repo, 'save')) {
            $repo->save([
                'contact' => $contact,
                'profile' => $profileData,
                'recommendations' => $result['recommendations'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Throwable $e) {
        error_log('[JeConduis DB] '.$e->getMessage());
    }

    respond(true, 'Votre recommandation a été envoyée par email.', 200, [
        'recommendations' => $result['recommendations'],
        'conseil_global' => $result['conseil_global'] ?? '',
        'budget_analyse' => $result['budget_analyse'] ?? '',
        'email' => maskEmail($email),
        'pdf_generated' => $pdfPath !== ''
    ]);
} catch (Throwable $e) {
    error_log('[JeConduis V2] '.$e->getMessage());
    respond(false, 'Notre conseiller IA est momentanément indisponible.', 502);
}

function decodeJson(string $text): array {
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));
    $result = json_decode($text, true);
    if (is_array($result)) return $result;
    $start = strpos($text, '{'); $end = strrpos($text, '}');
    if ($start !== false && $end !== false) {
        $result = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($result)) return $result;
    }
    throw new RuntimeException('JSON IA invalide : '.json_last_error_msg());
}

function validateResult(array $result): void {
    if (!isset($result['recommendations']) || !is_array($result['recommendations']) || count($result['recommendations']) !== 3) {
        throw new RuntimeException('Le JSON doit contenir exactement 3 recommandations.');
    }
    foreach ($result['recommendations'] as $item) {
        foreach (['rank','marque','modele','version','carrosserie','motorisation','score','justification','points_forts','point_vigilance'] as $key) {
            if (!array_key_exists($key, $item)) throw new RuntimeException('Champ manquant : '.$key);
        }
        if (!is_array($item['points_forts']) || count($item['points_forts']) !== 3) throw new RuntimeException('points_forts invalide.');
        if ((int)$item['score'] < 80 || (int)$item['score'] > 100) throw new RuntimeException('Score invalide.');
    }
}

function maskEmail(string $email): string {
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    if ($domain === '') return '';
    return (strlen($local) <= 2 ? substr($local, 0, 1).' *' : substr($local, 0, 2).str_repeat('*', max(1, strlen($local)-2))) . '@' . $domain;
}

function respond(bool $success, string $message, int $status = 200, array $extra = []): never {
    http_response_code($status);
    echo json_encode(array_merge(['success'=>$success,'message'=>$message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
