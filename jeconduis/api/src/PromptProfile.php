<?php
declare(strict_types=1);

class PromptProfile
{
    public function build(array $profile): array
    {
        $keys = [
            'delai','type_achat','financement','budget','nb_personnes','usage','kilometrage',
            'motorisation','borne_recharge','boite','priorite','carrosserie','duree_conservation','note_libre'
        ];

        $out = [];
        foreach ($keys as $key) {
            $value = $profile[$key] ?? '';
            $out[$key] = is_scalar($value) ? trim((string)$value) : $value;
        }

        $brands = $profile['marques_modeles'] ?? [];
        if (is_string($brands)) {
            $decoded = json_decode($brands, true);
            $brands = is_array($decoded) ? $decoded : [];
        }
        $out['marques_modeles'] = is_array($brands) ? $brands : [];

        return $out;
    }
}
