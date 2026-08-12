<?php

declare(strict_types=1);

class Labels
{
    public static function all(): array
    {
        return [
            // Délai
            'mois' => 'ce mois-ci',
            '3mois' => 'dans les 3 mois',
            '6mois' => 'dans les 6 mois',
            'annee' => "l'année prochaine",
            'renseigne' => 'en phase de renseignement',

            // Type d'achat
            'neuf' => 'neuf uniquement',
            'occasion' => 'occasion uniquement',
            'les2' => 'neuf ou occasion',

            // Financement
            'comptant' => 'paiement comptant',
            'credit' => 'crédit automobile',
            'loa' => 'LOA / leasing avec option d’achat',
            'lld' => 'LLD / location longue durée',

            // Nombre de personnes
            '1' => '1 personne le plus souvent',
            '2' => '2 personnes le plus souvent',
            '3-4' => '3 à 4 personnes le plus souvent',
            '5+' => '5 personnes ou plus le plus souvent',

            // Usage
            'travail' => 'trajets domicile-travail quotidiens',
            'pro' => 'usage professionnel',
            'famille' => 'usage familial',
            'voyages' => 'longs trajets et vacances',
            'ville' => 'principalement en ville',
            'mixte' => 'mixte ville et route',

            // Priorité
            'economie' => 'économie de consommation et d’entretien',
            'fiabilite' => 'fiabilité',
            'confort' => 'confort',
            'design' => 'design et style',
            'espace' => 'espace intérieur et volume de coffre',
            'perf' => 'performances',
            'securite' => 'sécurité',

            // Kilométrage
            'moins10k' => 'moins de 10 000 km/an',
            '10-20k' => '10 000 à 20 000 km/an',
            '20-30k' => '20 000 à 30 000 km/an',
            'plus30k' => 'plus de 30 000 km/an',

            // Motorisation
            'essence' => 'essence',
            'diesel' => 'diesel',
            'hybride' => 'hybride',
            'hybride-rechargeable' => 'hybride rechargeable (PHEV)',
            'electrique' => 'électrique',
            'gpl' => 'GPL',
            'saitpas' => 'sans préférence de motorisation pour le moment',

            // Recharge
            'domicile' => 'borne ou recharge disponible à domicile',
            'travail_borne' => 'borne ou recharge disponible au travail',
            'les2_borne' => 'recharge disponible à domicile et au travail',
            'non' => 'aucun accès régulier à une borne de recharge',

            // Boîte de vitesses
            'automatique' => 'boîte automatique',
            'manuelle' => 'boîte manuelle',
            'indifferent' => 'sans préférence de boîte de vitesses',

            // Carrosserie
            'citadine' => 'citadine',
            'berline' => 'berline',
            'suv' => 'SUV / 4×4',
            'break' => 'break',
            'monospace' => 'monospace',
            'pickup' => 'pickup',
            'utilitaire' => 'utilitaire',
            'coupe' => 'coupé / sport',
            'cabriolet' => 'cabriolet',
            'fourgon' => 'fourgon',

            // Budget
            'moins-15k' => 'moins de 15 000 €',
            '15k-25k' => '15 000 € à 25 000 €',
            '25k-40k' => '25 000 € à 40 000 €',
            'plus-40k' => '40 000 € et plus',

            // Durée de conservation
            'moins3ans' => 'moins de 3 ans',
            '3-5ans' => '3 à 5 ans',
            '5-10ans' => '5 à 10 ans',
            'plus10ans' => 'plus de 10 ans',
        ];
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? $key;
    }
}
