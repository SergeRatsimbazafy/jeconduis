<?php
declare(strict_types=1);

class Labels
{
    public static function all(): array
    {
        return [
            'mois'=>'ce mois-ci','3mois'=>'dans les 3 mois','6mois'=>'dans les 6 mois','annee'=>"dans l'année",'renseigne'=>'en phase de renseignement',
            'neuf'=>'neuf uniquement','occasion'=>'occasion uniquement','les2'=>'neuf ou occasion',
            'travail'=>'trajets domicile-travail quotidiens','pro'=>'usage professionnel','famille'=>'usage familial','voyages'=>'longs trajets','ville'=>'ville','mixte'=>'mixte ville et route',
            'economie'=>'économie','fiabilite'=>'fiabilité','confort'=>'confort','design'=>'design','espace'=>'espace','perf'=>'performances',
            'moins10k'=>'moins de 10 000 km/an','10-20k'=>'10 000 à 20 000 km/an','20-30k'=>'20 000 à 30 000 km/an','plus30k'=>'plus de 30 000 km/an',
            'essence'=>'essence','diesel'=>'diesel','hybride'=>'hybride','hybride-rechargeable'=>'hybride rechargeable','electrique'=>'électrique','gpl'=>'GPL','saitpas'=>'sans préférence',
            'citadine'=>'citadine','berline'=>'berline','suv'=>'SUV','break'=>'break','monospace'=>'monospace','pickup'=>'pickup','utilitaire'=>'utilitaire','coupe'=>'coupé','cabriolet'=>'cabriolet','fourgon'=>'fourgon',
            'moins-15k'=>'moins de 15 000 €','15k-25k'=>'15 000 € à 25 000 €','25k-40k'=>'25 000 € à 40 000 €','plus-40k'=>'plus de 40 000 €'
        ];
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? $key;
    }
}
