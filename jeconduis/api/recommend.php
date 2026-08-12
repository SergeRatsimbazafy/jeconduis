<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Méthode non autorisée.', 405);
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

foreach ([
    'Labels', 'PromptProfile', 'PromptRules', 'FranceRules', 'PromptBuilder',
    'AIService', 'RecommendationFormatter', 'RecommendationPdf',
    'MailService', 'LeadRepository'
] as $class) {
    $file = __DIR__ . '/src/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
}

$apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '');
if ($apiKey === '') {
    respond(false, 'Clé API Anthropic non configurée.', 500);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    respond(false, 'JSON invalide.', 400);
}

$contact = is_array($data['contact'] ?? null) ? $data['contact'] : [];
$profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];
$consent = is_array($data['consent'] ?? null) ? $data['consent'] : [];

$email = trim((string) ($contact['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Adresse email invalide.', 422);
}

$contact['email'] = $email;
$contact['prenom'] = trim((string) ($contact['prenom'] ?? ''));
$contact['nom'] = trim((string) ($contact['nom'] ?? ''));
$contact['telephone'] = trim((string) ($contact['telephone'] ?? ''));
$contact['ville'] = trim((string) ($contact['ville'] ?? ''));
$contact['codepostal'] = trim((string) ($contact['codepostal'] ?? ''));

if (($consent['optin_cgu'] ?? false) !== true) {
    respond(false, 'L’acceptation des conditions est obligatoire.', 422);
}

try {
    $profileData = (new PromptProfile())->build($profile);
    $prompt = (new PromptBuilder())->build($profileData);

    $rawText = (new AIService($apiKey))->ask($prompt);
    $result = decodeJson($rawText);
    validateResult($result);

    $formatter = new RecommendationFormatter();
    $formatted = $formatter->format($result, $profileData);

    $pdfPath = '';
    try {
        $pdfPath = (new RecommendationPdf())->generate(
            $result,
            $profileData,
            $contact
        );
    } catch (Throwable $e) {
        error_log('[JeConduis PDF] ' . $e->getMessage());
    }

    try {
    $mailSent = (new MailService())->sendRecommendation(
        $contact,
        $profileData,
        $formatted['recommendations'],
        $formatted['conseil_global'],
        $formatted['budget_analyse'],
        $pdfPath !== '' ? $pdfPath : null
    );

    if (!$mailSent) {
        throw new RuntimeException('Je-Conduis.com n’a pas pu envoyer le message.');
    }

    } catch (Throwable $e) {
    error_log('[JeConduis MAIL] ' . $e->getMessage());

    respond(
        false,
        'La recommandation a été générée mais l’email n’a pas pu être envoyé.',
        502
    );
    }

    $leadId = null;
    try {
        $pdo = createPdoFromEnvironment();
        if ($pdo instanceof PDO) {
            $leadId = (new LeadRepository($pdo))->create(
                $contact,
                $profileData,
                $pdfPath !== '' ? $pdfPath : null
            );
        }
    } catch (Throwable $e) {
        error_log('[JeConduis DB] ' . $e->getMessage());
    }

    respond(true, 'Votre recommandation a été envoyée par email.', 200, [
        'recommendations' => $result['recommendations'],
        'conseil_global' => $result['conseil_global'] ?? '',
        'budget_analyse' => $result['budget_analyse'] ?? '',
        'email' => maskEmail($email),
        'pdf_generated' => $pdfPath !== '',
        'lead_id' => $leadId
    ]);
} catch (Throwable $e) {
    error_log('[JeConduis V2] ' . $e->getMessage());
    respond(false, 'Notre conseiller IA est momentanément indisponible.', 502);
}

function createPdoFromEnvironment(): ?PDO
{
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');

    if ($host === '' || $name === '' || $user === '') {
        return null;
    }

    return new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function decodeJson(string $text): array
{
    $text = trim($text);
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);

    $result = json_decode($text, true);
    if (is_array($result)) {
        return $result;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $result = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($result)) {
            return $result;
        }
    }

    throw new RuntimeException('JSON IA invalide : ' . json_last_error_msg());
}

function validateResult(array $result): void
{
    if (!isset($result['recommendations']) || !is_array($result['recommendations']) || count($result['recommendations']) !== 3) {
        throw new RuntimeException('Le JSON doit contenir exactement 3 recommandations.');
    }

    $seenRanks = [];

    foreach ($result['recommendations'] as $index => $item) {
        if (!is_array($item)) {
            throw new RuntimeException('Recommandation #' . ($index + 1) . ' invalide.');
        }

        foreach ([
            'rank', 'marque', 'modele', 'version', 'carrosserie', 'motorisation',
            'prix_neuf_min', 'prix_neuf_max', 'prix_occasion_min', 'prix_occasion_max',
            'score', 'justification', 'points_forts', 'point_vigilance'
        ] as $key) {
            if (!array_key_exists($key, $item)) {
                throw new RuntimeException('Champ manquant : ' . $key);
            }
        }

        $rank = (int) $item['rank'];
        if ($rank < 1 || $rank > 3 || isset($seenRanks[$rank])) {
            throw new RuntimeException('Rang invalide ou dupliqué.');
        }
        $seenRanks[$rank] = true;

        foreach (['prix_neuf_min', 'prix_neuf_max', 'prix_occasion_min', 'prix_occasion_max'] as $priceKey) {
            if (!is_int($item[$priceKey]) && !is_float($item[$priceKey])) {
                throw new RuntimeException('Prix invalide : ' . $priceKey);
            }
            if ((float) $item[$priceKey] < 0) {
                throw new RuntimeException('Prix négatif interdit : ' . $priceKey);
            }
        }

        if ((float) $item['prix_neuf_min'] > (float) $item['prix_neuf_max'] && (float) $item['prix_neuf_max'] > 0) {
            throw new RuntimeException('Fourchette de prix neuf invalide.');
        }
        if ((float) $item['prix_occasion_min'] > (float) $item['prix_occasion_max'] && (float) $item['prix_occasion_max'] > 0) {
            throw new RuntimeException('Fourchette de prix occasion invalide.');
        }

        if (!is_array($item['points_forts']) || count($item['points_forts']) !== 3) {
            throw new RuntimeException('points_forts invalide.');
        }
        foreach ($item['points_forts'] as $point) {
            if (!is_string($point) || trim($point) === '') {
                throw new RuntimeException('Chaque point fort doit être une chaîne non vide.');
            }
        }

        $score = (int) $item['score'];
        if ($score < 80 || $score > 100) {
            throw new RuntimeException('Score invalide.');
        }
    }

    ksort($seenRanks);
    if (array_keys($seenRanks) !== [1, 2, 3]) {
        throw new RuntimeException('Les rangs doivent être exactement 1, 2 et 3.');
    }

    foreach (['conseil_global', 'budget_analyse'] as $key) {
        if (!array_key_exists($key, $result) || !is_string($result[$key])) {
            throw new RuntimeException('Champ global manquant ou invalide : ' . $key);
        }
    }
}

function maskEmail(string $email): string
{
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    if ($domain === '') {
        return '';
    }

    $maskedLocal = strlen($local) <= 2
        ? substr($local, 0, 1) . '*'
        : substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));

    return $maskedLocal . '@' . $domain;
}

function respond(bool $success, string $message, int $status = 200, array $extra = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
