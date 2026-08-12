<?php

declare(strict_types=1);

class PromptBuilder
{
    public function build(array $profile): string
    {
        $lines = [];

        foreach ($profile as $key => $value) {
            if ($key === 'marques_modeles') {
                continue;
            }

            if (!is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $label = Labels::get((string) $value);
            $fieldLabel = ucfirst(str_replace('_', ' ', $key));
            $lines[] = $fieldLabel . ' : ' . $label;
        }

        $brandLines = [];
        $brands = $profile['marques_modeles'] ?? [];

        if (is_array($brands)) {
            foreach ($brands as $brand => $models) {
                $brand = trim((string) $brand);
                if ($brand === '') {
                    continue;
                }

                $models = is_array($models)
                    ? array_values(array_filter(array_map(
                        static fn ($model): string => trim((string) $model),
                        $models
                    ), static fn (string $model): bool => $model !== ''))
                    : [];

                $brandLines[] = $models
                    ? '- ' . $brand . ' : ' . implode(', ', $models)
                    : '- ' . $brand . ' : aucun modèle précis sélectionné';
            }
        }

        $brandSection = $brandLines
            ? "\nMARQUES / MODÈLES PRÉFÉRÉS :\n" . implode("\n", $brandLines) . "\n"
            : "\nMARQUES / MODÈLES PRÉFÉRÉS : aucune préférence renseignée\n";

        $schema = <<<'JSON'
{
  "recommendations":[
    {"rank":1,"marque":"","modele":"","version":"","carrosserie":"","motorisation":"","prix_neuf_min":0,"prix_neuf_max":0,"prix_occasion_min":0,"prix_occasion_max":0,"score":0,"justification":"","points_forts":["","",""],"point_vigilance":""},
    {"rank":2,"marque":"","modele":"","version":"","carrosserie":"","motorisation":"","prix_neuf_min":0,"prix_neuf_max":0,"prix_occasion_min":0,"prix_occasion_max":0,"score":0,"justification":"","points_forts":["","",""],"point_vigilance":""},
    {"rank":3,"marque":"","modele":"","version":"","carrosserie":"","motorisation":"","prix_neuf_min":0,"prix_neuf_max":0,"prix_occasion_min":0,"prix_occasion_max":0,"score":0,"justification":"","points_forts":["","",""],"point_vigilance":""}
  ],
  "conseil_global":"",
  "budget_analyse":""
}
JSON;

        $typeAchat = (string) ($profile['type_achat'] ?? '');

        $priceRule = match ($typeAchat) {
            'occasion' => "Si type_achat = occasion : prix_neuf_min = 0 et prix_neuf_max = 0 pour les 3 véhicules.",
            'neuf' => "Si type_achat = neuf : prix_occasion_min = 0 et prix_occasion_max = 0 pour les 3 véhicules.",
            'les2' => "Si type_achat = neuf ou occasion : renseigner les fourchettes neuf et occasion lorsque suffisamment fiables.",
            default => "Respecter le type d'achat indiqué et utiliser 0 pour une catégorie de prix non pertinente ou inconnue.",
        };

        return "Tu es un expert automobile français.\n\n" .
            "PROFIL CLIENT :\n" . implode("\n", $lines) . "\n" .
            $brandSection . "\n" .
            "INTERPRÉTATION DES PRÉFÉRENCES :\n" .
            "- Les marques et modèles sélectionnés sont des préférences explicites du client.\n" .
            "- Priorise un modèle sélectionné lorsqu'il respecte réellement le budget, l'usage, la motorisation, la carrosserie et les autres critères.\n" .
            "- Ne recommande pas un modèle sélectionné s'il est objectivement incompatible avec le profil; dans ce cas, proposer une alternative pertinente et l'expliquer dans la justification.\n" .
            "- Si plusieurs modèles sélectionnés sont compatibles, ils peuvent apparaître parmi les 3 recommandations.\n\n" .
            "RÈGLE PRIX SELON TYPE D'ACHAT :\n" . $priceRule . "\n\n" .
            "RÈGLES :\n" . (new PromptRules())->text() . "\n\n" .
            (new FranceRules())->text() . "\n\n" .
            "RÉPONDS UNIQUEMENT AVEC UN OBJET JSON VALIDE. Aucun markdown, commentaire ou texte avant/après.\n" .
            "Toutes les chaînes doivent être des chaînes JSON valides et tous les prix/scores des nombres.\n" .
            "Si une donnée est inconnue, utiliser une chaîne vide ou 0.\n\nSCHEMA JSON :\n" . $schema;
    }
}
