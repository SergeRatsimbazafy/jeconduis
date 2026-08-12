<?php

declare(strict_types=1);

class PromptProfile
{
    public function build(array $profile): array
    {
        $keys = [
            'delai',
            'type_achat',
            'financement',
            'budget',
            'nb_personnes',
            'usage',
            'kilometrage',
            'motorisation',
            'borne_recharge',
            'boite',
            'priorite',
            'carrosserie',
            'duree_conservation',
            'note_libre',
        ];

        $out = [];

        foreach ($keys as $key) {
            $value = $profile[$key] ?? '';

            if (is_scalar($value)) {
                $out[$key] = trim((string) $value);
            } else {
                $out[$key] = '';
            }
        }

        $brands = $profile['marques_modeles'] ?? [];

        if (is_string($brands)) {
            $decoded = json_decode($brands, true);
            $brands = is_array($decoded) ? $decoded : [];
        }

        $normalizedBrands = [];

        if (is_array($brands)) {
            foreach ($brands as $brand => $models) {
                $brand = trim((string) $brand);

                if ($brand === '') {
                    continue;
                }

                if (!is_array($models)) {
                    $models = [];
                }

                $models = array_values(array_unique(array_filter(array_map(
                    static fn ($model): string => trim((string) $model),
                    $models
                ), static fn (string $model): bool => $model !== '')));

                $normalizedBrands[$brand] = $models;
            }
        }

        $out['marques_modeles'] = $normalizedBrands;

        return $out;
    }
}
