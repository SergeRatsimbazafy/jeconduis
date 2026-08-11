<?php

declare(strict_types=1);

/**
 * V2 - Persistance minimale des leads.
 *
 * Le profil complet sert à l'IA, au PDF et à l'email, mais la majorité des
 * réponses du questionnaire n'est pas conservée en SQL.
 */
class LeadRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre uniquement les informations nécessaires au suivi du lead.
     */
    public function create(array $contact, array $profile, ?string $pdfPath = null): int
    {
        $sql = <<<'SQL'
INSERT INTO leads_v2 (
    prenom,
    nom,
    email,
    telephone,
    ville,
    codepostal,
    budget,
    type_achat,
    marques_modeles,
    recommendation_pdf,
    created_at
) VALUES (
    :prenom,
    :nom,
    :email,
    :telephone,
    :ville,
    :codepostal,
    :budget,
    :type_achat,
    :marques_modeles,
    :recommendation_pdf,
    NOW()
)
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':prenom' => $this->string($contact['prenom'] ?? ''),
            ':nom' => $this->string($contact['nom'] ?? ''),
            ':email' => $this->string($contact['email'] ?? ''),
            ':telephone' => $this->string($contact['telephone'] ?? ''),
            ':ville' => $this->string($contact['ville'] ?? ''),
            ':codepostal' => $this->string($contact['codepostal'] ?? ''),
            ':budget' => $this->string($profile['budget'] ?? ''),
            ':type_achat' => $this->string($profile['type_achat'] ?? ''),
            ':marques_modeles' => $this->json($profile['marques_modeles'] ?? []),
            ':recommendation_pdf' => $pdfPath,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function string(mixed $value): string
    {
        return trim((string) $value);
    }

    private function json(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}
