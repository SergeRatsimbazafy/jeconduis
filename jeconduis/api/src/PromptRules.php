<?php
declare(strict_types=1);

class PromptRules
{
    public function text(): string
    {
        return <<<'RULES'
- Proposer exactement 3 véhicules réellement commercialisés en France.
- Respecter strictement le budget fourni; ne jamais inventer un prix précis si seule une fourchette fiable est disponible.
- Les marques et modèles sélectionnés par le client sont des préférences, pas une obligation: les respecter lorsqu'ils sont compatibles avec le profil.
- Ne pas présenter comme disponible en neuf un modèle/version qui n'est plus commercialisé en France.
- Score compris entre 80 et 100 et cohérent avec l'adéquation au profil.
- Justification de 25 à 40 mots, personnalisée.
- points_forts: exactement 3 éléments courts.
- point_vigilance: une phrase courte.
- conseil_global: maximum 2 phrases.
- budget_analyse: une phrase.
- Ne pas inventer de données techniques, de prix ou de disponibilité.
RULES;
    }
}
