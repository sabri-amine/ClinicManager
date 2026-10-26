<?php
session_start();
include("../../db.php");

$error_message = "";

if (isset($_GET['dossier_id']) && !empty($_GET['dossier_id'])) {
    $dossier_id = filter_var($_GET['dossier_id'], FILTER_VALIDATE_INT);

    if ($dossier_id === false) {
        $error_message = "Identifiant de dossier invalide.";
    } else {
        try {
            // Démarrer une transaction pour assurer l'intégrité (suppression liée chambre / dossier si nécessaire)
            $pdo->beginTransaction();

            // 1. (Optionnel selon votre schéma) Libérer ou supprimer la chambre associée si elle dépend du dossier
            // $stmtChambre = $pdo->prepare("DELETE FROM chambre WHERE chambre_id = :dossier_id");
            // $stmtChambre->execute([':dossier_id' => $dossier_id]);

            // 2. Supprimer le dossier médical
            $sql = "DELETE FROM dossier_medical WHERE dossier_id = :dossier_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':dossier_id', $dossier_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $pdo->commit();
                header("Location: ./doctor.php?deleted=success");
                exit();
            } else {
                $pdo->rollBack();
                $error_message = "Une erreur est survenue lors de la suppression de l'enregistrement.";
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur suppression dossier : " . $e->getMessage());
            $error_message = "Erreur de base de données : Impossible de supprimer ce dossier (il est peut-être lié à d'autres données).";
        }
    }
} else {
    $error_message = "Aucun identifiant de dossier (dossier_id) n'a été fourni.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de Suppression - Medic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 flex justify-center items-center min-h-screen p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xl max-w-md w-full text-center">
        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-rose-100 dark:bg-rose-500/20 text-rose-600 mb-5">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Échec de la suppression</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <a href="./doctor.php" class="inline-block w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition text-sm">
            Retourner au tableau de bord
        </a>
    </div>
</body>
</html>