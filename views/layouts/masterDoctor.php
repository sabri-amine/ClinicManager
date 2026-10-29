<?php
// views/layouts/masterDoctor.php - Dashboard Médecin ClinicManager
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

$doctorId = (int)($_SESSION['user_id'] ?? 0);
$doctorName = $_SESSION['full_name'] ?? 'Médecin';

// Traitement du changement de statut d'un rendez-vous par le médecin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($token)) {
        $rdvId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $actionBtn = trim($_POST['action_btn'] ?? $_POST['new_status'] ?? '');
        $fixDate = trim($_POST['fix_date'] ?? $_POST['prop_date'] ?? '');
        $fixHeure = trim($_POST['fix_heure'] ?? $_POST['prop_heure_debut'] ?? '09:00');
        $noteMedecin = trim($_POST['note_medecin'] ?? '');

        if ($rdvId) {
            try {
                $stmtRdv = $pdo->prepare('
                    SELECT r.*, u.full_name AS patient_name, u.email AS patient_email
                    FROM rendez_vous r
                    JOIN "user" u ON r.user_id = u.id
                    WHERE r.id = :id
                ');
                $stmtRdv->execute([':id' => $rdvId]);
                $rdvData = $stmtRdv->fetch(PDO::FETCH_ASSOC);

                if ($rdvData) {
                    $patientId = (int)$rdvData['user_id'];
                    $docNomComplet = !empty($doctorName) ? $doctorName : 'le médecin';

                    // 1. LE MÉDECIN ACCEPTE LA DEMANDE ET FIXE LA DATE & L'HEURE EXACTES
                    if ($actionBtn === 'accepter' || $actionBtn === 'confirme') {
                        if (empty($fixDate)) {
                            $_SESSION['message'] = "Veuillez choisir une date pour accepter et confirmer ce rendez-vous.";
                            $_SESSION['message_type'] = 'error';
                            header("Location: ./doctor.php");
                            exit();
                        }

                        $hDebutFormat = strlen($fixHeure) === 5 ? ($fixHeure . ':00') : $fixHeure;
                        $hFinFormat = date('H:i:s', strtotime($hDebutFormat . ' + 1 hour'));

                        // Vérification de disponibilité : aucun chevauchement avec un autre rendez-vous confirmé
                        if (!isDoctorSlotAvailable($pdo, $doctorId, $fixDate, $hDebutFormat, $hFinFormat, $rdvId)) {
                            $debutAffiche = substr($hDebutFormat, 0, 5);
                            $finAffiche = substr($hFinFormat, 0, 5);
                            $_SESSION['message'] = "Impossible de confirmer : vous avez déjà un rendez-vous confirmé le " . date('d/m/Y', strtotime($fixDate)) . " de $debutAffiche à $finAffiche.";
                            $_SESSION['message_type'] = 'error';
                            header("Location: ./doctor.php");
                            exit();
                        }

                        // Enregistrer l'acceptation et la confirmation de la date/heure choisies
                        $newDateTime = $fixDate . ' ' . $hDebutFormat;
                        $updateStmt = $pdo->prepare('
                            UPDATE rendez_vous
                            SET statut = \'confirme\',
                                is_confirmed = true,
                                date_rendez_vous = :ndatetime,
                                heure_rendez_vous = :ndebut,
                                heure_fin = :nfin,
                                date_proposee = NULL,
                                heure_debut_proposee = NULL,
                                heure_fin_proposee = NULL,
                                note_medecin = :note
                            WHERE id = :id
                        ');
                        $updateStmt->execute([
                            ':ndatetime' => $newDateTime,
                            ':ndebut'    => $hDebutFormat,
                            ':nfin'      => $hFinFormat,
                            ':note'      => $noteMedecin,
                            ':id'        => $rdvId
                        ]);

                        // NOTIFICATION AUTOMATIQUE EXACTE :
                        // « Votre demande de rendez-vous a été acceptée. Votre rendez-vous avec le Dr [Nom] est prévu le [Date] à [Heure]. »
                        $dateFr = date('d/m/Y', strtotime($fixDate));
                        $heureFr = substr($hDebutFormat, 0, 5);

                        $titre = "Demande de rendez-vous acceptée";
                        $msg = "Votre demande de rendez-vous a été acceptée. Votre rendez-vous avec le Dr $docNomComplet est prévu le $dateFr à $heureFr.";

                        addNotification($pdo, $patientId, $titre, $msg, 'success', $rdvId);

                        $_SESSION['message'] = "Demande acceptée ! Le rendez-vous a été fixé au $dateFr à $heureFr et le patient a été notifié.";
                        $_SESSION['message_type'] = 'success';
                        header("Location: ./doctor.php");
                        exit();

                    // 2. LE MÉDECIN REFUSE LA DEMANDE
                    } elseif ($actionBtn === 'refuser' || $actionBtn === 'refuse') {
                        $updateStmt = $pdo->prepare('
                            UPDATE rendez_vous
                            SET statut = \'refuse\',
                                is_confirmed = false,
                                date_proposee = NULL,
                                heure_debut_proposee = NULL,
                                heure_fin_proposee = NULL,
                                note_medecin = :note
                            WHERE id = :id
                        ');
                        $updateStmt->execute([
                            ':note' => $noteMedecin,
                            ':id'   => $rdvId
                        ]);

                        $titre = "Demande de rendez-vous refusée";
                        $msg = "Votre demande de rendez-vous a été refusée par le Dr $docNomComplet.";
                        if (!empty($noteMedecin)) $msg .= " Précision : " . $noteMedecin;

                        addNotification($pdo, $patientId, $titre, $msg, 'danger', $rdvId);

                        $_SESSION['message'] = "La demande de rendez-vous a été refusée.";
                        $_SESSION['message_type'] = 'info';
                        header("Location: ./doctor.php");
                        exit();

                    // 3. CONSULTATION TERMINÉE
                    } elseif ($actionBtn === 'termine') {
                        $updateStmt = $pdo->prepare('UPDATE rendez_vous SET statut = \'termine\', is_confirmed = true WHERE id = :id');
                        $updateStmt->execute([':id' => $rdvId]);

                        $titre = "Consultation terminée";
                        $msg = "Votre consultation avec le Dr $docNomComplet est terminée.";
                        addNotification($pdo, $patientId, $titre, $msg, 'termine', $rdvId);

                        $_SESSION['message'] = "Consultation marquée comme terminée. Le créneau et le compte du patient sont désormais libérés.";
                        $_SESSION['message_type'] = 'success';
                        header("Location: ./doctor.php");
                        exit();

                    // 4. ANNULATION
                    } elseif ($actionBtn === 'annule') {
                        $updateStmt = $pdo->prepare('UPDATE rendez_vous SET statut = \'annule\', is_confirmed = false WHERE id = :id');
                        $updateStmt->execute([':id' => $rdvId]);

                        $titre = "Rendez-vous annulé";
                        $msg = "Votre rendez-vous avec le Dr $docNomComplet a été annulé.";
                        addNotification($pdo, $patientId, $titre, $msg, 'annule', $rdvId);

                        $_SESSION['message'] = "Rendez-vous annulé.";
                        $_SESSION['message_type'] = 'info';
                        header("Location: ./doctor.php");
                        exit();
                    }
                }
            } catch (PDOException $e) {
                error_log("Erreur modification rdv docteur: " . $e->getMessage());
                if (strpos($e->getMessage(), 'Double réservation') !== false) {
                    $_SESSION['message'] = "Conflit d'horaire : vous avez déjà un rendez-vous confirmé sur ce créneau.";
                } else {
                    $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
                }
                $_SESSION['message_type'] = 'error';
                header("Location: ./doctor.php");
                exit();
            }
        }
    }
    header('Location: ./doctor.php');
    exit;
}

// Données statistiques dynamiques réelles
$count_rendez_vous = (int)$pdo->query('SELECT count(*) FROM rendez_vous')->fetchColumn();
$count_rendez_vous_today = (int)$pdo->query('SELECT count(*) FROM rendez_vous WHERE date_rendez_vous::date = CURRENT_DATE')->fetchColumn();
$count_dossiers = (int)$pdo->query('SELECT count(*) FROM dossier_medical')->fetchColumn();
$count_ordonnances = (int)$pdo->query('SELECT count(*) FROM ordonnance')->fetchColumn();

// Inclusion des sections de contenu
include_once __DIR__ . '/../doctor/contentAdmin.php';
include_once __DIR__ . '/../doctor/dossierMedical.php';
include_once __DIR__ . '/../doctor/prescriptions.php';
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Espace Médecin | ClinicManager</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #f8fafc; }
        h1, h2, h3, h4 { font-family: 'Manrope', sans-serif; }
        .dashboard-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 24px -6px rgba(37, 99, 235, 0.1);
        }
        .tab-btn-active {
            border-color: #2563eb !important;
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }
    </style>
