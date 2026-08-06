<?php
/**
 * api/src/LeadRepository.php
 * Responsabilité unique : lire et écrire les leads en base MySQL.
 */

class LeadRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insère un nouveau lead avec les données du formulaire.
     * Retourne l'ID inséré pour la mise à jour IA ultérieure.
     */
    public function insert(array $contact, array $profile): int
    {
        $sql = "
            INSERT INTO leads (
                prenom, nom, email, telephone,
                delai, type_achat, `usage`, priorite,
                kilometrage, motorisation, carrosserie,
                marque, modele, budget,
                ip_hash, user_agent
            ) VALUES (
                :prenom, :nom, :email, :telephone,
                :delai, :type_achat, :usage, :priorite,
                :kilometrage, :motorisation, :carrosserie,
                :marque, :modele, :budget,
                :ip_hash, :user_agent
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            // Coordonnées — stockées telles quelles (à chiffrer si besoin RGPD avancé)
            ':prenom'       => $contact['prenom'],
            ':nom'          => $contact['nom'],
            ':email'        => $contact['email'],
            ':telephone'    => $contact['telephone'],

            // Profil formulaire
            ':delai'        => $profile['delai']        ?? null,
            ':type_achat'   => $profile['type_achat']   ?? null,
            ':usage'        => $profile['usage']        ?? null,
            ':priorite'     => $profile['priorite']     ?? null,
            ':kilometrage'  => $profile['kilometrage']  ?? null,
            ':motorisation' => $profile['motorisation'] ?? null,
            ':carrosserie'  => $profile['carrosserie']  ?? null,
            ':marque'       => $profile['marque']       ?? null,
            ':modele'       => $profile['modele']       ?? null,
            ':budget'       => $profile['budget'],

            // Tracking RGPD (IP hashée, jamais en clair)
            ':ip_hash'      => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
            ':user_agent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour le lead avec les recommandations retournées par l'IA.
     * Appelé après l'appel API Claude réussi.
     */
    public function updateWithAI(int $leadId, array $recommendations, string $conseil): void
    {
        $sql = "
            UPDATE leads
            SET ai_recommandations = :reco,
                ai_conseil         = :conseil,
                ai_succes          = 1
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reco'    => json_encode($recommendations, JSON_UNESCAPED_UNICODE),
            ':conseil' => $conseil,
            ':id'      => $leadId,
        ]);
    }
}