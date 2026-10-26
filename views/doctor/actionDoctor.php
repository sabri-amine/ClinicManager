<?php
session_start();
include('../../db.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient      = trim($_POST['patient'] ?? '');
    $date_dossier = trim($_POST['date_dossier'] ?? '');
    $diagnostic   = trim($_POST['diagnostic'] ?? '');
    $traitement   = trim($_POST['traitement'] ?? '');
    $num          = trim($_POST['num'] ?? '');
    $type         = trim($_POST['type'] ?? '');
    $statut       = trim($_POST['statut'] ?? '');

    // Validation de base
    if (!empty($patient) && !empty($date_dossier) && !empty($diagnostic) && !empty($traitement) && !empty($num) && !empty($type) && !empty($statut)) {
        try {
            // Démarrer une transaction pour garantir l'intégrité des deux insertions
            $pdo->beginTransaction();

            // 1. Insertion du dossier médical avec RETURNING pour récupérer l'ID de manière fiable sous PostgreSQL
            $sql1 = "INSERT INTO dossier_medical (date_dossie, diagnostic, traitement, user_id) 
                     VALUES (:date_dossie, :diagnostic, :traitement, :user_id) 
                     RETURNING dossier_id";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':date_dossie' => $date_dossier,
                ':diagnostic'  => $diagnostic,
                ':traitement'  => $traitement,
                ':user_id'     => $patient
            ]);
            
            $dossier_id = $stmt1->fetchColumn();

            if (!$dossier_id) {
                throw new Exception("Impossible de récupérer l'ID du dossier inséré.");
            }

            // 2. Insertion de la chambre liée au dossier
            $sql2 = "INSERT INTO chambre (num, type, statut, dossier_id) 
                     VALUES (:num, :type, :statut, :dossier_id)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':num'        => $num,
                ':type'       => $type,
                ':statut'     => $statut,
                ':dossier_id' => $dossier_id
            ]);

            // Valider la transaction
            $pdo->commit();

            // Redirection propre vers le dashboard
            header("Location: ./doctor.php?success=1");
            exit();

        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur ajout dossier médical : " . $e->getMessage());
            echo "Erreur lors de l'ajout du dossier médical: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        echo "Tous les champs sont requis.";
    }
} else {
    header("Location: ./doctor.php");
    exit();
}
?>