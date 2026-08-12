<?php
declare(strict_types=1);

class FranceRules
{
    public function text(): string
    {
        return <<<'RULES'
CONTEXTE FRANCE :
- Utiliser uniquement le marché automobile français et les prix en euros.
- Tenir compte de la disponibilité réelle en France et des usages français.
- Pour l'occasion, raisonner en fourchette de prix cohérente avec le marché français plutôt qu'en prix catalogue neuf.
- Ne pas confondre prix catalogue, prix promotionnel, remise constructeur et prix d'occasion.
- Ne pas considérer comme disponible en neuf un modèle ou une version qui n'est plus commercialisé en France.
- Pour un véhicule électrique ou hybride rechargeable, tenir compte de l'accès à la recharge renseigné par le client.
- Le choix de la motorisation doit rester cohérent avec le kilométrage annuel, l'usage principal et la possibilité de recharge.
- Si une information de marché n'est pas suffisamment fiable, utiliser 0 dans les champs numériques plutôt qu'une valeur inventée.
RULES;
    }
}
