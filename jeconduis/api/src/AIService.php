<?php

declare(strict_types=1);

class AIService
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(
        string $apiKey,
        ?string $model = null,
        int $timeout = 60
    ) {
        $this->apiKey = trim($apiKey);
        $this->model = $model
            ?: (getenv('ANTHROPIC_MODEL') ?: ($_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-4-20250514'));
        $this->timeout = $timeout;

        if ($this->apiKey === '') {
            throw new InvalidArgumentException('Clé API Anthropic manquante.');
        }
    }

    /**
     * Envoie un prompt à l'API Messages Anthropic et retourne uniquement
     * le texte JSON généré par Claude.
     */
    public function ask(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new InvalidArgumentException('Prompt vide.');
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => 4000,
            'temperature' => 0.2,
            'system' => implode("\n", [
                'Tu réponds exclusivement avec un objet JSON valide.',
                'Aucun markdown.',
                'Aucun commentaire.',
                'Aucun texte avant ou après le JSON.',
                'Respecte strictement le schéma JSON demandé dans le prompt utilisateur.'
            ]),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        try {
            $encodedPayload = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Impossible d\'encoder la requête Anthropic : ' . $e->getMessage(),
                0,
                $e
            );
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');

        if ($ch === false) {
            throw new RuntimeException('Impossible d\'initialiser cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POSTFIELDS => $encodedPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            throw new RuntimeException(
                "Erreur cURL Anthropic ({$errno}) : {$error}"
            );
        }

        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        try {
            $body = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Réponse Anthropic non JSON : ' . $e->getMessage(),
                0,
                $e
            );
        }

        if (!is_array($body)) {
            throw new RuntimeException('Réponse Anthropic invalide.');
        }

        if ($http < 200 || $http >= 300) {
            $message = $body['error']['message'] ?? ('Erreur Anthropic HTTP ' . $http);
            throw new RuntimeException($message);
        }

        $stopReason = (string) ($body['stop_reason'] ?? '');

        if ($stopReason === 'max_tokens') {
            throw new RuntimeException(
                'Réponse Claude tronquée : limite max_tokens atteinte.'
            );
        }

        if ($stopReason === 'refusal') {
            throw new RuntimeException('Claude a refusé de traiter la requête.');
        }

        if ($stopReason === 'pause_turn') {
            throw new RuntimeException(
                'Claude a interrompu temporairement le tour de génération.'
            );
        }

        $textParts = [];

        foreach (($body['content'] ?? []) as $block) {
            if (
                is_array($block) &&
                ($block['type'] ?? '') === 'text' &&
                isset($block['text']) &&
                is_string($block['text'])
            ) {
                $textParts[] = $block['text'];
            }
        }

        $text = trim(implode("\n", $textParts));

        if ($text === '') {
            throw new RuntimeException('Réponse Claude vide ou sans bloc texte.');
        }

        // Nettoyage défensif si le modèle ajoute malgré tout des fences Markdown.
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        return trim($text);
    }
}
