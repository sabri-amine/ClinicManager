<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Vérification de la session utilisateur
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $userId = (int)$_SESSION['user_id'];
    $CNI = trim($_POST["cni"] ?? '');
    $date = trim($_POST["date"] ?? '');
    $heureRaw = trim($_POST["heure"] ?? '09:00');
    $doctorId = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);

    if (!empty($userId) && !empty($CNI) && !empty($date)) {
        try {
            // Médecin par défaut si non spécifié
            if (!$doctorId) {
                $docStmt = $pdo->query("SELECT id FROM \"user\" WHERE role_id = (SELECT id FROM role WHERE name = 'doctor') AND status = 'actif' LIMIT 1");
                $doctorId = (int)($docStmt->fetchColumn() ?: 0);
            }

            // 1. RÈGLE : Un seul rendez-vous actif (en attente ou confirmé) par patient et par médecin
            if ($doctorId > 0 && hasActiveAppointmentWithDoctor($pdo, $userId, $doctorId)) {
                $_SESSION['message'] = "Vous avez déjà un rendez-vous actif (en attente ou confirmé) avec ce médecin. Vous devez attendre que cette consultation soit terminée ou annulée pour en demander une nouvelle.";
                $_SESSION['message_type'] = 'error';
                header("Location: ./user.php#mes_rendez_vous");
                exit();
            }

            // Calcul des heures de début et fin pour la date souhaitée
            $heureDebut = strlen($heureRaw) === 5 ? ($heureRaw . ':00') : $heureRaw;
            $heureFin = date('H:i:s', strtotime($heureDebut . ' + 1 hour'));

            // 2. Insertion de la demande au statut 'en_attente' (le médecin examinera et fixera la date et l'heure)
            $sql = "INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed) 
                    VALUES (:cni, :date_rendez_vous, :heure_rendez_vous, :heure_fin, :user_id, :doctor_id, 'en_attente', false)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cni'               => $CNI,
                ':date_rendez_vous'  => $date . ' ' . $heureDebut,
                ':heure_rendez_vous' => $heureDebut,
                ':heure_fin'         => $heureFin,
                ':user_id'           => $userId,
                ':doctor_id'         => $doctorId ?: null
            ]);

            $_SESSION['message'] = "Votre demande de rendez-vous a été envoyée avec succès ! Le médecin va l'examiner et fixer la date et l'heure de votre consultation.";
            $_SESSION['message_type'] = 'success'; 
            header("Location: ./user.php#mes_rendez_vous");
            exit();

        } catch (PDOException $e) {
            error_log("Erreur création rendez-vous: " . $e->getMessage());
            if (strpos($e->getMessage(), 'uq_active_rdv_patient_doctor') !== false) {
                $_SESSION['message'] = "Vous possédez déjà un rendez-vous actif avec ce médecin.";
            } elseif (strpos($e->getMessage(), 'Double réservation') !== false) {
                $_SESSION['message'] = "Ce créneau horaire est déjà indisponible pour ce médecin.";
            } else {
                $_SESSION['message'] = "Une erreur est survenue lors de l'enregistrement de votre rendez-vous.";
            }
            $_SESSION['message_type'] = 'error';
            header("Location: ./user.php#rendez_vous");
            exit();
        }
    } else {
        $_SESSION['message'] = "Veuillez remplir tous les champs obligatoires.";
        $_SESSION['message_type'] = 'error';
        header("Location: ./user.php#rendez_vous");
        exit();
    }
} else {
    header("Location: ./user.php");
    exit();
}
?>
