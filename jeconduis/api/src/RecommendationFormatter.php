<?php

declare(strict_types=1);

/**
 * Prépare les données de recommandation pour l'email et le PDF.
 */
class RecommendationFormatter
{
    public function format(array $result, array $profile = []): array
    {
        $recommendations = [];

        foreach ($result['recommendations'] ?? [] as $index => $recommendation) {
            $recommendations[] = [
                'rank' => (int) ($recommendation['rank'] ?? ($index + 1)),
                'marque' => trim((string) ($recommendation['marque'] ?? '')),
                'modele' => trim((string) ($recommendation['modele'] ?? '')),
                'version' => trim((string) ($recommendation['version'] ?? '')),
                'carrosserie' => trim((string) ($recommendation['carrosserie'] ?? '')),
                'motorisation' => trim((string) ($recommendation['motorisation'] ?? '')),
                'prix_neuf_min' => (int) ($recommendation['prix_neuf_min'] ?? 0),
                'prix_neuf_max' => (int) ($recommendation['prix_neuf_max'] ?? 0),
                'prix_occasion_min' => (int) ($recommendation['prix_occasion_min'] ?? 0),
                'prix_occasion_max' => (int) ($recommendation['prix_occasion_max'] ?? 0),
                'score' => max(0, min(100, (int) ($recommendation['score'] ?? 0))),
                'justification' => trim((string) ($recommendation['justification'] ?? '')),
                'points_forts' => array_values(array_map(
                    static fn ($value): string => trim((string) $value),
                    array_slice(is_array($recommendation['points_forts'] ?? null) ? $recommendation['points_forts'] : [], 0, 3)
                )),
                'point_vigilance' => trim((string) ($recommendation['point_vigilance'] ?? '')),
            ];
        }

        return [
            'recommendations' => $recommendations,
            'conseil_global' => trim((string) ($result['conseil_global'] ?? '')),
            'budget_analyse' => trim((string) ($result['budget_analyse'] ?? '')),
            'profile' => $profile,
        ];
    }

    public function money(int $value): string
    {
        return $value > 0
            ? number_format($value, 0, ',', ' ') . ' €'
            : '—';
    }

    public function priceRange(array $recommendation, string $type): string
    {
        $min = (int) ($recommendation["prix_{$type}_min"] ?? 0);
        $max = (int) ($recommendation["prix_{$type}_max"] ?? 0);

        if ($min <= 0 && $max <= 0) {
            return '—';
        }

        if ($min > 0 && $max > 0 && $min !== $max) {
            return $this->money($min) . ' – ' . $this->money($max);
        }

        return $this->money($max > 0 ? $max : $min);
    }
}
