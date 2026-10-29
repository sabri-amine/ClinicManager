<?php
// includes/auth.php - Fonctions centrales d'authentification et de sécurité

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Générer un jeton CSRF s'il n'existe pas
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier le jeton CSRF
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Échappement HTML sécurisé
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Vérifie si l'utilisateur est authentifié
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Exige d'être connecté, sinon redirige vers la page de login
function requireLogin(): void {
    if (!isAuthenticated()) {
        header('Location: ../login/login.php');
        exit;
    }
}

// Exige un ou plusieurs rôles spécifiques
function requireRole($allowedRoles): void {
    requireLogin();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = $_SESSION['role'] ?? '';
    if (!in_array($userRole, $roles, true)) {
        header('Location: ../login/login.php');
        exit;
    }
}

// Création d'une notification utilisateur avec protection anti-doublon
function addNotification(PDO $pdo, int $userId, string $titre, string $message, string $type = 'info', ?int $rdvId = null): void {
    try {
        // Empêcher la création de notifications en double récentes (dans les dernières 24h pour le même rendez-vous et même type)
        if ($rdvId !== null) {
            $check = $pdo->prepare('
                SELECT id FROM notification
                WHERE user_id = :user_id 
                  AND rendez_vous_id = :rdv_id 
                  AND type = :type
                  AND created_at >= NOW() - INTERVAL \'24 hours\'
                LIMIT 1
            ');
            $check->execute([
                ':user_id' => $userId,
                ':rdv_id'  => $rdvId,
                ':type'    => $type
            ]);
            if ($check->fetch()) {
                // Déjà notifié pour cet événement
                return;
            }
        }

        $stmt = $pdo->prepare('
            INSERT INTO notification (user_id, titre, message, type, rendez_vous_id)
            VALUES (:user_id, :titre, :message, :type, :rendez_vous_id)
        ');
        $stmt->execute([
            ':user_id'        => $userId,
            ':titre'          => $titre,
            ':message'        => $message,
            ':type'           => $type,
            ':rendez_vous_id' => $rdvId
        ]);
    } catch (PDOException $e) {
        error_log("Erreur création notification : " . $e->getMessage());
    }
}

// Vérifie si un créneau horaire est libre pour un médecin donné (aucun autre rendez-vous confirmé)
function isDoctorSlotAvailable(PDO $pdo, int $doctorId, string $date, string $heureDebut, string $heureFin, ?int $excludeRdvId = null): bool {
    $hDebut = strlen($heureDebut) === 5 ? ($heureDebut . ':00') : $heureDebut;
    $hFin = strlen($heureFin) === 5 ? ($heureFin . ':00') : $heureFin;

    $sql = '
        SELECT id FROM rendez_vous
        WHERE doctor_id = :doc_id
          AND id != :exclude_id
          AND statut = \'confirme\'
          AND date_rendez_vous::date = :slot_date
          AND heure_rendez_vous < :slot_fin
          AND :slot_debut < COALESCE(heure_fin, (heure_rendez_vous + INTERVAL \'1 hour\')::time)
        LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':doc_id'     => $doctorId,
        ':exclude_id' => $excludeRdvId ?? 0,
        ':slot_date'  => $date,
        ':slot_debut' => $hDebut,
        ':slot_fin'   => $hFin
    ]);

    return $stmt->fetch() === false; // Libre si aucun rendez-vous confirmé en conflit
}

// Vérifie si un patient a déjà un rendez-vous actif (en attente ou confirmé) avec un médecin
function hasActiveAppointmentWithDoctor(PDO $pdo, int $userId, int $doctorId, ?int $excludeRdvId = null): bool {
    $sql = '
        SELECT id, statut, date_rendez_vous FROM rendez_vous
        WHERE user_id = :uid
          AND doctor_id = :doc_id
          AND id != :exclude_id
          AND statut IN (\'en_attente\', \'confirme\')
        LIMIT 1
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid'        => $userId,
        ':doc_id'     => $doctorId,
        ':exclude_id' => $excludeRdvId ?? 0
    ]);
    return $stmt->fetch() !== false;
}

// Récupère la liste des créneaux occupés pour un médecin à une date donnée
function getDoctorOccupiedSlots(PDO $pdo, int $doctorId, string $date, ?int $excludeRdvId = null): array {
    $sql = '
        SELECT heure_rendez_vous AS debut, COALESCE(heure_fin, (heure_rendez_vous + INTERVAL \'1 hour\')::time) AS fin, statut
        FROM rendez_vous
        WHERE doctor_id = :doc_id
          AND id != :exclude_id
          AND statut = \'confirme\'
          AND date_rendez_vous::date = :slot_date
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':doc_id'     => $doctorId,
        ':exclude_id' => $excludeRdvId ?? 0,
        ':slot_date'  => $date
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

