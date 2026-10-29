<?php
// views/doctor/delete_dossier.php - Suppression sécurisée d'un dossier médical
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['dossier_id'])) {
    $dossier_id = filter_var($_POST['dossier_id'] ?? $_GET['dossier_id'] ?? null, FILTER_VALIDATE_INT);
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error_message = "Requête invalide ou session expirée.";
    } elseif ($dossier_id === false || $dossier_id < 1) {
        $error_message = "Identifiant de dossier invalide.";
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "DELETE FROM dossier_medical WHERE dossier_id = :dossier_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dossier_id' => $dossier_id]);

            $pdo->commit();
            header("Location: ./doctor.php?deleted=success");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur suppression dossier : " . $e->getMessage());
            $error_message = "Impossible de supprimer ce dossier médical (il est potentiellement lié à une facture ou prescription active).";
        }
    }
} else {
    $error_message = "Aucun identifiant de dossier n'a été fourni.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de Suppression | ClinicManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'DM Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex justify-center items-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl max-w-md w-full text-center">
        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-rose-50 text-rose-600 mb-5">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 mb-2">Opération non effectuée</h2>
        <p class="text-sm text-slate-500 mb-6">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <a href="./doctor.php" class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition text-sm">
            Retourner au tableau de bord
        </a>
    </div>
</body>
</html>
