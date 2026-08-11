<?php
declare(strict_types=1);

class FranceRules
{
    public function text(): string
    {
        return <<<'RULES'
CONTEXTE FRANCE:
- Utiliser les prix en euros et le marché automobile français.
- Tenir compte de la disponibilité en France et des usages français.
- Pour l'occasion, raisonner en fourchette de prix plutôt qu'en prix catalogue neuf.
- Ne pas confondre prix catalogue, prix promotionnel et prix d'occasion.
- Si une information de marché n'est pas suffisamment fiable, utiliser 0 dans le JSON plutôt qu'une valeur inventée.
RULES;
    }
}
