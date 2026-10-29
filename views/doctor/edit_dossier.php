<?php
// views/doctor/edit_dossier.php - Modification d'un dossier médical
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

$doctorId = (int)($_SESSION['user_id'] ?? 0);
$csrfToken = getCsrfToken();
$error = '';
$success = '';

$dossierId = filter_input(INPUT_GET, 'dossier_id', FILTER_VALIDATE_INT);
if (!$dossierId) {
    header('Location: doctor.php');
    exit;
}

// Récupération du dossier médical et de sa chambre éventuelle
$stmt = $pdo->prepare('
    SELECT d.dossier_id, d.date_dossie, d.diagnostic, d.traitement, d.user_id, d.doctor_id,
           u.full_name AS patient_name,
           c.chambre_id, c.num AS chambre_num, c.type AS chambre_type, c.statut AS chambre_statut
    FROM dossier_medical d
    JOIN "user" u ON d.user_id = u.id
    LEFT JOIN chambre c ON d.dossier_id = c.dossier_id
    WHERE d.dossier_id = :id
');
$stmt->execute([':id' => $dossierId]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    header('Location: doctor.php');
    exit;
}

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = "Session expirée. Veuillez recharger la page.";
    } else {
        $dateDossier = trim($_POST['date_dossier'] ?? '');
        $diagnostic = trim($_POST['diagnostic'] ?? '');
        $traitement = trim($_POST['traitement'] ?? '');
        $chambreNum = trim($_POST['chambre_num'] ?? '');
        $chambreType = trim($_POST['chambre_type'] ?? '');
        $chambreStatut = trim($_POST['chambre_statut'] ?? 'Disponible');

        if (empty($dateDossier) || empty($diagnostic) || empty($traitement)) {
            $error = "La date, le diagnostic et le traitement sont obligatoires.";
        } else {
            try {
                $pdo->beginTransaction();

                // Mise à jour du dossier
                $updateDossier = $pdo->prepare('
                    UPDATE dossier_medical
                    SET date_dossie = :date_dossie,
                        diagnostic = :diagnostic,
                        traitement = :traitement,
                        doctor_id = :doctor_id
                    WHERE dossier_id = :id
                ');
                $updateDossier->execute([
                    ':date_dossie' => $dateDossier,
                    ':diagnostic' => $diagnostic,
                    ':traitement' => $traitement,
                    ':doctor_id' => $doctorId,
                    ':id' => $dossierId
                ]);

                // Gestion de la chambre associée
                if (!empty($chambreNum)) {
                    if (!empty($dossier['chambre_id'])) {
                        $updateChambre = $pdo->prepare('
                            UPDATE chambre
                            SET num = :num, type = :type, statut = :statut
                            WHERE dossier_id = :dossier_id
                        ');
                        $updateChambre->execute([
                            ':num' => (int)$chambreNum,
                            ':type' => $chambreType,
                            ':statut' => $chambreStatut,
                            ':dossier_id' => $dossierId
                        ]);
                    } else {
                        $insertChambre = $pdo->prepare('
                            INSERT INTO chambre (num, type, statut, dossier_id)
                            VALUES (:num, :type, :statut, :dossier_id)
                        ');
                        $insertChambre->execute([
                            ':num' => (int)$chambreNum,
                            ':type' => $chambreType,
                            ':statut' => $chambreStatut,
                            ':dossier_id' => $dossierId
                        ]);
                    }
                }

                $pdo->commit();
                header('Location: doctor.php?updated=1');
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Erreur modification dossier : " . $e->getMessage());
                $error = "Une erreur est survenue lors de la mise à jour.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Dossier Médical | ClinicManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body { font-family: 'DM Sans', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="text-slate-800 min-h-screen p-6 sm:p-10 flex items-center justify-center">

    <div class="max-w-3xl w-full bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 sm:p-10">
        
        <div class="flex items-center justify-between pb-6 border-b border-slate-100">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Dossier Médical N° <?= (int)$dossier['dossier_id'] ?></span>
                <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Modification du dossier</h1>
                <p class="text-xs text-slate-500 mt-1">Patient : <strong class="text-slate-800"><?= e($dossier['patient_name']) ?></strong></p>
            </div>
            <a href="doctor.php" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
                <i class="bi bi-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mt-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="mt-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date -->
                <div class="md:col-span-2">
                    <label for="date_dossier" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date du dossier *</label>
                    <input type="date" id="date_dossier" name="date_dossier" required value="<?= e(substr($dossier['date_dossie'], 0, 10)) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
                </div>

                <!-- Diagnostic -->
                <div class="md:col-span-2">
                    <label for="diagnostic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Diagnostic Médical *</label>
                    <textarea id="diagnostic" name="diagnostic" rows="3" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"><?= e($dossier['diagnostic']) ?></textarea>
                </div>

                <!-- Traitement -->
                <div class="md:col-span-2">
                    <label for="traitement" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Traitement & Recommandations *</label>
                    <textarea id="traitement" name="traitement" rows="3" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"><?= e($dossier['traitement']) ?></textarea>
                </div>
            </div>

            <!-- Section Chambre optionnelle -->
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-hospital text-blue-600"></i> Attribution de Chambre (Optionnel)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="chambre_num" class="block text-xs font-semibold text-slate-600 mb-1">N° Chambre</label>
                        <input type="number" id="chambre_num" name="chambre_num" value="<?= e($dossier['chambre_num'] ?? '') ?>" placeholder="Ex: 204" class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="chambre_type" class="block text-xs font-semibold text-slate-600 mb-1">Type de chambre</label>
                        <input type="text" id="chambre_type" name="chambre_type" value="<?= e($dossier['chambre_type'] ?? '') ?>" placeholder="Simple, VIP..." class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="chambre_statut" class="block text-xs font-semibold text-slate-600 mb-1">Statut Chambre</label>
                        <select id="chambre_statut" name="chambre_statut" class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                            <option value="Disponible" <?= ($dossier['chambre_statut'] ?? '') === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="Occupée" <?= ($dossier['chambre_statut'] ?? '') === 'Occupée' ? 'selected' : '' ?>>Occupée</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="doctor.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-md shadow-blue-500/20 transition">
                    Enregistrer les modifications
                </button>
            </div>
        </form>

    </div>

</body>
</html>

