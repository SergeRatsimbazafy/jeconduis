<?php
declare(strict_types=1);

/**
 * Je-Conduis V2 - email de synthèse.
 *
 * Variables attendues :
 * $contact
 * $recommendations
 * $conseil_global
 * $budget_analyse
 */

if (!function_exists('jc_email_escape')) {
    function jc_email_escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('jc_email_price')) {
    function jc_email_price(mixed $value): string
    {
        $value = (int) $value;
        return $value > 0 ? number_format($value, 0, ',', ' ') . ' €' : '—';
    }
}

if (!function_exists('jc_email_range')) {
    function jc_email_range(array $car, string $type): string
    {
        $min = (int) ($car["prix_{$type}_min"] ?? 0);
        $max = (int) ($car["prix_{$type}_max"] ?? 0);

        if ($min <= 0 && $max <= 0) return '—';
        if ($min > 0 && $max > 0 && $min !== $max) {
            return jc_email_price($min) . ' – ' . jc_email_price($max);
        }
        return jc_email_price($max > 0 ? $max : $min);
    }
}

if (!function_exists('jc_email_score_color')) {
    function jc_email_score_color(int $score): string
    {
        if ($score >= 90) return '#0F2747';
        if ($score >= 80) return '#6B7280';
        return '#9CA3AF';
    }
}

$prenom = jc_email_escape($contact['prenom'] ?? '');
$recommendations = is_array($recommendations ?? null) ? $recommendations : [];
$conseil_global = (string) ($conseil_global ?? '');
$budget_analyse = (string) ($budget_analyse ?? '');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Votre recommandation automobile — Je-Conduis</title>
</head>
<body style="margin:0;padding:0;background:#F4F6F9;font-family:Arial,Helvetica,sans-serif;color:#172033;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F4F6F9;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;background:#FFFFFF;">

<tr><td style="background:#0F2747;padding:28px 30px;">
    <div style="font-size:26px;line-height:1;font-weight:700;letter-spacing:.5px;color:#FFFFFF;">JE-CONDUIS</div>
    <div style="margin-top:8px;font-size:14px;color:#D8E4F2;">Votre recommandation automobile personnalisée</div>
</td></tr>

<tr><td style="padding:30px 30px 20px;">
    <div style="font-size:24px;font-weight:700;color:#0F2747;">Bonjour <?= $prenom ?>,</div>
    <p style="margin:10px 0 0;font-size:15px;line-height:1.6;color:#667085;">
        Voici les 3 véhicules qui correspondent le mieux aux critères renseignés.
    </p>
</td></tr>

<?php foreach (array_slice($recommendations, 0, 3) as $index => $car): ?>
<?php
    $rank = (int) ($car['rank'] ?? ($index + 1));
    $score = max(0, min(100, (int) ($car['score'] ?? 0)));
    $name = trim(($car['marque'] ?? '') . ' ' . ($car['modele'] ?? ''));
?>
<tr><td style="padding:0 30px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E2E6EC;">
<tr><td style="padding:20px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td valign="top">
            <span style="display:inline-block;background:#F2C230;color:#0F2747;font-size:12px;font-weight:700;padding:4px 8px;">#<?= $rank ?></span>
            <div style="margin-top:9px;font-size:21px;font-weight:700;color:#0F2747;"><?= jc_email_escape($name) ?></div>
            <?php if (!empty($car['version'])): ?><div style="margin-top:4px;font-size:13px;color:#667085;"><?= jc_email_escape($car['version']) ?></div><?php endif; ?>
        </td>
        <td align="right" valign="top" width="85">
            <div style="font-size:25px;font-weight:700;color:<?= jc_email_score_color($score) ?>;"><?= $score ?><span style="font-size:11px;color:#98A2B3;">/100</span></div>
        </td>
    </tr>
    </table>

    <div style="margin-top:15px;border-top:1px solid #EEF1F4;padding-top:13px;font-size:13px;color:#475467;">
        <?= jc_email_escape($car['carrosserie'] ?? '') ?>
        <?php if (!empty($car['carrosserie']) && !empty($car['motorisation'])): ?> · <?php endif; ?>
        <?= jc_email_escape($car['motorisation'] ?? '') ?>
    </div>

    <?php if (!empty($car['justification'])): ?>
    <p style="margin:14px 0 0;font-size:14px;line-height:1.55;color:#475467;"><?= nl2br(jc_email_escape($car['justification'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($car['points_forts']) && is_array($car['points_forts'])): ?>
    <div style="margin-top:14px;font-size:13px;color:#344054;">
        <strong style="color:#0F2747;">Points forts</strong>
        <?php foreach (array_slice($car['points_forts'], 0, 3) as $point): ?>
            <div style="margin-top:5px;">• <?= jc_email_escape($point) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($car['point_vigilance'])): ?>
    <div style="margin-top:13px;padding:10px 12px;background:#FFFDF5;border-left:3px solid #F2C230;font-size:12px;line-height:1.5;color:#475467;">
        <strong style="color:#0F2747;">Vigilance :</strong> <?= jc_email_escape($car['point_vigilance']) ?>
    </div>
    <?php endif; ?>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:15px;">
    <tr>
        <td width="50%" style="padding:10px;background:#F8FAFC;font-size:11px;color:#667085;">NEUF<br><strong style="font-size:13px;color:#0F2747;"><?= jc_email_escape(jc_email_range($car, 'neuf')) ?></strong></td>
        <td width="8"></td>
        <td width="50%" style="padding:10px;background:#F8FAFC;font-size:11px;color:#667085;">OCCASION<br><strong style="font-size:13px;color:#0F2747;"><?= jc_email_escape(jc_email_range($car, 'occasion')) ?></strong></td>
    </tr>
    </table>
</td></tr>
</table>
</td></tr>
<?php endforeach; ?>

<tr><td style="padding:8px 30px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8FAFC;border-left:4px solid #0F2747;">
<tr><td style="padding:18px 20px;">
    <div style="font-size:16px;font-weight:700;color:#0F2747;">Notre conseil</div>
    <div style="margin-top:7px;font-size:14px;line-height:1.6;color:#475467;"><?= nl2br(jc_email_escape($conseil_global)) ?></div>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:0 30px 28px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFFDF5;border-left:4px solid #F2C230;">
<tr><td style="padding:18px 20px;">
    <div style="font-size:16px;font-weight:700;color:#0F2747;">Budget</div>
    <div style="margin-top:7px;font-size:14px;line-height:1.6;color:#475467;"><?= nl2br(jc_email_escape($budget_analyse)) ?></div>
</td></tr>
</table>
</td></tr>

<tr><td style="border-top:1px solid #E5E7EB;padding:20px 30px;text-align:center;">
    <div style="font-size:12px;color:#667085;">Votre rapport complet est joint à cet email au format PDF.</div>
    <div style="margin-top:7px;font-size:11px;color:#98A2B3;">Je-Conduis.com · Recommandation automobile personnalisée</div>
</td></tr>

</table>
</td></tr></table>
</body>
</html>
