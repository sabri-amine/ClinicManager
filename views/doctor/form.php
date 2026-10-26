<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Dossier Médical & Chambre - Medic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 flex justify-center items-center min-h-screen p-4 sm:p-6">

    <form action="actionDoctor.php" method="POST" class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xl w-full max-w-4xl transition-all">
        
        <div class="mb-8 text-center">
            <span class="inline-block px-3 py-1 mb-2 text-xs font-semibold uppercase tracking-wider text-purple-600 bg-purple-50 dark:bg-purple-900/30 rounded-full">
                Gestion Clinique
            </span>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight">
                Dossier Médical & Attribution de Chambre
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Remplissez les informations ci-dessous pour créer un nouveau dossier patient.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Colonne 1: Dossier Médical -->
            <div class="space-y-5 bg-gray-50/50 dark:bg-gray-700/20 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/40">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">Dossier Médical</h3>
                </div>

                <!-- Patient selection -->
                <div>
                    <label for="patient" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Patient :</label>
                    <?php 
                        include("../../db.php");
                        $sql = "SELECT u.id, u.full_name FROM \"user\" AS u";
                        $sqlState = $pdo->prepare($sql);
                        $sqlState->execute();
                        $patients = $sqlState->fetchAll();
                    ?>
                    <select name="patient" id="patient" class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs">
                        <option value="">-- Choisir un patient --</option>
                        <?php 
                            foreach ($patients as $patient) {
                                echo "<option value='" . $patient['id'] . "'>" . htmlspecialchars($patient['full_name']) . "</option>";
                            }
                        ?>
                    </select>
                </div>

                <!-- Date du Dossier -->
                <div>
                    <label for="date_dossier" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Date du Dossier :</label>
                    <input type="date" id="date_dossier" name="date_dossier" class="w-full px-3.5 py-2 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs" required>
                </div>

                <!-- Diagnostic -->
                <div>
                    <label for="diagnostic" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Diagnostic :</label>
                    <textarea id="diagnostic" name="diagnostic" rows="3" placeholder="Entrer le diagnostic médical..." class="w-full px-3.5 py-2 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs resize-none" required></textarea>
                </div>

                <!-- Traitement -->
                <div>
                    <label for="traitement" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Traitement :</label>
                    <textarea id="traitement" name="traitement" rows="3" placeholder="Entrer le traitement prescrit..." class="w-full px-3.5 py-2 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs resize-none" required></textarea>
                </div>
            </div>

            <!-- Colonne 2: Chambre -->
            <div class="space-y-5 bg-gray-50/50 dark:bg-gray-700/20 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/40">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">Affectation de Chambre</h3>
                </div>

                <!-- Numéro de la Chambre -->
                <div>
                    <label for="num" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Numéro de la Chambre :</label>
                    <input type="text" id="num" name="num" placeholder="Ex: 304" class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs" required>
                </div>

                <!-- Type de Chambre -->
                <div>
                    <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Type de Chambre :</label>
                    <input type="text" id="type" name="type" placeholder="Ex: Simple, Double, VIP" class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs" required>
                </div>

                <!-- Statut -->
                <div>
                    <label for="statut" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Statut :</label>
                    <select id="statut" name="statut" class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition shadow-2xs" required>
                        <option value="Disponible">Disponible</option>
                        <option value="Occupée">Occupée</option>
                    </select>
                </div>
            </div>

        </div>

        <!-- Bouton Soumettre -->
        <div class="mt-8">
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg shadow-purple-600/20 focus:outline-none focus:ring-2 focus:ring-purple-500/30 transition-all text-sm tracking-wide">
                Enregistrer le dossier et la chambre
            </button>
        </div>

    </form>
</body>
</html>