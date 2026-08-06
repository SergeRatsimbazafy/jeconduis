<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configuration SMTP
     */
    private function configureSMTP(): void
    {
        $this->mail->isSMTP();

        $this->mail->Host = $_ENV['SMTP_HOST'] ?? 'mail.vosaas.fr';
        $this->mail->SMTPAuth = true;

        $this->mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';

        $this->mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);

        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        $this->mail->SMTPKeepAlive = false;
        $this->mail->Timeout = 30;

        // Mettre 2 pour déboguer puis remettre à 0
        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'error_log';

        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';

        $this->mail->isHTML(true);

        $this->mail->setFrom(
            $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USERNAME'],
            $_ENV['SMTP_FROM_NAME'] ?? 'Je-Conduis'
        );
    }

    /**
     * Envoi de l'e-mail de recommandations
     */
    public function sendRecommendation(
        array $contact,
        array $profile,
        array $recommendations,
        string $conseil_global,
        string $budget_analyse
    ): bool {

        try {

            $this->mail->clearAddresses();

            $this->mail->addAddress(
                $contact['email'],
                trim($contact['prenom'] . ' ' . $contact['nom'])
            );

            // Exemple :
            // $this->mail->addBCC('serge@vosaas.fr');

            $this->mail->Subject = 'Vos recommandations automobiles personnalisées';

            /*
             * Variables utilisées par recommendation-email.php
             */

            ob_start();

            require __DIR__ . '/../../templates/recommendation-email.php';

            $html = ob_get_clean();

            $this->mail->Body = $html;

            $this->mail->AltBody = $this->buildTextVersion(
                $contact['prenom'],
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

    /**
     * Version texte
     */
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

            $txt .= $car['rank'] . ". ";

            $txt .= $car['marque'] . " ";

            $txt .= $car['modele'] . "\n";

            if (!empty($car['version'])) {
                $txt .= "Version : " . $car['version'] . "\n";
            }

            if (!empty($car['motorisation'])) {
                $txt .= "Motorisation : " . $car['motorisation'] . "\n";
            }

            if (!empty($car['score'])) {
                $txt .= "Score : " . $car['score'] . "/100\n";
            }

            $txt .= "\n";

            $txt .= $car['justification'] . "\n\n";

            if (!empty($car['points_forts'])) {

                $txt .= "Points forts :\n";

                foreach ($car['points_forts'] as $point) {
                    $txt .= "• {$point}\n";
                }

                $txt .= "\n";
            }

            if (!empty($car['point_vigilance'])) {
                $txt .= "Point de vigilance :\n";
                $txt .= $car['point_vigilance'] . "\n\n";
            }
        }

        $txt .= "====================================\n\n";

        if (!empty($conseil)) {

            $txt .= "Conseil personnalisé :\n";

            $txt .= $conseil . "\n\n";
        }

        if (!empty($budget)) {

            $txt .= "Analyse du budget :\n";

            $txt .= $budget . "\n";
        }

        return $txt;
    }
}