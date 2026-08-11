<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP(): void
    {
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['SMTP_HOST'] ?? 'mail.vosaas.fr';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);
        $this->mail->SMTPSecure = $this->mail->Port === 465
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->SMTPKeepAlive = false;
        $this->mail->Timeout = 30;
        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'error_log';
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';
        $this->mail->isHTML(true);

        $this->mail->setFrom(
            $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USERNAME'],
            $_ENV['SMTP_FROM_NAME'] ?? 'Je-Conduis.com'
        );
    }

    /**
     * Envoie l'email de synthèse et, si fourni, le PDF complet en pièce jointe.
     * Le paramètre $pdfPath est optionnel pour rester compatible avec l'appel V1.
     */
    public function sendRecommendation(
        array $contact,
        array $profile,
        array $recommendations,
        string $conseil_global,
        string $budget_analyse,
        ?string $pdfPath = null
    ): bool {
        try {
            $email = trim((string)($contact['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Adresse email invalide.');
            }

            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->addAddress(
                $email,
                trim((string)($contact['prenom'] ?? '') . ' ' . (string)($contact['nom'] ?? ''))
            );

            if ($pdfPath !== null && is_file($pdfPath)) {
                $this->mail->addAttachment(
                    $pdfPath,
                    'Votre-recommandation-Je-Conduis.pdf',
                    PHPMailer::ENCODING_BASE64,
                    'application/pdf'
                );
            }

            $this->mail->Subject = 'Votre recommandation automobile – Je-Conduis.com';

            // Variables utilisées par recommendation-email.php.
            ob_start();
            require __DIR__ . '/../../templates/recommendation-email.php';
            $html = ob_get_clean();

            $this->mail->Body = $html;
            $this->mail->AltBody = $this->buildTextVersion(
                (string)($contact['prenom'] ?? ''),
                $recommendations,
                $conseil_global,
                $budget_analyse
            );

            return $this->mail->send();
        } catch (\Throwable $e) {
            error_log('[MAIL] ' . $e->getMessage());
            return false;
        }
    }

    private function buildTextVersion(
        string $prenom,
        array $recommendations,
        string $conseil,
        string $budget
    ): string {
        $txt = "Bonjour {$prenom}\n\n";
        $txt .= "Voici vos recommandations automobiles personnalisées.\n\n";

        foreach ($recommendations as $car) {
            $txt .= "====================================\n";
            $txt .= ($car['rank'] ?? '') . '. ';
            $txt .= ($car['marque'] ?? '') . ' ' . ($car['modele'] ?? '') . "\n";

            if (!empty($car['version'])) {
                $txt .= 'Version : ' . $car['version'] . "\n";
            }
            if (!empty($car['motorisation'])) {
                $txt .= 'Motorisation : ' . $car['motorisation'] . "\n";
            }
            if (isset($car['score'])) {
                $txt .= 'Score : ' . $car['score'] . "/100\n";
            }

            if (!empty($car['prix_neuf_min']) || !empty($car['prix_neuf_max'])) {
                $txt .= 'Prix neuf : ' . ($car['prix_neuf_min'] ?? 0) . ' - ' . ($car['prix_neuf_max'] ?? 0) . " €\n";
            }
            if (!empty($car['prix_occasion_min']) || !empty($car['prix_occasion_max'])) {
                $txt .= 'Prix occasion : ' . ($car['prix_occasion_min'] ?? 0) . ' - ' . ($car['prix_occasion_max'] ?? 0) . " €\n";
            }

            $txt .= "\n" . ($car['justification'] ?? '') . "\n\n";

            if (!empty($car['points_forts'])) {
                $txt .= "Points forts :\n";
                foreach ($car['points_forts'] as $point) {
                    $txt .= "- {$point}\n";
                }
                $txt .= "\n";
            }

            if (!empty($car['point_vigilance'])) {
                $txt .= "Point de vigilance :\n" . $car['point_vigilance'] . "\n\n";
            }
        }

        $txt .= "====================================\n\n";

        if ($conseil !== '') {
            $txt .= "Conseil personnalisé :\n{$conseil}\n\n";
        }

        if ($budget !== '') {
            $txt .= "Analyse du budget :\n{$budget}\n";
        }

        if ($txt !== '') {
            $txt .= "\nVotre analyse complète est jointe au format PDF.\n";
        }

        return $txt;
    }
}
