<?php
// views/layouts/masterAdmin.php - Master Layout Administration ClinicManager
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$adminName = $_SESSION['full_name'] ?? 'Administrateur';

// Statistiques administratives dynamiques et réelles
$total_users = 0;
$total_users_archive = 0;
$total_users_InsetTODay = 0;
$total_patients = 0;
$total_doctors = 0;
$total_appointments = 0;
$total_revenue = 0.00;

try {
    $sqlStats = $pdo->query("
        SELECT
            COUNT(*) FILTER (WHERE status = 'actif') AS active_users,
            COUNT(*) FILTER (WHERE status = 'archivé') AS archived_users,
            COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE) AS users_created_today,
            COUNT(*) FILTER (WHERE role_id = (SELECT id FROM role WHERE name = 'user') AND status = 'actif') AS total_patients,
            COUNT(*) FILTER (WHERE role_id = (SELECT id FROM role WHERE name = 'doctor') AND status = 'actif') AS total_doctors
        FROM \"user\"
    ");
    $stats = $sqlStats->fetch(PDO::FETCH_ASSOC);

    $total_users = (int)($stats['active_users'] ?? 0);
    $total_users_archive = (int)($stats['archived_users'] ?? 0);
    $total_users_InsetTODay = (int)($stats['users_created_today'] ?? 0);
    $total_patients = (int)($stats['total_patients'] ?? 0);
    $total_doctors = (int)($stats['total_doctors'] ?? 0);

    $total_appointments = (int)$pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
    $total_revenue = (float)$pdo->query("SELECT COALESCE(SUM(montant), 0) FROM facture WHERE statut_paiement = 'paye'")->fetchColumn();

} catch (PDOException $e) {
    error_log('Erreur statistiques admin : ' . $e->getMessage());
}

$flashSuccess = $_SESSION['success_message'] ?? '';
$flashError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard Admin | ClinicManager') ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #f8fafc; }
        h1, h2, h3, h4 { font-family: 'Manrope', sans-serif; }
        .stat-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 25px -5px rgba(37, 99, 235, 0.08);
        }
        .tab-active {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>

<body class="h-full bg-slate-100/60 text-slate-800 antialiased">

    <div class="flex min-h-screen">

        <!-- Navigation latérale -->
        <?php include __DIR__ . '/../include/navAdmin.php'; ?>

        <!-- Contenu principal -->
        <main class="min-w-0 flex-1 flex flex-col">

            <!-- Barre supérieure -->
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur-md shadow-2xs">
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 sm:px-8">

                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">
                                ClinicManager Admin
                            </p>
                        </div>
                        <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                            <?= e($title ?? 'Tableau de bord') ?>
                        </h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-bold text-slate-900">
                                <?= e($adminName) ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                Administrateur Principal
                            </p>
                        </div>

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-md shadow-blue-500/20 text-sm">
                            <?= e(mb_strtoupper(mb_substr($adminName, 0, 1))) ?>
                        </div>

                        <a 
                            href="../logout/logout_action.php"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-2xs transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="hidden md:inline">Déconnexion</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- SweetAlert flash messages -->
            <?php if (!empty($flashSuccess)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès',
                            text: <?= json_encode($flashSuccess) ?>,
                            confirmButtonColor: '#2563eb',
                            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                        });
                    });
                </script>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Attention',
                            text: <?= json_encode($flashError) ?>,
                            confirmButtonColor: '#2563eb',
                            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                        });
                    });
                </script>
            <?php endif; ?>

            <!-- Zone de travail -->
            <div class="flex-1 space-y-8 p-6 sm:p-8 max-w-7xl w-full mx-auto">

                <!-- Cartes statistiques interactives -->
                <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    <!-- Utilisateurs Actifs -->
                    <button
                        type="button"
                        id="cardActive"
                        class="stat-card tab-active rounded-3xl border border-slate-200/90 bg-white p-6 text-left shadow-xs cursor-pointer relative overflow-hidden group"
                        data-section="active"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Utilisateurs Actifs
                                </p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900">
                                    <?= $total_users ?>
                                </p>
                                <p class="mt-1 text-xs text-blue-600 font-semibold group-hover:underline">
                                    <?= $total_patients ?> patient(s) · <?= $total_doctors ?> médecin(s)
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 text-xl shadow-inner">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </button>

                    <!-- Utilisateurs Archivés -->
                    <button
                        type="button"
                        id="cardArchive"
                        class="stat-card rounded-3xl border border-slate-200/90 bg-white p-6 text-left shadow-xs cursor-pointer relative overflow-hidden group"
                        data-section="archive"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Comptes Archivés
                                </p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900">
                                    <?= $total_users_archive ?>
                                </p>
                                <p class="mt-1 text-xs text-amber-600 font-semibold group-hover:underline">
                                    Voir les archives &rarr;
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 text-xl shadow-inner">
                                <i class="bi bi-archive"></i>
                            </div>
                        </div>
                    </button>

                    <!-- Total Rendez-vous -->
                    <div class="stat-card rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs relative overflow-hidden">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Total Consultations
                                </p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900">
                                    <?= $total_appointments ?>
                                </p>
                                <p class="mt-1 text-xs text-slate-500 font-medium">
                                    Rendez-vous programmés
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 text-xl shadow-inner">
                                <i class="bi bi-calendar3"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Recettes & Factures Payées -->
                    <div class="stat-card rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs relative overflow-hidden">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Recettes Encaissées
                                </p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-900">
                                    <?= number_format($total_revenue, 0, ',', ' ') ?> <span class="text-sm font-semibold text-slate-400">DH</span>
                                </p>
                                <p class="mt-1 text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Factures réglées
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 text-xl shadow-inner">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>

                </section>

                <!-- Conteneurs de listes dynamiques -->
                <div class="space-y-6">
                    <!-- Section Utilisateurs Actifs -->
                    <section id="activeSection" class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs transition-all sm:p-8">
                        <?= $content ?? '' ?>
                    </section>

                    <!-- Section Utilisateurs Archivés -->
                    <section id="archiveSection" class="hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs transition-all sm:p-8">
                        <?= $userArchif ?? '' ?>
                    </section>
                </div>

            </div>
        </main>
    </div>

    <!-- Script de gestion des sections -->
    <script>
        const activeSection = document.getElementById('activeSection');
        const archiveSection = document.getElementById('archiveSection');
        const cardActive = document.getElementById('cardActive');
        const cardArchive = document.getElementById('cardArchive');

        document.querySelectorAll('[data-section]').forEach((card) => {
            card.addEventListener('click', () => {
                const section = card.dataset.section;

                if (section === 'archive') {
                    activeSection.classList.add('hidden');
                    archiveSection.classList.remove('hidden');
                    cardArchive.classList.add('tab-active');
                    cardActive.classList.remove('tab-active');
                } else {
                    archiveSection.classList.add('hidden');
                    activeSection.classList.remove('hidden');
                    cardActive.classList.add('tab-active');
                    cardArchive.classList.remove('tab-active');
                }
            });
        });
    </script>

</body>
</html>
