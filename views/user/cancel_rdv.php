<?php
// views/user/cancel_rdv.php - Annulation d'un rendez-vous par le patient
require_once __DIR__ . '/../../includes/auth.php';
requireRole('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./user.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    $_SESSION['message'] = "Session expirée. Veuillez réessayer.";
    $_SESSION['message_type'] = 'error';
    header('Location: ./user.php');
    exit;
}

$rdvId = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($rdvId) {
    try {
        $stmt = $pdo->prepare('
            UPDATE rendez_vous 
            SET statut = \'annule\', is_confirmed = false 
            WHERE id = :id AND user_id = :user_id AND statut = \'en_attente\'
        ');
        $stmt->execute([':id' => $rdvId, ':user_id' => $userId]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Votre rendez-vous a été annulé.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Impossible d'annuler ce rendez-vous (déjà confirmé ou introuvable).";
            $_SESSION['message_type'] = 'error';
        }
    } catch (PDOException $e) {
        error_log("Erreur annulation rdv: " . $e->getMessage());
        $_SESSION['message'] = "Une erreur est survenue lors de l'annulation.";
        $_SESSION['message_type'] = 'error';
    }
}

header('Location: ./user.php#mes_rendez_vous');
exit;

