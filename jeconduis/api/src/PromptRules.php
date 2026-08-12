<?php
declare(strict_types=1);

class PromptRules
{
    public function text(): string
    {
        return <<<'RULES'
- Proposer exactement 3 véhicules réellement commercialisés en France.
- Les rangs doivent être exactement 1, 2 et 3, sans doublon.
- Respecter strictement le budget fourni ; ne jamais proposer volontairement un véhicule hors budget.
- Ne jamais inventer un prix précis si seule une fourchette fiable est disponible.
- Les marques et modèles sélectionnés par le client sont des préférences explicites : les prioriser lorsqu'ils restent compatibles avec le profil.
- Ne pas présenter comme disponible en neuf un modèle/version qui n'est plus commercialisé en France.
- Le score doit être un nombre entier compris entre 80 et 100 et cohérent avec l'adéquation au profil.
- justification : entre 25 et 40 mots, personnalisée et sans répétition entre les 3 véhicules.
- points_forts : exactement 3 chaînes non vides et courtes.
- point_vigilance : une seule phrase courte.
- conseil_global : maximum 2 phrases.
- budget_analyse : exactement 1 phrase maximum.
- Les champs prix_neuf_min, prix_neuf_max, prix_occasion_min et prix_occasion_max doivent toujours être présents et contenir des nombres entiers >= 0.
- Pour chaque fourchette de prix renseignée, min doit être inférieur ou égal à max.
- marque, modele, version, carrosserie et motorisation doivent toujours être présents dans chaque recommandation, même si une valeur inconnue doit être "".
- Ne pas inventer de données techniques, de prix ou de disponibilité.
RULES;
    }
}
