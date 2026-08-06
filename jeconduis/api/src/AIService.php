<?php
/**
 * api/src/AIService.php
 * Service Anthropic Claude robuste.
 */

class AIService
{
    private string $apiKey;

    // Remplace par le modèle exact auquel ta clé API donne accès.
    private string $model = 'claude-sonnet-4-6';

    private int $timeout = 60;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Envoie un prompt et retourne uniquement le texte généré.
     *
     * @throws RuntimeException
     */
    public function ask(string $prompt): string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 3000,
            'temperature' => 0.2,
            'system' =>
                "Tu réponds exclusivement avec un objet JSON valide.
Aucun markdown.
Aucun commentaire.
Aucun texte avant ou après le JSON.",

            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ]
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Erreur cURL : {$err}");
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $body = json_decode($response, true);

        if (!is_array($body)) {
            throw new RuntimeException(
                "Réponse API invalide :\n" . $response
            );
        }

        if ($http !== 200) {

            $msg = $body['error']['message']
                ?? "HTTP {$http}";

            throw new RuntimeException($msg);
        }

        if (($body['stop_reason'] ?? '') === 'max_tokens') {

            throw new RuntimeException(
                'Claude a interrompu sa réponse (max_tokens atteint).'
            );
        }

        if (
            empty($body['content'])
            || !isset($body['content'][0]['text'])
        ) {
            throw new RuntimeException(
                'Réponse Claude vide.'
            );
        }

        $text = trim($body['content'][0]['text']);

        // Nettoyage éventuel si Claude renvoie encore des fences Markdown.
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/', '', $text);
        $text = preg_replace('/```$/', '', $text);

        return trim($text);
    }
}