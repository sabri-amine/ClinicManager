<?php
// views/admin/delete_user.php - Archivage, Restauration et Suppression sécurisée
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$currentAdminId = (int)($_SESSION['user_id'] ?? 0);
$userId = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? $_GET['action'] ?? 'archive'; // 'archive', 'restore', ou 'delete'
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (!verifyCsrfToken($csrfToken)) {
    $_SESSION['error_message'] = "Jeton de sécurité invalide ou session expirée.";
    header('Location: ./admin.php');
    exit();
}

if (!$userId || $userId < 1) {
    $_SESSION['error_message'] = "ID utilisateur invalide.";
    header('Location: ./admin.php');
    exit();
}

// Empêcher l'auto-suppression ou l'auto-archivage du compte courant
if ($userId === $currentAdminId) {
    $_SESSION['error_message'] = "Action refusée : vous ne pouvez pas archiver ou supprimer votre propre compte administrateur.";
    header('Location: ./admin.php');
    exit();
}

try {
    if ($action === 'archive') {
        // Soft delete (Archivage selon les spécifications du projet)
        $stmt = $pdo->prepare('UPDATE "user" SET status = \'archivé\', updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $_SESSION['success_message'] = "Le compte utilisateur a été archivé avec succès.";

    } elseif ($action === 'restore') {
        // Restauration d'un compte archivé
        $stmt = $pdo->prepare('UPDATE "user" SET status = \'actif\', updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $_SESSION['success_message'] = "Le compte utilisateur a été restauré avec succès.";

    } elseif ($action === 'delete') {
        // Suppression définitive sécurisée
        $stmt = $pdo->prepare('DELETE FROM "user" WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $_SESSION['success_message'] = "L'utilisateur a été définitivement supprimé.";
    }

} catch (PDOException $e) {
    error_log("Erreur modification utilisateur : " . $e->getMessage());
    $_SESSION['error_message'] = "Impossible d'effectuer cette opération : l'utilisateur est lié à des données cliniques actives (rendez-vous ou dossiers). Préférez l'archivage.";
}

header('Location: ./admin.php');
exit();
