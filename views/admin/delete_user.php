<?php
session_start();
include('../../db.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($user_id === false) {
        $_SESSION['error_message'] = "ID de l'utilisateur invalide.";
    } else {
        try {
            $sql = $pdo->prepare("DELETE FROM \"user\" WHERE id = :id");
            $sql->bindParam(':id', $user_id, PDO::PARAM_INT);

            if ($sql->execute()) {
                $_SESSION['success_message'] = "Utilisateur supprimé avec succès.";
            } else {
                $_SESSION['error_message'] = "Une erreur s'est produite lors de la suppression de l'utilisateur.";
            }
        } catch (PDOException $e) {
            // Empêche le plantage si l'utilisateur a des rendez-vous ou dossiers liés (Contrainte de clé étrangère)
            error_log("Erreur suppression utilisateur : " . $e->getMessage());
            $_SESSION['error_message'] = "Impossible de supprimer cet utilisateur car il possède des données associées (rendez-vous ou dossiers).";
        }
    }
} else {
    $_SESSION['error_message'] = "ID de l'utilisateur non fourni.";
}

header('Location: ./admin.php');
exit();
?>