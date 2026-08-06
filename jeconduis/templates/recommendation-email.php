<?php
/**
 * templates/recommendation-email.php
 *
 * Variables attendues :
 * $contact
 * $profile
 * $recommendations
 * $conseil_global
 * $budget_analyse
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Je-Conduis');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatPrice($price): string
{
    $price = (int)$price;

    if ($price <= 0) {
        return '-';
    }

    return number_format($price, 0, ',', ' ') . ' €';
}

function scoreColor(int $score): string
{
    if ($score >= 95) return '#00A651';
    if ($score >= 90) return '#6CBF00';
    if ($score >= 85) return '#F2C230';

    return '#F47920';
}

function scoreBar(int $score): string
{
    $width = max(0, min(100, $score));

    return '
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
        <tr>
            <td style="background:#E8EDF5;border-radius:20px;height:10px;">
                <div style="
                    width:' . $width . '%;
                    height:10px;
                    background:#0F2747;
                    border-radius:20px;
                "></div>
            </td>
        </tr>
    </table>';
}

$prenom = e($contact['prenom'] ?? 'Client');

?>
<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width,initial-scale=1.0">

<title>Votre recommandation automobile</title>

</head>

<body style="
margin:0;
padding:0;
background:#F4F6F9;
font-family:Arial,Helvetica,sans-serif;
">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="background:#F4F6F9;">

<tr>

<td align="center" style="padding:30px 15px;">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="
       max-width:700px;
       background:#FFFFFF;
       border-radius:14px;
       overflow:hidden;
       ">

<!-- HEADER -->

<tr>

<td
style="
background:#0F2747;
padding:40px;
text-align:center;
">

<div
style="
font-size:34px;
font-weight:bold;
color:#FFFFFF;
letter-spacing:1px;
">

JE-CONDUIS

</div>

<div
style="
margin-top:10px;
font-size:16px;
color:#D8E4F2;
">

Votre rapport automobile personnalisé

</div>

</td>

</tr>

<!-- INTRO -->

<tr>

<td style="padding:40px;">

<h2
style="
margin:0 0 15px;
color:#0F2747;
font-size:28px;
">

Bonjour <?= $prenom ?>,

</h2>

<p
style="
margin:0;
font-size:16px;
line-height:1.8;
color:#555;
">

Merci d'avoir utilisé notre conseiller automobile intelligent.

Notre IA a analysé votre profil afin d'identifier les véhicules les plus adaptés à vos besoins.

Vous trouverez ci-dessous votre rapport personnalisé.

</p>

</td>

</tr>

<!-- PROFIL -->

<tr>

<td style="padding:0 40px 40px;">

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
border:1px solid #E5E5E5;
border-radius:10px;
">

<tr>

<td
colspan="2"
style="
padding:18px;
background:#F2C230;
color:#0F2747;
font-size:20px;
font-weight:bold;
">

Votre profil

</td>

</tr>
<tr>

<td
colspan="2"
style="
padding:18px;
background:#F2C230;
color:#0F2747;
font-size:20px;
font-weight:bold;
">

Votre profil

</td>

</tr>
<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;width:35%;">
Budget
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['budget'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Type d'achat
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['type_achat'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Usage
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['usage'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Kilométrage
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['kilometrage'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Motorisation
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['motorisation'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Carrosserie
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['carrosserie'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;border-bottom:1px solid #EEE;font-weight:bold;">
Priorité
</td>

<td style="padding:18px;border-bottom:1px solid #EEE;">
<?= e($profile['priorite'] ?? '-') ?>
</td>

</tr>

<tr>

<td style="padding:18px;font-weight:bold;">
Délai
</td>

<td style="padding:18px;">
<?= e($profile['delai'] ?? '-') ?>
</td>

</tr>

</table>

</td>

</tr>

<!-- RECOMMANDATIONS -->

<?php foreach ($recommendations as $car): ?>

<tr>

<td style="padding:0 40px 35px;">

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
border:1px solid #E5E5E5;
border-radius:12px;
overflow:hidden;
">

<tr>

<td style="background:#0F2747;padding:22px;">

<h2 style="margin:0;color:#FFF;font-size:26px;">

<?= e($car['marque']) ?>

<?= e($car['modele']) ?>

</h2>

<div style="margin-top:8px;color:#D6E3F0;font-size:15px;">

<?= e($car['version']) ?>

</div>

</td>

</tr>

<tr>

<td style="padding:25px;">

<table width="100%">

<tr>

<td width="70%">

<div
style="
display:inline-block;
background:#F2C230;
padding:8px 14px;
border-radius:30px;
font-weight:bold;
color:#0F2747;
margin-right:8px;
">

<?= e($car['motorisation']) ?>

</div>

<div
style="
display:inline-block;
background:#E9EEF6;
padding:8px 14px;
border-radius:30px;
font-weight:bold;
color:#0F2747;
">

<?= e($car['carrosserie']) ?>

</div>

</td>

<td align="right">

<div
style="
font-size:34px;
font-weight:bold;
color:<?= scoreColor((int)$car['score']) ?>;
">

<?= (int)$car['score'] ?>/100

</div>

</td>

</tr>

</table>

<?= scoreBar((int)$car['score']) ?>

<p
style="
margin-top:25px;
line-height:1.8;
color:#555;
font-size:15px;
">

<?= nl2br(e($car['justification'])) ?>

</p>
<p
style="
margin-top:25px;
line-height:1.8;
color:#555;
font-size:15px;
">

<?= nl2br(e($car['justification'])) ?>

</p>
<!-- POINTS FORTS -->

<?php if (!empty($car['points_forts'])): ?>

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
margin-top:20px;
background:#F8FAFC;
border-radius:10px;
">

<tr>

<td style="padding:18px;">

<div
style="
font-weight:bold;
color:#0F2747;
margin-bottom:12px;
font-size:16px;
">

Points forts

</div>

<?php foreach ($car['points_forts'] as $point): ?>

<div style="
padding:8px 0;
font-size:14px;
color:#444;
">

✓ <?= e($point) ?>

</div>

<?php endforeach; ?>

</td>

</tr>

</table>

<?php endif; ?>

<!-- POINT DE VIGILANCE -->

<?php if (!empty($car['point_vigilance'])): ?>

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
margin-top:18px;
border-left:4px solid #F2C230;
background:#FFFDF5;
">

<tr>

<td style="padding:18px;">

<div
style="
font-weight:bold;
color:#0F2747;
margin-bottom:8px;
">

Point de vigilance

</div>

<div
style="
font-size:14px;
line-height:1.7;
color:#555;
">

<?= e($car['point_vigilance']) ?>

</div>

</td>

</tr>

</table>

<?php endif; ?>

<!-- PRIX -->

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="margin-top:25px;">

<tr>

<?php if (($car['prix_neuf_min'] ?? 0) > 0): ?>

<td width="48%"
style="
border:1px solid #E5E5E5;
border-radius:10px;
padding:20px;
text-align:center;
">

<div
style="
font-size:13px;
color:#888;
text-transform:uppercase;
">

Prix neuf

</div>

<div
style="
margin-top:10px;
font-size:24px;
font-weight:bold;
color:#0F2747;
">

<?= formatPrice($car['prix_neuf_min']) ?>

</div>

<div style="color:#777;margin-top:5px;">

à

</div>

<div
style="
font-size:24px;
font-weight:bold;
color:#0F2747;
">

<?= formatPrice($car['prix_neuf_max']) ?>

</div>

</td>

<?php endif; ?>

<?php if (
    ($car['prix_neuf_min'] ?? 0) > 0
    &&
    ($car['prix_occasion_min'] ?? 0) > 0
): ?>

<td width="4%"></td>

<?php endif; ?>

<?php if (($car['prix_occasion_min'] ?? 0) > 0): ?>

<td width="48%"
style="
border:1px solid #E5E5E5;
border-radius:10px;
padding:20px;
text-align:center;
">

<div
style="
font-size:13px;
color:#888;
text-transform:uppercase;
">

Prix occasion

</div>

<div
style="
margin-top:10px;
font-size:24px;
font-weight:bold;
color:#0F2747;
">

<?= formatPrice($car['prix_occasion_min']) ?>

</div>

<div style="color:#777;margin-top:5px;">

à

</div>

<div
style="
font-size:24px;
font-weight:bold;
color:#0F2747;
">

<?= formatPrice($car['prix_occasion_max']) ?>

</div>

</td>

<?php endif; ?>

</tr>

</table>

</td>

</tr>

</table>

</td>

</tr>

<?php endforeach; ?>

<!-- CONSEIL IA -->

<tr>

<td style="padding:10px 40px 0;">

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
background:#F8FAFC;
border-radius:12px;
border-left:5px solid #0F2747;
">

<tr>

<td style="padding:25px;">

<div
style="
font-size:22px;
font-weight:bold;
color:#0F2747;
margin-bottom:15px;
">

Conseil de notre IA

</div>

<div
style="
font-size:15px;
line-height:1.8;
color:#555;
">

<?= nl2br(e($conseil_global ?? '')) ?>

</div>

</td>

</tr>

</table>

</td>

</tr>

<!-- BUDGET -->

<tr>

<td style="padding:25px 40px 40px;">

<table
width="100%"
cellpadding="0"
cellspacing="0"
style="
background:#FFFDF5;
border-radius:12px;
border-left:5px solid #F2C230;
">

<tr>

<td style="padding:25px;">

<div
style="
font-size:22px;
font-weight:bold;
color:#0F2747;
margin-bottom:15px;
">

Analyse du budget

</div>

<div
style="
font-size:15px;
line-height:1.8;
color:#555;
">

<?= nl2br(e($budget_analyse ?? '')) ?>

</div>

</td>

</tr>

</table>

</td>

</tr>
<!-- BOUTON -->

<tr>

<td align="center" style="padding:0 40px 45px;">

<a
href="https://www.je-conduis.com"
style="
display:inline-block;
background:#0F2747;
color:#FFFFFF;
text-decoration:none;
padding:18px 34px;
border-radius:8px;
font-size:16px;
font-weight:bold;
">

Découvrir plus de conseils automobiles

</a>

</td>

</tr>

<!-- SÉPARATEUR -->

<tr>

<td style="padding:0 40px;">

<hr style="
border:none;
border-top:1px solid #E5E5E5;
margin:0;
">

</td>

</tr>

<!-- FOOTER -->

<tr>

<td
style="
padding:35px 40px;
background:#FAFBFC;
text-align:center;
">

<div
style="
font-size:22px;
font-weight:bold;
color:#0F2747;
">

JE-CONDUIS

</div>

<div
style="
margin-top:12px;
font-size:15px;
line-height:1.8;
color:#666;
">

Votre conseiller automobile intelligent.

</div>

<div
style="
margin-top:20px;
font-size:14px;
color:#777;
">

Ce rapport a été généré automatiquement à partir des informations
que vous nous avez communiquées.

</div>

<div
style="
margin-top:12px;
font-size:14px;
color:#777;
">

Les prix indiqués sont donnés à titre indicatif et peuvent évoluer
selon la finition, l'année, le kilométrage ou les offres du marché.

</div>

<div
style="
margin-top:30px;
font-size:13px;
color:#999;
line-height:1.8;
">

© <?= date('Y') ?> Je-Conduis.com<br>

Tous droits réservés.

</div>

</td>

</tr>

</table>

</td>

</tr>

</table>

</body>

</html>