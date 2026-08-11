<?php
declare(strict_types=1);

class AIService
{
    public function __construct(
        private string $apiKey,
        private string $model = 'claude-sonnet-4-6',
        private int $timeout = 60
    ) {}

    public function ask(string $prompt): string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 4000,
            'temperature' => 0.2,
            'system' => 'Tu réponds exclusivement avec un objet JSON valide. Aucun markdown, aucun commentaire, aucun texte avant ou après le JSON.',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','x-api-key: '.$this->apiKey,'anthropic-version: 2023-06-01'],
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch); curl_close($ch);
            throw new RuntimeException('Erreur cURL : '.$error);
        }
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $body = json_decode($response, true);
        if (!is_array($body)) throw new RuntimeException('Réponse Anthropic invalide.');
        if ($http < 200 || $http >= 300) throw new RuntimeException($body['error']['message'] ?? 'Erreur Anthropic HTTP '.$http);
        if (($body['stop_reason'] ?? '') === 'max_tokens') throw new RuntimeException('Réponse Claude tronquée.');
        $text = $body['content'][0]['text'] ?? '';
        if (!is_string($text) || trim($text) === '') throw new RuntimeException('Réponse Claude vide.');
        return trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text));
    }
}
