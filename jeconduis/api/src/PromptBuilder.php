<?php
declare(strict_types=1);

class PromptBuilder
{
    public function build(array $profile): string
    {
        $lines = [];
        foreach ($profile as $key => $value) {
            if ($key === 'marques_modeles') {
                $lines[] = 'Marques / modèles préférés : ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                continue;
            }
            if (is_scalar($value) && (string)$value !== '') {
                $label = Labels::get((string)$value);
                $lines[] = ucfirst(str_replace('_', ' ', $key)) . ' : ' . $label;
            }
        }

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

        return "Tu es un expert automobile français.\n\n" .
            "PROFIL CLIENT :\n" . implode("\n", $lines) . "\n\n" .
            "RÈGLES :\n" . (new PromptRules())->text() . "\n\n" .
            (new FranceRules())->text() . "\n\n" .
            "RÉPONDS UNIQUEMENT AVEC UN OBJET JSON VALIDE. Aucun markdown, commentaire ou texte avant/après.\n" .
            "Si une donnée est inconnue, utiliser une chaîne vide ou 0.\n\nSCHEMA JSON :\n" . $schema;
    }
}
