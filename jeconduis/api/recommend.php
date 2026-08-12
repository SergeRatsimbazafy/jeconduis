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
    'Labels',
    'PromptProfile',
    'PromptRules',
    'FranceRules',
    'PromptBuilder',
    'AIService',
    'RecommendationFormatter',
    'RecommendationPdf',
    'MailService',
    'LeadRepository'
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
    // 1. Normalisation du profil questionnaire.
    $profileData = (new PromptProfile())->build($profile);

    // 2. Construction du prompt V2.
    $prompt = (new PromptBuilder())->build($profileData);

    // 3. Appel Claude.
    $rawText = (new AIService($apiKey))->ask($prompt);

    // 4. Décodage + validation stricte du JSON V2.
    $result = decodeJson($rawText);
    validateResult($result);

    // 5. Préparation commune email / PDF.
    $formatter = new RecommendationFormatter();
    $formatted = $formatter->format($result, $profileData);

    // 6. Génération du PDF.
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

    // 7. Envoi email.
    // Signature V2 : contact + recommandations + conseil + analyse budget + PDF.
    try {
        (new MailService())->sendRecommendation(
            $contact,
            $formatted['recommendations'],
            $formatted['conseil_global'],
            $formatted['budget_analyse'],
            $pdfPath !== '' ? $pdfPath : null
        );
    } catch (Throwable $e) {
        error_log('[JeConduis MAIL] ' . $e->getMessage());
        respond(
            false,
            'La recommandation a été générée mais l’email n’a pas pu être envoyé.',
            502
        );
    }

    // 8. Sauvegarde SQL facultative : l'échec SQL ne bloque pas l'envoi.
    $leadId = null;

    try {
        $pdo = createPdoFromEnvironment();

        if ($pdo instanceof PDO) {
            $repository = new LeadRepository($pdo);

            $leadId = $repository->create(
                $contact,
                $profileData,
                $pdfPath !== '' ? $pdfPath : null
            );
        }
    } catch (Throwable $e) {
        error_log('[JeConduis DB] ' . $e->getMessage());
    }

    // 9. Réponse destinée à recommendation_auto.html.
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

    respond(
        false,
        'Notre conseiller IA est momentanément indisponible.',
        502
    );
}

/**
 * Connexion PDO à partir des variables .env / environnement serveur.
 * Variables attendues :
 * DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 */
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

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name
    );

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function decodeJson(string $text): array
{
    $text = trim($text);

    $text = preg_replace(
        '/^```(?:json)?\s*|\s*```$/i',
        '',
        $text
    );

    $result = json_decode($text, true);

    if (is_array($result)) {
        return $result;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');

    if ($start !== false && $end !== false && $end > $start) {
        $json = substr($text, $start, $end - $start + 1);
        $result = json_decode($json, true);

        if (is_array($result)) {
            return $result;
        }
    }

    throw new RuntimeException(
        'JSON IA invalide : ' . json_last_error_msg()
    );
}

function validateResult(array $result): void
{
    if (
        !isset($result['recommendations']) ||
        !is_array($result['recommendations']) ||
        count($result['recommendations']) !== 3
    ) {
        throw new RuntimeException(
            'Le JSON doit contenir exactement 3 recommandations.'
        );
    }

    foreach ($result['recommendations'] as $index => $item) {
        if (!is_array($item)) {
            throw new RuntimeException(
                'Recommandation #' . ($index + 1) . ' invalide.'
            );
        }

        foreach ([
            'rank',
            'marque',
            'modele',
            'version',
            'carrosserie',
            'motorisation',
            'score',
            'justification',
            'points_forts',
            'point_vigilance'
        ] as $key) {
            if (!array_key_exists($key, $item)) {
                throw new RuntimeException(
                    'Champ manquant : ' . $key
                );
            }
        }

        if (
            !is_array($item['points_forts']) ||
            count($item['points_forts']) !== 3
        ) {
            throw new RuntimeException('points_forts invalide.');
        }

        $score = (int) $item['score'];

        if ($score < 80 || $score > 100) {
            throw new RuntimeException('Score invalide.');
        }
    }

    if (!isset($result['conseil_global'])) {
        $result['conseil_global'] = '';
    }

    if (!isset($result['budget_analyse'])) {
        $result['budget_analyse'] = '';
    }
}

function maskEmail(string $email): string
{
    [$local, $domain] = array_pad(
        explode('@', $email, 2),
        2,
        ''
    );

    if ($domain === '') {
        return '';
    }

    if (strlen($local) <= 2) {
        $maskedLocal = substr($local, 0, 1) . '*';
    } else {
        $maskedLocal = substr($local, 0, 2)
            . str_repeat('*', max(1, strlen($local) - 2));
    }

    return $maskedLocal . '@' . $domain;
}

function respond(
    bool $success,
    string $message,
    int $status = 200,
    array $extra = []
): never {
    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}
