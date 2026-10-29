<?php
// views/doctor/form.php - Création d'un nouveau dossier médical & ordonnance
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

$csrfToken = getCsrfToken();

// Récupération des patients actifs uniquement
$patients = [];
try {
    $stmt = $pdo->query('
        SELECT u.id, u.full_name, u.email
        FROM "user" u
        JOIN role r ON u.role_id = r.id
        WHERE r.name = \'user\' AND u.status = \'actif\'
        ORDER BY u.full_name ASC
    ');
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur chargement patients: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Dossier Médical | ClinicManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body { font-family: 'DM Sans', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="text-slate-800 min-h-screen p-6 sm:p-10 flex items-center justify-center">

    <div class="max-w-4xl w-full bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 sm:p-10">
        
        <div class="flex items-center justify-between pb-6 border-b border-slate-100">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Consultation Médicale</span>
                <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Nouveau Dossier Médical & Prescription</h1>
                <p class="text-xs text-slate-500 mt-1">Renseignez le diagnostic, le traitement et optionnellement une ordonnance ou une chambre.</p>
            </div>
            <a href="doctor.php" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <form action="actionDoctor.php" method="POST" class="mt-6 space-y-8">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <!-- Bloc Dossier Médical -->
            <div class="space-y-5 bg-slate-50 p-6 rounded-2xl border border-slate-200/80">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-journal-medical text-blue-600"></i> Informations Médicales
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Sélection Patient -->
                    <div>
                        <label for="patient" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Patient *</label>
                        <select name="patient" id="patient" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:border-blue-600 focus:outline-none">
                            <option value="">-- Choisir un patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?> (<?= e($p['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date du Dossier -->
                    <div>
                        <label for="date_dossier" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date de consultation *</label>
                        <input type="date" id="date_dossier" name="date_dossier" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:border-blue-600 focus:outline-none">
                    </div>

                    <!-- Diagnostic -->
                    <div class="md:col-span-2">
                        <label for="diagnostic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Diagnostic Médical *</label>
                        <textarea id="diagnostic" name="diagnostic" rows="3" required placeholder="Observations cliniques et diagnostic..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:border-blue-600 focus:outline-none resize-none"></textarea>
                    </div>

                    <!-- Traitement -->
                    <div class="md:col-span-2">
                        <label for="traitement" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Protocole de Traitement *</label>
                        <textarea id="traitement" name="traitement" rows="3" required placeholder="Plan de traitement et recommandations..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:border-blue-600 focus:outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Bloc Ordonnance Prescription (Optionnel) -->
            <div class="space-y-4 bg-emerald-50/50 p-6 rounded-2xl border border-emerald-100">
                <h3 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                    <i class="bi bi-prescription2 text-emerald-600 text-lg"></i> Prescription d'Ordonnance (Optionnel)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="medicaments" class="block text-xs font-semibold text-slate-700 mb-1">Médicaments prescrits</label>
                        <input type="text" id="medicaments" name="medicaments" placeholder="Ex: Paracétamol 1g, Amoxicilline 500mg" class="w-full px-4 py-2 text-sm bg-white border border-emerald-200 rounded-xl focus:border-emerald-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="posologie" class="block text-xs font-semibold text-slate-700 mb-1">Posologie</label>
                        <input type="text" id="posologie" name="posologie" placeholder="Ex: 1 comprimé 3x/jour pendant 5 jours" class="w-full px-4 py-2 text-sm bg-white border border-emerald-200 rounded-xl focus:border-emerald-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="instructions" class="block text-xs font-semibold text-slate-700 mb-1">Instructions particulières</label>
                        <input type="text" id="instructions" name="instructions" placeholder="Ex: À prendre au milieu des repas" class="w-full px-4 py-2 text-sm bg-white border border-emerald-200 rounded-xl focus:border-emerald-600 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Bloc Attribution de Chambre (Optionnel) -->
            <div class="space-y-4 bg-slate-50 p-6 rounded-2xl border border-slate-200/80">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-hospital text-blue-600"></i> Affectation de Chambre (Optionnel si hospitalisation)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="num" class="block text-xs font-semibold text-slate-600 mb-1">Numéro de chambre</label>
                        <input type="number" id="num" name="num" placeholder="Ex: 102" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="type" class="block text-xs font-semibold text-slate-600 mb-1">Type</label>
                        <input type="text" id="type" name="type" placeholder="Simple, Double, VIP" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="statut" class="block text-xs font-semibold text-slate-600 mb-1">Statut Chambre</label>
                        <select id="statut" name="statut" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:outline-none">
                            <option value="Disponible">Disponible</option>
                            <option value="Occupée" selected>Occupée</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="doctor.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Annuler
                </a>
                <button type="submit" class="px-7 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                    <i class="bi bi-check2-circle text-base"></i> Enregistrer le dossier
                </button>
            </div>
        </form>

    </div>

</body>
</html>
