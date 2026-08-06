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

echo "<h2>Diagnostic Je-Conduis.com</h2>";
echo "<pre style='font-family:monospace;font-size:14px;background:#1e293b;color:#f1f5f9;padding:20px;border-radius:8px'>";

// Test 1 : PHP
echo "PHP Version : " . PHP_VERSION . "\n";
echo (PHP_MAJOR_VERSION >= 8 ? "OK PHP 8+\n" : "ERREUR PHP 8 requis\n") . "\n";

// Test 2 : cURL
echo "cURL : ";
echo extension_loaded('curl') ? "OK Active\n\n" : "ERREUR DESACTIVEE - contacter hebergeur\n\n";

// Test 3 : .env et cle API
$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
echo "Fichier .env : " . (file_exists($envFile) ? "OK Trouve\n" : "ERREUR Introuvable\n");
echo "Cle API : " . (!empty($apiKey) && str_starts_with($apiKey, 'sk-ant-') ? "OK (" . substr($apiKey,0,20) . "...)\n\n" : "ERREUR Manquante\n\n");

// Test 4 : MySQL
echo "MySQL : ";
try {
    $pdo = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST']??'127.0.0.1') . ";dbname=" . ($_ENV['DB_NAME']??'') . ";charset=utf8mb4",
        $_ENV['DB_USER'] ?? '', $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "OK Connexion reussie\n";
    $t = $pdo->query("SHOW TABLES LIKE 'leads'")->fetchAll();
    echo "Table leads : " . (count($t) > 0 ? "OK Existe\n\n" : "ERREUR MANQUANTE - importer database.sql\n\n");
} catch (PDOException $e) {
    echo "ERREUR " . $e->getMessage() . "\n\n";
}

// Test 5 : Appel Claude
echo "Appel API Claude : ";
if (!extension_loaded('curl') || empty($apiKey)) {
    echo "IGNORE\n";
} else {
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['model'=>'claude-haiku-4-5-20251001','max_tokens'=>10,'messages'=>[['role'=>'user','content'=>'Dis OK']]]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "ERREUR cURL : " . $curlErr . "\n";
        echo "  -> Hebergeur bloque les connexions sortantes vers api.anthropic.com\n";
    } elseif ($httpCode === 200) {
        $body = json_decode($response, true);
        echo "OK HTTP 200 - Reponse : " . ($body['content'][0]['text'] ?? '?') . "\n";
    } elseif ($httpCode === 401) {
        echo "ERREUR Cle API invalide (HTTP 401) - Regenerer sur console.anthropic.com\n";
    } else {
        $body = json_decode($response, true);
        echo "ERREUR HTTP " . $httpCode . " - " . ($body['error']['message'] ?? $response) . "\n";
    }
}

echo "\n!!! SUPPRIMER CE FICHIER test.php APRES LES TESTS !!!\n";
echo "</pre>";
