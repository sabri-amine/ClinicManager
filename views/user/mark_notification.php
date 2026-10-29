<?php
// views/user/mark_notification.php - Marquer une ou toutes les notifications comme lues
require_once __DIR__ . '/../../includes/auth.php';
requireRole('user');

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['notification_id']) || isset($_GET['mark_all'])) {
    $notifId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT) 
             ?: filter_input(INPUT_GET, 'notification_id', FILTER_VALIDATE_INT);
    $markAll = (isset($_POST['mark_all']) && $_POST['mark_all'] == '1') 
             || (isset($_GET['mark_all']) && $_GET['mark_all'] == '1');

    try {
        if ($markAll) {
            $stmt = $pdo->prepare('UPDATE notification SET is_read = true WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
        } elseif ($notifId) {
            $stmt = $pdo->prepare('UPDATE notification SET is_read = true WHERE id = :id AND user_id = :uid');
            $stmt->execute([':id' => $notifId, ':uid' => $userId]);
        }

        // Si requête AJAX JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erreur mise à jour notification : " . $e->getMessage());
    }
}

// Redirection par défaut vers la page patient
$redirectRdv = filter_input(INPUT_GET, 'rdv_id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
if ($redirectRdv) {
    header('Location: ./user.php#rdv_' . $redirectRdv);
} else {
    header('Location: ./user.php');
}
exit;
