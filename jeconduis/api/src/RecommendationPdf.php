<?php

declare(strict_types=1);

/**
 * Génère le PDF synthétique de recommandation.
 * Nécessite dompdf/dompdf via Composer.
 */
class RecommendationPdf
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?: dirname(__DIR__) . '/storage/pdf';

        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Impossible de créer le dossier PDF.');
        }
    }

    public function generate(array $result, array $profile = [], array $contact = []): string
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            throw new RuntimeException('Dompdf non installé. Lancez composer install.');
        }

        $formatter = new RecommendationFormatter();
        $data = $formatter->format($result, $profile);
        $html = $this->html($data, $contact, $formatter);

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'recommendation-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.pdf';
        $path = $this->directory . '/' . $filename;

        file_put_contents($path, $dompdf->output());

        return $path;
    }

    private function html(array $data, array $contact, RecommendationFormatter $formatter): string
    {
        $prenom = htmlspecialchars((string) ($contact['prenom'] ?? ''), ENT_QUOTES, 'UTF-8');
        $recommendations = '';

        foreach ($data['recommendations'] as $r) {
            $name = htmlspecialchars(trim($r['marque'] . ' ' . $r['modele']), ENT_QUOTES, 'UTF-8');
            $version = htmlspecialchars($r['version'], ENT_QUOTES, 'UTF-8');
            $motorisation = htmlspecialchars($r['motorisation'], ENT_QUOTES, 'UTF-8');
            $carrosserie = htmlspecialchars($r['carrosserie'], ENT_QUOTES, 'UTF-8');
            $justification = htmlspecialchars($r['justification'], ENT_QUOTES, 'UTF-8');
            $vigilance = htmlspecialchars($r['point_vigilance'], ENT_QUOTES, 'UTF-8');
            $score = $r['score'];

            $points = '';
            foreach ($r['points_forts'] as $point) {
                $points .= '<li>' . htmlspecialchars($point, ENT_QUOTES, 'UTF-8') . '</li>';
            }

            $recommendations .= <<<HTML
<section class="card">
    <div class="head">
        <div>
            <span class="rank">#{$r['rank']}</span>
            <h2>{$name}</h2>
            <div class="muted">{$version}</div>
        </div>
        <div class="score">{$score}<small>/100</small></div>
    </div>
    <table>
        <tr><td>Carrosserie</td><td>{$carrosserie}</td><td>Motorisation</td><td>{$motorisation}</td></tr>
        <tr><td>Neuf</td><td>{$formatter->priceRange($r, 'neuf')}</td><td>Occasion</td><td>{$formatter->priceRange($r, 'occasion')}</td></tr>
    </table>
    <p class="justification">{$justification}</p>
    <div class="columns">
        <div><strong>Points forts</strong><ul>{$points}</ul></div>
        <div><strong>Point de vigilance</strong><p>{$vigilance}</p></div>
    </div>
</section>
HTML;
        }

        $global = htmlspecialchars($data['conseil_global'], ENT_QUOTES, 'UTF-8');
        $budget = htmlspecialchars($data['budget_analyse'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 34px 36px; }
body { font-family: DejaVu Sans, sans-serif; color:#172033; font-size:10px; line-height:1.45; }
.header { border-bottom:3px solid #f2c230; padding-bottom:14px; margin-bottom:20px; }
.brand { font-size:22px; font-weight:700; color:#0f2747; }
.subtitle { color:#667085; margin-top:4px; }
h1 { font-size:19px; color:#0f2747; margin:0 0 5px; }
h2 { display:inline; font-size:16px; color:#0f2747; margin:0; }
.card { border:1px solid #dfe4ea; border-radius:8px; padding:13px; margin-bottom:14px; page-break-inside:avoid; }
.head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
.rank { display:inline-block; background:#0f2747; color:#fff; padding:3px 7px; border-radius:4px; margin-right:7px; font-weight:bold; }
.muted { color:#667085; margin-top:5px; }
.score { font-size:20px; font-weight:700; color:#0f2747; }
.score small { font-size:9px; color:#667085; }
table { width:100%; border-collapse:collapse; margin:8px 0; }
td { border:1px solid #e6e9ee; padding:6px; }
td:nth-child(odd) { font-weight:700; background:#f4f6f9; width:17%; }
.justification { margin:10px 0; }
.columns { display:table; width:100%; }
.columns > div { display:table-cell; width:50%; vertical-align:top; padding-right:12px; }
ul { margin:5px 0 0; padding-left:16px; }
li { margin-bottom:3px; }
.summary { background:#f4f6f9; border-left:4px solid #f2c230; padding:12px; margin-top:16px; page-break-inside:avoid; }
.footer { margin-top:20px; color:#667085; font-size:8px; text-align:center; }
</style>
</head>
<body>
<div class="header">
    <div class="brand">Je-Conduis.com</div>
    <div class="subtitle">Votre recommandation automobile personnalisée</div>
</div>
<h1>Bonjour {$prenom}, voici vos 3 recommandations</h1>
<p class="subtitle">Analyse basée sur les critères renseignés dans votre questionnaire.</p>
{$recommendations}
<div class="summary">
    <strong>Conseil global</strong><br>{$global}
    <br><br>
    <strong>Analyse du budget</strong><br>{$budget}
</div>
<div class="footer">Je-Conduis.com — Document de synthèse généré automatiquement.</div>
</body>
</html>
HTML;
    }
}
