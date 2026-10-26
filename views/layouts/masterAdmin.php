<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();

    header('Location: ../login/login.php');
    exit();
}

$adminName = $_SESSION['full_name'] ?? 'Administrateur';

$total_users = 0;
$total_users_archive = 0;
$total_users_InsetTODay = 0;

try {
    $sqlCount = $pdo->prepare(
        "SELECT
            COUNT(*) FILTER (WHERE status = 'actif') AS active_users,
            COUNT(*) FILTER (WHERE status = 'archivé') AS archived_users,
            COUNT(*) FILTER (
                WHERE created_at::date = CURRENT_DATE
            ) AS users_created_today
         FROM \"user\""
    );

    $sqlCount->execute();
    $stats = $sqlCount->fetch(PDO::FETCH_ASSOC);

    $total_users = (int) ($stats['active_users'] ?? 0);
    $total_users_archive = (int) ($stats['archived_users'] ?? 0);
    $total_users_InsetTODay = (int) ($stats['users_created_today'] ?? 0);
} catch (PDOException $e) {
    error_log('Erreur statistiques admin : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard Admin', ENT_QUOTES, 'UTF-8') ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        clinic: {
                            50: '#f0f6ff',
                            100: '#e0f0fe',
                            500: '#0ea5e9',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a'
                        }
                    }
                }
            }
        };
    </script>

    <style>
        .stat-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.08), 0 8px 10px -6px rgba(37, 99, 235, 0.08);
        }
        .tab-active {
            border-color: #2563eb !important;
            background: linear-gradient(to bottom right, #ffffff, #f8fafc);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>

<body class="h-full bg-slate-100/60 text-slate-800 antialiased">

    <div class="flex min-h-screen">

        <!-- Navigation latérale -->
        <?php include __DIR__ . '/../include/navAdmin.php'; ?>

        <!-- Contenu principal -->
        <main class="min-w-0 flex-1 flex flex-col">

            <!-- Barre supérieure élégante -->
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur-md shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 sm:px-8">

                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-600">
                                ClinicManager Pro
                            </p>
                        </div>
                        <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                            <?= htmlspecialchars($title ?? 'Tableau de bord', ENT_QUOTES, 'UTF-8') ?>
                        </h1>
                        <p class="text-xs text-slate-500 sm:text-sm">
                            Gestion et suivi de votre espace médical en temps réel
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-slate-900">
                                <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="text-xs font-medium text-slate-500">
                                Administrateur Principal
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 font-bold text-white shadow-md shadow-blue-500/20">
                            <?= htmlspecialchars(
                                mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <form method="POST" action="">
                            <button
                                type="submit"
                                name="logout"
                                value="1"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-2xs transition-all hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden md:inline">Déconnexion</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Zone de travail -->
            <div class="flex-1 space-y-8 p-6 sm:p-8 max-w-7xl w-full mx-auto">

                <!-- Message de notification -->
                <?php if (isset($_GET['updated'])): ?>
                    <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm backdrop-blur">
                        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Les informations de l'utilisateur ont été mises à jour avec succès.
                    </div>
                <?php endif; ?>

                <!-- Cartes statistiques interactives -->
                <section class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">

                    <!-- Actifs -->
                    <button
                        type="button"
                        id="cardActive"
                        class="stat-card tab-active rounded-3xl border border-slate-200/90 bg-white p-6 text-left shadow-xs cursor-pointer relative overflow-hidden group"
                        data-section="active"
                    >
                        <div class="absolute top-0 right-0 h-24 w-24 bg-blue-50 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Utilisateurs Actifs
                                </p>
                                <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900">
                                    <?= $total_users ?>
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Comptes actuellement en ligne / actifs
                                </p>
                            </div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100/80 text-blue-600 shadow-inner">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-6 flex items-center gap-1.5 text-sm font-semibold text-blue-600 group-hover:translate-x-1 transition-transform">
                            <span>Consulter la liste</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>

                    <!-- Archivés -->
                    <button
                        type="button"
                        id="cardArchive"
                        class="stat-card rounded-3xl border border-slate-200/90 bg-white p-6 text-left shadow-xs cursor-pointer relative overflow-hidden group"
                        data-section="archive"
                    >
                        <div class="absolute top-0 right-0 h-24 w-24 bg-amber-50 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Comptes Archivés
                                </p>
                                <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900">
                                    <?= $total_users_archive ?>
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Dossiers conservés en archive
                                </p>
                            </div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100/80 text-amber-600 shadow-inner">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1 14h12l1-14M9 7V4h6v3m-5 4v6m4-6v6"/>
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-6 flex items-center gap-1.5 text-sm font-semibold text-amber-600 group-hover:translate-x-1 transition-transform">
                            <span>Consulter les archives</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>

                    <!-- Inscriptions Aujourd'hui -->
                    <div class="stat-card rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs relative overflow-hidden sm:col-span-2 xl:col-span-1">
                        <div class="absolute top-0 right-0 h-24 w-24 bg-emerald-50 rounded-bl-full -z-0"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Nouveaux Inscrits (Aujourd'hui)
                                </p>
                                <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900">
                                    <?= $total_users_InsetTODay ?>
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Enregistrements de la journée
                                </p>
                            </div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100/80 text-emerald-600 shadow-inner">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-semibold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg w-fit">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Mise à jour automatique en direct
                        </div>
                    </div>

                </section>

                <!-- Conteneurs de listes dynamiques -->
                <div class="space-y-6">
                    <!-- Liste des utilisateurs actifs -->
                    <section id="activeSection" class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs transition-all sm:p-8">
                        <?= $content ?? '' ?>
                    </section>

                    <!-- Liste des utilisateurs archivés -->
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

                // Défilement fluide vers la zone de contenu
                document.querySelector('main').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });
    </script>

</body>
</html>