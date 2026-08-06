<?php

class PromptBuilder
{
    private array $labels = [

        // Délai
        'mois'        => 'ce mois-ci',
        '3mois'       => 'dans les 3 mois',
        '6mois'       => 'dans les 6 mois',
        'annee'       => "dans l'année",
        'renseigne'   => 'en phase de renseignement',

        // Type d'achat
        'neuf'        => 'neuf uniquement',
        'occasion'    => "occasion uniquement",
        'les2'        => 'neuf ou occasion',

        // Usage
        'travail'     => 'trajets domicile-travail quotidiens',
        'pro'         => 'usage professionnel',
        'famille'     => 'usage familial',
        'voyages'     => 'longs trajets',
        'ville'       => 'ville',
        'mixte'       => 'mixte ville et route',

        // Priorité
        'economie'    => 'économie',
        'fiabilite'   => 'fiabilité',
        'confort'     => 'confort',
        'design'      => 'design',
        'espace'      => 'espace',
        'perf'        => 'performances',

        // Kilométrage
        'moins10k'    => 'moins de 10 000 km/an',
        '10-20k'      => '10 000 à 20 000 km/an',
        '20-30k'      => '20 000 à 30 000 km/an',
        'plus30k'     => 'plus de 30 000 km/an',

        // Motorisation
        'essence'                 => 'essence',
        'diesel'                  => 'diesel',
        'hybride'                 => 'hybride',
        'hybride-rechargeable'    => 'hybride rechargeable',
        'electrique'              => 'électrique',
        'gpl'                     => 'GPL',
        'saitpas'                 => 'sans préférence',

        // Carrosserie
        'citadine'   => 'citadine',
        'berline'    => 'berline',
        'suv'         => 'SUV',
        'break'       => 'break',
        'monospace'   => 'monospace',
        'pickup'      => 'pickup',
        'utilitaire'  => 'utilitaire',
        'coupe'       => 'coupé',
        'cabriolet'   => 'cabriolet',
        'fourgon'     => 'fourgon',

        // Budget
        'moins-15k'   => 'moins de 15 000 €',
        '15k-25k'     => '15 000 € à 25 000 €',
        '25k-40k'     => '25 000 € à 40 000 €',
        'plus-40k'    => 'plus de 40 000 €',
    ];

    public function build(array $data): string
    {
        $delai       = $this->label($data['delai'] ?? '');
        $typeAchat   = $this->label($data['type_achat'] ?? '');
        $usage       = $this->label($data['usage'] ?? '');
        $priorite    = $this->label($data['priorite'] ?? '');
        $km          = $this->label($data['kilometrage'] ?? '');
        $moteur      = $this->label($data['motorisation'] ?? '');
        $carrosserie = $this->label($data['carrosserie'] ?? '');
        $budget      = $this->label($data['budget'] ?? '');

        $marque = trim($data['marque'] ?? '');
        $modele = trim($data['modele'] ?? '');

        $ligneMarque = '';

        if ($marque !== '') {
            $ligneMarque = "- Marque préférée : {$marque}";
            if ($modele !== '') {
                $ligneMarque .= " ({$modele})";
            }
            $ligneMarque .= "\n";
        }

        return <<<PROMPT
Tu es un expert automobile français.

Tu analyses le profil du client et proposes EXACTEMENT 3 véhicules.

=========================
PROFIL CLIENT
=========================

Budget :
{$budget}

Type d'achat :
{$typeAchat}

Usage :
{$usage}

Kilométrage :
{$km}

Motorisation :
{$moteur}

Carrosserie :
{$carrosserie}

Priorité :
{$priorite}

Délai :
{$delai}

{$ligneMarque}

=========================
RÈGLES
=========================

- Respecter strictement le budget.
- Ne jamais proposer un véhicule hors budget.
- Les véhicules doivent être vendus en France.
- Les recommandations doivent être réalistes.
- Le score doit être compris entre 80 et 100.
- Les prix doivent être cohérents avec le marché français.

Si achat = occasion :

prix_neuf_min = 0
prix_neuf_max = 0

Si achat = neuf :

prix_occasion_min = 0
prix_occasion_max = 0

Si achat = neuf ou occasion :

compléter les deux.

Chaque justification :

- entre 25 et 40 mots
- personnalisée
- sans répétition

points_forts :

exactement 3 éléments

point_vigilance :

une seule phrase

conseil_global :

2 phrases maximum

budget_analyse :

1 phrase maximum

IMPORTANT

Réponds UNIQUEMENT avec un objet JSON.

Aucun texte.

Aucun commentaire.

Aucun markdown.

Aucun ```json.

Toutes les chaînes doivent être entre guillemets.

Tous les nombres doivent être des nombres.

Si une valeur est inconnue :

texte = ""

nombre = 0

=========================
JSON
=========================

{
  "recommendations":[
    {
      "rank":1,
      "marque":"",
      "modele":"",
      "version":"",
      "carrosserie":"",
      "motorisation":"",
      "prix_neuf_min":0,
      "prix_neuf_max":0,
      "prix_occasion_min":0,
      "prix_occasion_max":0,
      "score":0,
      "justification":"",
      "points_forts":[
        "",
        "",
        ""
      ],
      "point_vigilance":""
    },
    {
      "rank":2,
      "marque":"",
      "modele":"",
      "version":"",
      "carrosserie":"",
      "motorisation":"",
      "prix_neuf_min":0,
      "prix_neuf_max":0,
      "prix_occasion_min":0,
      "prix_occasion_max":0,
      "score":0,
      "justification":"",
      "points_forts":[
        "",
        "",
        ""
      ],
      "point_vigilance":""
    },
    {
      "rank":3,
      "marque":"",
      "modele":"",
      "version":"",
      "carrosserie":"",
      "motorisation":"",
      "prix_neuf_min":0,
      "prix_neuf_max":0,
      "prix_occasion_min":0,
      "prix_occasion_max":0,
      "score":0,
      "justification":"",
      "points_forts":[
        "",
        "",
        ""
      ],
      "point_vigilance":""
    }
  ],
  "conseil_global":"",
  "budget_analyse":""
}

PROMPT;
    }

    private function label(string $key): string
    {
        return $this->labels[$key] ?? $key;
    }
}