</head>
<body class="text-slate-800 antialiased h-full bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar latérale Médecin -->
        <aside class="z-20 hidden w-72 overflow-y-auto bg-white border-r border-slate-200/90 md:flex flex-col justify-between p-6 shrink-0 shadow-xs">
            <div>
                <!-- Logo -->
                <div class="flex items-center gap-3 px-2 mb-8">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-slate-900">Clinic<span class="text-blue-600">Manager</span></h2>
                        <p class="text-xs text-slate-400">Espace Praticien</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-1.5 font-medium text-sm">
                    <button type="button" class="btn-nav-rdv w-full flex items-center gap-3 px-4 py-3 rounded-2xl bg-blue-50 text-blue-700 font-semibold transition">
                        <i class="bi bi-calendar3 text-base"></i>
                        <span>Tous les Rendez-vous</span>
                    </button>

                    <button type="button" class="btn-nav-today w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                        <i class="bi bi-calendar-event text-base"></i>
                        <span>Rendez-vous Aujourd'hui</span>
                    </button>

                    <button type="button" class="btn-nav-dossiers w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                        <i class="bi bi-folder2-open text-base"></i>
                        <span>Dossiers Médicaux</span>
                    </button>

                    <button type="button" class="btn-nav-prescriptions w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                        <i class="bi bi-prescription2 text-base"></i>
                        <span>Ordonnances</span>
                    </button>

                    <a href="form.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                        <i class="bi bi-file-earmark-medical text-base"></i>
                        <span>Nouveau Dossier / Presc.</span>
                    </a>
                </nav>
            </div>

            <!-- Footer Sidebar -->
            <div class="pt-6 border-t border-slate-100">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-xs font-semibold text-slate-600">Connecté : Dr. <?= e($doctorName) ?></span>
                </div>
            </div>
        </aside>

        <!-- Conteneur principal -->
        <div class="flex flex-col flex-1 w-full overflow-hidden">
            
            <!-- Header supérieur -->
            <header class="sticky top-0 z-10 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-2xs py-3.5 px-6 sm:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900">Tableau de Bord Médical</h1>
                        <p class="text-xs text-slate-500">Suivi des consultations, dossiers et prescriptions de la clinique</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="bi bi-person-check-fill"></i> Dr. <?= e($doctorName) ?>
                        </span>

                        <a href="../logout/logout_action.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="hidden sm:inline">Déconnexion</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Zone de contenu défilable -->
            <main class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-8 max-w-7xl w-full mx-auto">
                
                <!-- Alertes SweetAlert pour modifications ou créations -->
                <?php if (isset($_GET['created'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Enregistré',
                                text: 'Le dossier médical et la prescription ont été créés avec succès.',
                                confirmButtonColor: '#2563eb',
                                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                            });
                        });
                    </script>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Mis à jour',
                                text: 'Le dossier médical a été modifié avec succès.',
                                confirmButtonColor: '#2563eb',
                                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                            });
                        });
                    </script>
                <?php endif; ?>

                <!-- Grille des statistiques interactives -->
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Carte 1 : Tous les Rendez-vous -->
                    <div class="dashboard-card card-rendez-vous cursor-pointer p-6 bg-white rounded-3xl border border-slate-200/90 shadow-xs relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Rendez-vous</p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= $count_rendez_vous ?></p>
                                <p class="mt-1 text-xs text-blue-600 font-semibold group-hover:underline">Consulter la liste &rarr;</p>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                                <i class="bi bi-calendar3"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte 2 : Rendez-vous Aujourd'hui -->
                    <div class="dashboard-card card-rendez-vousToDay cursor-pointer p-6 bg-white rounded-3xl border border-slate-200/90 shadow-xs relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Aujourd'hui</p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= $count_rendez_vous_today ?></p>
                                <p class="mt-1 text-xs text-emerald-600 font-semibold group-hover:underline">Voir les RDV du jour &rarr;</p>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte 3 : Dossiers Médicaux (Chiffre réel dynamique) -->
                    <div class="dashboard-card card_dossier_medical cursor-pointer p-6 bg-white rounded-3xl border border-slate-200/90 shadow-xs relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dossiers Médicaux</p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= $count_dossiers ?></p>
                                <p class="mt-1 text-xs text-purple-600 font-semibold group-hover:underline">Consulter les dossiers &rarr;</p>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte 4 : Prescriptions & Ordonnances -->
                    <div class="dashboard-card card_prescriptions cursor-pointer p-6 bg-white rounded-3xl border border-slate-200/90 shadow-xs relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ordonnances</p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900"><?= $count_ordonnances ?></p>
                                <p class="mt-1 text-xs text-amber-600 font-semibold group-hover:underline">Voir les prescriptions &rarr;</p>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                                <i class="bi bi-prescription2"></i>
                            </div>
                        </div>
                    </div>

                </section>

                <!-- Sections de contenu dynamiques -->
                <div class="space-y-6">
                    <!-- Section : Tous les RDV -->
                    <section class="contentAll_rendez_vous">
                        <?= $content ?>
                    </section>

                    <!-- Section : RDV du jour -->
                    <section class="contentAll_rendez_vousToDay hidden">
                        <?= $content_today ?>
                    </section>

                    <!-- Section : Dossiers Médicaux -->
                    <section class="dossier_medical hidden">
                        <?= $content_dossier ?>
                    </section>

                    <!-- Section : Ordonnances -->
                    <section class="prescriptions_section hidden">
                        <?= $content_prescriptions ?>
                    </section>
                </div>

            </main>
        </div>
    </div>

    <!-- Script de navigation des vues et cartes -->
    <script>
        const contentAll = document.querySelector(".contentAll_rendez_vous");
        const contentToday = document.querySelector(".contentAll_rendez_vousToDay");
        const contentDossiers = document.querySelector(".dossier_medical");
        const contentPresc = document.querySelector(".prescriptions_section");

        function hideAllSections() {
            contentAll.classList.add("hidden");
            contentToday.classList.add("hidden");
            contentDossiers.classList.add("hidden");
            contentPresc.classList.add("hidden");
        }

        // Clics Cartes
        document.querySelector(".card-rendez-vous").addEventListener("click", () => {
            hideAllSections();
            contentAll.classList.remove("hidden");
        });

        document.querySelector(".card-rendez-vousToDay").addEventListener("click", () => {
            hideAllSections();
            contentToday.classList.remove("hidden");
        });

        document.querySelector(".card_dossier_medical").addEventListener("click", () => {
            hideAllSections();
            contentDossiers.classList.remove("hidden");
        });

        document.querySelector(".card_prescriptions").addEventListener("click", () => {
            hideAllSections();
            contentPresc.classList.remove("hidden");
        });

        // Clics Sidebar
        document.querySelector(".btn-nav-rdv").addEventListener("click", () => {
            hideAllSections();
            contentAll.classList.remove("hidden");
        });

        document.querySelector(".btn-nav-today").addEventListener("click", () => {
            hideAllSections();
            contentToday.classList.remove("hidden");
        });

        document.querySelector(".btn-nav-dossiers").addEventListener("click", () => {
            hideAllSections();
            contentDossiers.classList.remove("hidden");
        });

        document.querySelector(".btn-nav-prescriptions").addEventListener("click", () => {
            hideAllSections();
            contentPresc.classList.remove("hidden");
        });

        // Afficher/masquer les champs de proposition de créneau
        function handleStatusChange(id) {
            const sel = document.getElementById('status_sel_' + id);
            const box = document.getElementById('proposal_box_' + id);
            if (sel && box) {
                if (sel.value === 'proposer_creneau' || sel.value === 'propose') {
                    box.style.display = 'flex';
                } else {
                    box.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
