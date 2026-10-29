<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Protection de session : redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../login/login.php'); 
    exit();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['full_name'] ?? 'Patient';

// Récupération des notifications du patient
$patientNotifications = [];
$unreadCount = 0;
try {
    $stmtNotif = $pdo->prepare('
        SELECT n.*, r.date_rendez_vous, r.heure_rendez_vous, r.statut AS rdv_statut, u_med.full_name AS medecin_nom
        FROM notification n
        LEFT JOIN rendez_vous r ON n.rendez_vous_id = r.id
        LEFT JOIN "user" u_med ON r.doctor_id = u_med.id
        WHERE n.user_id = :uid
        ORDER BY n.created_at DESC
        LIMIT 30
    ');
    $stmtNotif->execute([':uid' => $userId]);
    $patientNotifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

    foreach ($patientNotifications as $notif) {
        if (empty($notif['is_read'])) {
            $unreadCount++;
        }
    }
} catch (PDOException $e) {
    error_log("Erreur chargement notifications : " . $e->getMessage());
}

// Récupération des rendez-vous du patient
$patientAppointments = [];
try {
    $stmtRdv = $pdo->prepare('
        SELECT r.*, u_med.full_name AS medecin_nom, u_med.specialite AS medecin_specialite
        FROM rendez_vous r
        LEFT JOIN "user" u_med ON r.doctor_id = u_med.id
        WHERE r.user_id = :uid
        ORDER BY r.date_rendez_vous DESC, r.id DESC
    ');
    $stmtRdv->execute([':uid' => $userId]);
    $patientAppointments = $stmtRdv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur chargement rendez-vous : " . $e->getMessage());
}

// Récupération de la liste des médecins disponibles
$doctorsList = [];
try {
    $stmtDocs = $pdo->query('
        SELECT id, full_name, specialite 
        FROM "user" 
        WHERE role_id = (SELECT id FROM role WHERE name = \'doctor\') AND status = \'actif\' 
        ORDER BY full_name ASC
    ');
    $doctorsList = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur chargement liste medecins : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClinicManager — Espace Patient & Soins Médicaux</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { 
            font-family: 'DM Sans', sans-serif; 
            background-color: #f8fafc;
            color: #334155;
        }
        h1, h2, h3, h4, .font-heading { 
            font-family: 'Manrope', sans-serif; 
        }
        .clinic-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .clinic-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.08), 0 8px 10px -6px rgba(37, 99, 235, 0.04);
        }
        .hero-pattern {
            background-image: radial-gradient(#2563eb 0.75px, transparent 0.75px), radial-gradient(#2563eb 0.75px, #f8fafc 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            opacity: 0.04;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- Bannière de Message d'alerte / Confirmation -->
    <div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-lg px-4 pointer-events-auto">
        <?php if (isset($_SESSION['message'])): 
            $message = $_SESSION['message'];
            $isSuccess = ($_SESSION['message_type'] ?? '') === 'success';
        ?>
            <div class="p-4 rounded-2xl shadow-xl border flex items-center justify-between gap-3 text-sm font-semibold transition-all duration-300 <?= $isSuccess ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-xl flex items-center justify-center shrink-0 <?= $isSuccess ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' ?>">
                        <i class="bi <?= $isSuccess ? 'bi-check-lg' : 'bi-exclamation-triangle' ?> text-lg"></i>
                    </div>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
    </div>

    <!-- Barre de Navigation Supérieure Moderne -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Logo ClinicManager -->
                <a href="#" class="flex items-center gap-3 group">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-700 to-blue-500 text-white shadow-md shadow-blue-500/25 group-hover:scale-105 transition-transform duration-200">
                        <img class="h-6 w-6 brightness-0 invert" src="../../assets/images/logo.svg" alt="ClinicManager Logo">
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-tight text-slate-900 group-hover:text-blue-600 transition-colors">
                            Clinic<span class="text-blue-600">Manager</span>
                        </span>
                        <span class="hidden sm:block text-[11px] font-semibold text-slate-400 tracking-wider uppercase">
                            Centre Hospitalier
                        </span>
                    </div>
                </a>

                <!-- Liens de navigation ancrés -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-semibold text-slate-600">
                    <a href="#apropos" class="px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition-all">À propos</a>
                    <a href="#services" class="px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition-all">Nos Services</a>
                    <a href="#equipe" class="px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition-all">Corps Médical</a>
                    <a href="#mes_rendez_vous" class="px-3.5 py-2 rounded-xl text-blue-700 bg-blue-50/70 hover:bg-blue-100 transition-all flex items-center gap-1.5 font-bold">
                        <i class="bi bi-clock-history"></i> Mes Rendez-vous
                    </a>
                    <a href="#rendez_vous" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition-all flex items-center gap-1.5 shadow-md shadow-blue-500/20">
                        <i class="bi bi-calendar2-plus"></i> Prendre RDV
                    </a>
                </nav>

                <!-- Zone Utilisateur & Notifications & Menu Déconnexion -->
                <div class="flex items-center gap-2 sm:gap-3">

                    <!-- Cloche de Notifications Patient -->
                    <div class="relative">
                        <button type="button" 
                                id="notifBellBtn" 
                                aria-label="Notifications" 
                                class="relative p-2.5 rounded-2xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="bi bi-bell-fill text-lg"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span id="notifBadge" class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[11px] font-extrabold text-white shadow-md shadow-rose-600/30 animate-pulse">
                                    <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <!-- Panneau Déroulant des Notifications -->
                        <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-white border border-slate-200/90 rounded-3xl shadow-2xl z-50 overflow-hidden transition-all duration-200">
                            
                            <!-- En-tête du panneau -->
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-extrabold text-slate-900">Notifications</span>
                                    <?php if ($unreadCount > 0): ?>
                                        <span id="unreadCountBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                            <?= $unreadCount ?> non <?= $unreadCount > 1 ? 'lues' : 'lue' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($unreadCount > 0): ?>
                                    <form action="mark_notification.php" method="POST" class="inline" id="markAllForm">
                                        <input type="hidden" name="mark_all" value="1">
                                        <button type="button" onclick="markAllNotificationsAsRead()" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                                            Tout marquer comme lu
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Corps : liste défilable des notifications -->
                            <div id="notifListContainer" class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                <?php if (empty($patientNotifications)): ?>
                                    <div class="p-8 text-center text-slate-400">
                                        <i class="bi bi-bell-slash text-3xl mb-2 block"></i>
                                        <p class="text-xs font-semibold">Aucune notification pour le moment.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($patientNotifications as $notif): 
                                        $isUnread = empty($notif['is_read']);
                                        $type = $notif['type'] ?? 'info';
                                        $badgeBg = 'bg-blue-50 text-blue-600';
                                        $iconName = 'bi-info-circle-fill';
                                        if ($type === 'success') {
                                            $badgeBg = 'bg-emerald-50 text-emerald-600';
                                            $iconName = 'bi-check-circle-fill';
                                        } elseif ($type === 'danger') {
                                            $badgeBg = 'bg-rose-50 text-rose-600';
                                            $iconName = 'bi-x-circle-fill';
                                        } elseif ($type === 'warning') {
                                            $badgeBg = 'bg-amber-50 text-amber-600';
                                            $iconName = 'bi-exclamation-triangle-fill';
                                        }
                                    ?>
                                        <div class="p-4 hover:bg-slate-50 transition-colors <?= $isUnread ? 'bg-blue-50/40 notif-unread' : '' ?>" id="notif_card_<?= $notif['id'] ?>">
                                            <div class="flex items-start gap-3">
                                                <div class="h-8 w-8 rounded-xl flex items-center justify-center shrink-0 <?= $badgeBg ?>">
                                                    <i class="bi <?= $iconName ?> text-sm"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <h4 class="text-xs font-bold text-slate-900 truncate <?= $isUnread ? 'text-blue-950 font-extrabold' : '' ?>">
                                                            <?= htmlspecialchars($notif['titre'], ENT_QUOTES, 'UTF-8') ?>
                                                        </h4>
                                                        <span class="text-[10px] text-slate-400 shrink-0">
                                                            <?= date('d/m H:i', strtotime($notif['created_at'])) ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-600 mt-1 leading-snug">
                                                        <?= nl2br(htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8')) ?>
                                                    </p>

                                                    <div class="mt-2.5 flex items-center justify-between pt-1">
                                                        <?php if (!empty($notif['rendez_vous_id'])): ?>
                                                            <a href="#rdv_<?= $notif['rendez_vous_id'] ?>" 
                                                               onclick="navigateToAppointment(<?= $notif['rendez_vous_id'] ?>, <?= $notif['id'] ?>, <?= $isUnread ? 'true' : 'false' ?>)" 
                                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                                                <span>Voir le rendez-vous</span>
                                                                <i class="bi bi-arrow-right text-[10px]"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span></span>
                                                        <?php endif; ?>

                                                        <?php if ($isUnread): ?>
                                                            <button type="button" 
                                                                    onclick="markSingleAsRead(<?= $notif['id'] ?>)" 
                                                                    id="btn_read_<?= $notif['id'] ?>"
                                                                    class="text-[11px] font-semibold text-slate-400 hover:text-blue-600 transition flex items-center gap-1">
                                                                <i class="bi bi-check2"></i> Marquer lu
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Pied du panneau de notifications -->
                            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 text-center">
                                <a href="#mes_rendez_vous" onclick="document.getElementById('notifDropdown').classList.add('hidden')" class="text-xs font-bold text-slate-700 hover:text-blue-600 transition flex items-center justify-center gap-1">
                                    <span>Accéder au suivi de mes rendez-vous</span>
                                    <i class="bi bi-chevron-right text-[10px]"></i>
                                </a>
                            </div>

                        </div>
                    </div>

                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-xs font-bold text-slate-900 truncate max-w-[150px]"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-[11px] font-semibold text-emerald-600 flex items-center justify-end gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Espace Patient
                        </span>
                    </div>

                    <div class="relative">
                        <button type="button" aria-label="Menu profil" class="Profil flex items-center gap-2 p-1 rounded-full ring-2 ring-slate-200 hover:ring-blue-500 transition-all focus:outline-none">
                            <img src="../../assets/images/team-06.png" class="w-10 h-10 rounded-full object-cover shadow-inner" alt="Avatar patient">
                        </button>

                        <!-- Menu déroulant -->
                        <div class="menu absolute right-0 top-14 hidden w-48 p-2 bg-white border border-slate-200/90 rounded-2xl shadow-xl z-50 transition-all">
                            <div class="px-3 py-2 border-b border-slate-100 sm:hidden">
                                <p class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[10px] text-slate-400">Compte Patient</p>
                            </div>
                            <a href="#mes_rendez_vous" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                <i class="bi bi-calendar2-check text-blue-600"></i> Mes Rendez-vous
                            </a>
                            <form action="" method="POST" class="pt-1">
                                <button type="submit" name="logout" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="bi bi-box-arrow-right text-base"></i> Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- SECTION 1 : HERO BANNER - Nouveau Design Médical Lumineux & Moderne -->
    <section class="relative bg-white pt-12 pb-20 overflow-hidden border-b border-slate-100">
        <!-- Fond graphique avec motifs légers -->
        <div class="absolute inset-0 hero-pattern pointer-events-none"></div>
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-100/50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 -left-20 w-80 h-80 bg-sky-100/40 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Colonne Gauche : Texte & Accroche -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100/80 text-blue-700 text-xs font-bold tracking-wide">
                        <span class="flex h-2 w-2 rounded-full bg-blue-600"></span>
                        CLINIQUE MÉDICALE PLURIDISCIPLINAIRE
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                        Une équipe à votre écoute pour <span class="text-blue-600 underline decoration-blue-200 decoration-wavy decoration-2">vous soigner</span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Notre clinique offre des soins de qualité dans un cadre moderne et accueillant, avec une équipe dédiée à votre bien-être et une large gamme de services médicaux personnalisés.
                    </p>

                    <div class="pt-3 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="#rendez_vous" class="w-full sm:w-auto px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/35 transition-all text-sm flex items-center justify-center gap-2.5">
                            <i class="bi bi-calendar-check text-base"></i> Prendre rendez-vous
                        </a>
                        <a href="#services" class="w-full sm:w-auto px-7 py-4 bg-slate-100 hover:bg-slate-200/80 text-slate-700 font-bold rounded-2xl transition-all text-sm flex items-center justify-center gap-2">
                            <span>Découvrir nos soins</span> <i class="bi bi-arrow-right text-xs"></i>
                        </a>
                    </div>

                    <!-- Puces de réassurance médicale -->
                    <div class="pt-6 border-t border-slate-100 grid grid-cols-3 gap-4 max-w-lg mx-auto lg:mx-0 text-left">
                        <div>
                            <p class="text-2xl font-extrabold text-slate-900">24/7</p>
                            <p class="text-xs font-medium text-slate-500">Soins continus</p>
                        </div>
                        <div>
                            <p class="text-2xl font-extrabold text-blue-600">100%</p>
                            <p class="text-xs font-medium text-slate-500">Praticiens diplômés</p>
                        </div>
                        <div>
                            <p class="text-2xl font-extrabold text-slate-900">0 min</p>
                            <p class="text-xs font-medium text-slate-500">Attente optimisée</p>
                        </div>
                    </div>
                </div>

                <!-- Colonne Droite : Image Docteur d'origine dans un cadre élégant -->
                <div class="lg:col-span-5 relative flex justify-center">
                    <div class="relative w-full max-w-md">
                        <!-- Arrière-plan graphique bleu doux -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-blue-600 to-sky-400 rounded-[36px] -rotate-3 scale-95 opacity-20"></div>
                        <div class="relative bg-gradient-to-b from-blue-50/80 to-white rounded-[32px] p-6 border border-blue-100 shadow-xl overflow-hidden flex justify-center">
                            
                            <!-- Image originale préservée : docteur.png -->
                            <img class="max-w-xs sm:max-w-sm w-full object-contain drop-shadow-xl relative z-10" 
                                 src="../../assets/images/docteur.png" 
                                 alt="Docteur" 
                                 data-aos="fade-up" 
                                 data-aos-duration="1200">

                            <!-- Badge flottant informatif -->
                            <div class="absolute bottom-6 left-6 right-6 glass-panel rounded-2xl p-4 border border-white/80 shadow-lg flex items-center gap-3.5 z-20">
                                <div class="h-10 w-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-blue-500/30">
                                    <i class="bi bi-shield-check text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold text-slate-900 truncate">Consultations certifiées</p>
                                    <p class="text-[11px] text-slate-500 truncate">Accueil bienveillant & suivi personnalisé</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION 2 : NOS SERVICES - Dominante Blanche & Cartes Pro -->
    <section class="py-24 bg-slate-50/70 border-b border-slate-200/60" id="services">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="max-w-3xl mx-auto text-center mb-16 space-y-4" data-aos="fade-up">
                <span class="inline-block px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">
                    Pôles d'excellence
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Une clinique à la pointe pour vous proposer les meilleurs soins
                </h2>
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                    Équipée des dernières technologies médicales, notre clinique s’engage à vous offrir des soins de qualité optimale. Notre priorité est votre bien-être, dans un environnement moderne, sécurisé et chaleureux.
                </p>
            </div>

            <!-- Grille des 3 services avec les icônes SVG d'origine préservées -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Service 1 : Médecins spécialistes -->
                <div class="clinic-card bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs relative flex flex-col justify-between group" data-aos="fade-up" data-aos-delay="100">
                    <div>
                        <div class="h-16 w-16 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <!-- Image originale préservée : icone-docteur.svg -->
                            <img class="h-9 w-9 object-contain" src="../../assets/images/icone-docteur.svg" alt="Médecins spécialistes">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-blue-600 transition-colors">
                            Médecins spécialistes
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Des médecins spécialistes expérimentés, dédiés à votre santé, offrant des diagnostics précis et des traitements adaptés.
                        </p>
                    </div>
                    <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-blue-600">
                        <span>Pôle Médical</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>

                <!-- Service 2 : Soins d'urgence -->
                <div class="clinic-card bg-white p-8 rounded-3xl border border-blue-200/90 shadow-sm relative flex flex-col justify-between group" data-aos="fade-up" data-aos-delay="200">
                    <div>
                        <div class="h-16 w-16 rounded-2xl bg-blue-600 text-white flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-md shadow-blue-500/20">
                            <!-- Image originale préservée : icone-urgence.svg -->
                            <img class="h-9 w-9 object-contain brightness-0 invert" src="../../assets/images/icone-urgence.svg" alt="Soins d'urgence">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-blue-600 transition-colors">
                            Soins d'urgence
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Des soins d'urgence rapides et efficaces, assurés par une équipe médicale qualifiée dans un environnement sécurisé.
                        </p>
                    </div>
                    <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-blue-600">
                        <span>Intervention Rapide</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>

                <!-- Service 3 : Disponibilité 24/7 -->
                <div class="clinic-card bg-white p-8 rounded-3xl border border-slate-200/80 shadow-xs relative flex flex-col justify-between group" data-aos="fade-up" data-aos-delay="300">
                    <div>
                        <div class="h-16 w-16 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <!-- Image originale préservée : icone-disponibilite.svg -->
                            <img class="h-9 w-9 object-contain" src="../../assets/images/icone-disponibilite.svg" alt="Disponibilité 24/7">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-blue-600 transition-colors">
                            Disponibilité 24/7
                        </h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Un service accessible 24h/24 et 7j/7 pour assurer votre santé en toute heure avec un accompagnement personnalisé.
                        </p>
                    </div>
                    <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-blue-600">
                        <span>Permanence des soins</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION 3 : PRÉSENTATION / À PROPOS - Image originale accueil-clinique.jpg -->
    <section class="py-24 bg-white" id="apropos">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Colonne Gauche : Image originale avec cadre design -->
                <div class="lg:col-span-6 relative order-2 lg:order-1" data-aos="fade-right">
                    <div class="relative rounded-3xl overflow-hidden border border-slate-200/80 shadow-2xl">
                        <!-- Image originale préservée : accueil-clinique.jpg -->
                        <img class="w-full h-[420px] object-cover hover:scale-105 transition-transform duration-500" 
                             src="../../assets/images/accueil-clinique.jpg" 
                             alt="Présentation de la clinique">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                        
                        <div class="absolute bottom-6 left-6 right-6 text-white">
                            <span class="inline-block px-3 py-1 bg-blue-600 text-[11px] font-bold rounded-lg uppercase tracking-wider mb-2">
                                Environnement Médical
                            </span>
                            <p class="text-lg font-bold">Un cadre pensé pour votre sérénité</p>
                        </div>
                    </div>
                </div>

                <!-- Colonne Droite : Texte et points forts -->
                <div class="lg:col-span-6 order-1 lg:order-2 space-y-6" data-aos="fade-left">
                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">
                        Qualité d'accueil
                    </span>

                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Votre confort commence dès votre arrivée
                    </h2>

                    <p class="text-base text-slate-600 leading-relaxed">
                        Notre clinique vous garantit un accueil professionnel et bienveillant. Notre équipe dévouée est à votre écoute pour vous offrir une expérience sereine et personnalisée dès votre arrivée.
                    </p>

                    <!-- Liste des atouts avec icônes de contrôle -->
                    <div class="space-y-3.5 pt-2">
                        <div class="flex items-center gap-3">
                            <div class="h-7 w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-check2 text-base font-bold"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-800">Espaces d'attente calmes, lumineux et climatisés</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-7 w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-check2 text-base font-bold"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-800">Prise en charge administrative fluide et numérisée</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-7 w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-check2 text-base font-bold"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-800">Personnel d'orientation dédié à vos formalités</span>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a href="#rendez_vous" class="inline-flex items-center gap-2 px-7 py-3.5 text-sm font-bold text-white bg-slate-900 hover:bg-blue-600 rounded-xl transition-all shadow-md">
                            <span>Prendre rendez-vous en ligne</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION 4 : ÉQUIPE MÉDICALE - Slider / Carrousel Moderne et Professionnel -->
    <section class="py-24 bg-slate-50/70 border-y border-slate-200/60 overflow-hidden" id="equipe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- En-tête de section avec Titre et Contrôles de navigation -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-12 gap-6" data-aos="fade-up">
                <div class="space-y-3 max-w-2xl">
                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">
                        Corps Médical
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Une équipe hautement qualifiée
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        Découvrez nos spécialistes dévoués à votre santé et votre guérison au quotidien.
                    </p>
                </div>

                <!-- Flèches de navigation du Slider -->
                <div class="flex items-center gap-3 self-start md:self-end">
                    <button type="button" 
                            id="sliderPrevBtn" 
                            aria-label="Médecin précédent" 
                            class="h-12 w-12 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 flex items-center justify-center text-lg shadow-sm hover:shadow-md transition-all duration-200 active:scale-95 focus:outline-none">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" 
                            id="sliderNextBtn" 
                            aria-label="Médecin suivant" 
                            class="h-12 w-12 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 flex items-center justify-center text-lg shadow-sm hover:shadow-md transition-all duration-200 active:scale-95 focus:outline-none">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Conteneur du Slider (Viewport) -->
            <div id="doctorSliderViewport" class="overflow-hidden relative -mx-4 px-4 py-2 select-none" data-aos="fade-up" data-aos-delay="100">
                
                <!-- Piste coulissante (Track) -->
                <div id="doctorSliderTrack" class="flex transition-transform duration-500 ease-out will-change-transform">
                    
                    <!-- Slide 1 : Charles KHUTE (team-01.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-01.png" 
                                     alt="Charles KHUTE"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Chirurgien
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Charles KHUTE
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Interventions & Consultations préopératoires</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 : Eva TEXAMINET (team-02.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-02.png" 
                                     alt="Eva TEXAMINET"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Médecin généraliste
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Eva TEXAMINET
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Suivi médical global, bilan & prévention</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3 : Dan THYSTE (team-03.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-03.png" 
                                     alt="Dan THYSTE"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Chef de service
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Dan THYSTE
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Coordination des soins & consultations expertes</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4 : Joss KULTH (team-04.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-04.png" 
                                     alt="Joss KULTH"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Médecin urgentiste
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Joss KULTH
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Soins intensifs & prise en charge rapide</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 5 : Emma TAUME (team-05.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-05.png" 
                                     alt="Emma TAUME"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Pédiatre
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Emma TAUME
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Santé de l'enfant, développement & vaccins</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 6 : Ray ANYMASSION (team-06.png) -->
                    <div class="slider-item shrink-0 px-3 w-full sm:w-1/2 lg:w-1/3">
                        <div class="clinic-card bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-full group hover:border-blue-300 transition-all duration-300">
                            <div class="relative bg-gradient-to-b from-slate-100 to-blue-50/50 overflow-hidden h-72 flex items-end justify-center">
                                <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                                     src="../../assets/images/team-06.png" 
                                     alt="Ray ANYMASSION"
                                     draggable="false">
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full border border-slate-100 shadow-2xs flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Disponible
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between text-center">
                                <div>
                                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
                                        Anesthésiste
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                        Ray ANYMASSION
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">Anesthésie générale, locale & prise en charge de la douleur</p>
                                </div>
                                <div class="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between">
                                    <a href="#rendez_vous" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 transition">
                                        Prendre RDV <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                    <div class="flex gap-3 text-slate-400">
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                                        <a href="#" class="hover:text-blue-600 transition" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Indicateurs de Pagination (Dots) -->
            <div id="doctorSliderDots" class="flex items-center justify-center gap-2.5 mt-10"></div>

        </div>
    </section>

    <!-- SECTION : MES RENDEZ-VOUS & STATUTS EN DIRECT -->
    <section class="py-20 bg-slate-50/80 border-b border-slate-200/70 relative" id="mes_rendez_vous">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100/80 text-blue-700 text-xs font-bold tracking-wide mb-3">
                        <i class="bi bi-calendar2-check"></i>
                        ESPACE CONSULTATIONS & SUIVI PATIENT
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Mes Rendez-vous & Statuts en direct
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2 max-w-2xl">
                        Suivez en temps réel la validation, les confirmations, reports ou motifs d'annulation communiqués par l'équipe médicale.
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="#rendez_vous" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-2xl shadow-md shadow-blue-500/25 transition-all flex items-center gap-2">
                        <i class="bi bi-plus-circle"></i> Nouveau rendez-vous
                    </a>
                </div>
            </div>

            <!-- Résumé rapide des statuts -->
            <?php
                $countTotal = count($patientAppointments);
                $countConfirme = 0;
                $countEnAttente = 0;
                $countAutre = 0;
                foreach ($patientAppointments as $a) {
                    $st = $a['statut'] ?? 'en_attente';
                    if (in_array($st, ['confirme', 'disponible'])) $countConfirme++;
                    elseif ($st === 'en_attente') $countEnAttente++;
                    else $countAutre++;
                }
            ?>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Demandes</p>
                    <p class="text-2xl font-black text-slate-900 mt-1"><?= $countTotal ?></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-2xs">
                    <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Confirmés</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1"><?= $countConfirme ?></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-2xs">
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">En attente</p>
                    <p class="text-2xl font-black text-amber-700 mt-1"><?= $countEnAttente ?></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-blue-100 shadow-2xs">
                    <p class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Modifiés / Autres</p>
                    <p class="text-2xl font-black text-blue-700 mt-1"><?= $countAutre ?></p>
                </div>
            </div>

            <!-- Liste des cartes de rendez-vous -->
            <?php if (empty($patientAppointments)): ?>
                <div class="bg-white rounded-3xl border border-slate-200/90 p-12 text-center max-w-xl mx-auto shadow-sm">
                    <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Aucun rendez-vous pour le moment</h3>
                    <p class="text-slate-500 text-xs sm:text-sm mt-1 max-w-md mx-auto">
                        Vous n'avez pas encore planifié de consultation. Remplissez simplement le formulaire ci-dessous pour choisir votre date.
                    </p>
                    <a href="#rendez_vous" class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-2xl shadow-lg shadow-blue-500/25 transition">
                        <i class="bi bi-calendar-plus"></i> Prendre mon premier rendez-vous
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($patientAppointments as $rdv): 
                        $status = $rdv['statut'] ?? 'en_attente';
                        $dateRdv = !empty($rdv['date_rendez_vous']) ? date('d/m/Y', strtotime($rdv['date_rendez_vous'])) : date('d/m/Y', strtotime($rdv['date']));
                        $heureRdv = !empty($rdv['heure_rendez_vous']) ? substr($rdv['heure_rendez_vous'], 0, 5) : '09:00';
                        $medecinNom = !empty($rdv['medecin_nom']) ? $rdv['medecin_nom'] : 'Dr. Cabinet Médical';
                        $medecinSpecialite = !empty($rdv['medecin_specialite']) ? $rdv['medecin_specialite'] : 'Médecine Générale';
                    ?>
                        <div id="rdv_<?= $rdv['id'] ?>" class="rdv-card bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-lg transition-all duration-300 p-6 flex flex-col justify-between relative overflow-hidden">
                            
                            <div>
                                <!-- En-tête de la carte : Statut & ID -->
                                <div class="flex items-center justify-between gap-2 mb-4">
                                    <span class="text-[11px] font-extrabold text-slate-400">RDV #<?= $rdv['id'] ?></span>
                                    
                                    <?php if ($status === 'confirme'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="bi bi-check-circle-fill text-emerald-500"></i> Confirmé par le médecin
                                        </span>
                                    <?php elseif ($status === 'termine'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <i class="bi bi-check2-all text-slate-500"></i> Consultation terminée
                                        </span>
                                    <?php elseif ($status === 'refuse'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i class="bi bi-x-circle text-rose-500"></i> Refusé
                                        </span>
                                    <?php elseif ($status === 'annule'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <i class="bi bi-slash-circle text-slate-500"></i> Annulé
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                            <i class="bi bi-hourglass-split text-amber-500"></i> En attente d'acceptation
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Date & Heure du RDV -->
                                <div class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100/60 flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/30">
                                            <i class="bi bi-calendar-event text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 font-medium">Date de consultation</p>
                                            <p class="text-sm font-extrabold text-slate-900"><?= $dateRdv ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-slate-500 font-medium">Heure</p>
                                        <p class="text-sm font-extrabold text-blue-600"><?= $heureRdv ?></p>
                                    </div>
                                </div>

                                <!-- Praticien -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <i class="bi bi-person-fill text-slate-400 text-xs"></i>
                                        <span class="text-xs font-bold text-slate-900"><?= htmlspecialchars($medecinNom, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500 text-xs">
                                        <i class="bi bi-bookmark-fill text-slate-400 text-[10px]"></i>
                                        <span><?= htmlspecialchars($medecinSpecialite, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500 text-xs">
                                        <i class="bi bi-card-text text-slate-400 text-xs"></i>
                                        <span>CNI : <strong class="text-slate-700"><?= htmlspecialchars($rdv['cni'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                    </div>
                                </div>

                                <!-- Message de statut clair pour le patient (aucun bouton d'action requis) -->
                                <?php if ($status === 'confirme'): ?>
                                    <div class="mt-4 p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-2xl text-xs text-emerald-900 flex items-start gap-2.5">
                                        <i class="bi bi-check-circle-fill text-emerald-600 text-base shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="font-extrabold text-slate-900">Consultation confirmée</p>
                                            <p class="text-slate-600 mt-0.5 leading-relaxed">Votre demande a été acceptée par le Dr <strong><?= htmlspecialchars($medecinNom, ENT_QUOTES, 'UTF-8') ?></strong>. Votre rendez-vous est définitivement programmé le <strong><?= $dateRdv ?></strong> à <strong><?= $heureRdv ?></strong>.</p>
                                        </div>
                                    </div>
                                <?php elseif ($status === 'en_attente'): ?>
                                    <div class="mt-4 p-3.5 bg-amber-50/70 border border-amber-200 rounded-2xl text-xs text-amber-900 flex items-start gap-2.5">
                                        <i class="bi bi-hourglass-split text-amber-600 text-base shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="font-extrabold text-slate-900">Demande en attente d'acceptation</p>
                                            <p class="text-slate-600 mt-0.5 leading-relaxed">Le Dr <strong><?= htmlspecialchars($medecinNom, ENT_QUOTES, 'UTF-8') ?></strong> examine votre demande et va fixer la date et l'heure exactes de votre consultation.</p>
                                        </div>
                                    </div>
                                <?php elseif ($status === 'refuse'): ?>
                                    <div class="mt-4 p-3.5 bg-rose-50/70 border border-rose-200 rounded-2xl text-xs text-rose-900 flex items-start gap-2.5">
                                        <i class="bi bi-x-circle-fill text-rose-600 text-base shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="font-extrabold text-slate-900">Demande refusée</p>
                                            <p class="text-slate-600 mt-0.5 leading-relaxed">Le médecin ne peut pas assurer ce rendez-vous. Vous pouvez renouveler une demande auprès d'un autre praticien.</p>
                                        </div>
                                    </div>
                                <?php elseif (!empty($rdv['note_medecin'])): ?>
                                    <div class="mt-4 p-3.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-xs">
                                        <p class="text-[11px] font-bold text-slate-900 uppercase">Note du médecin</p>
                                        <p class="text-slate-600 mt-0.5"><?= nl2br(htmlspecialchars($rdv['note_medecin'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <!-- Bas de carte : Actions possibles -->
                            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="text-[11px] text-slate-400">Demande le <?= date('d/m/Y', strtotime($rdv['date'] ?? $rdv['date_rendez_vous'])) ?></span>
                                
                                <?php if ($status === 'en_attente'): ?>
                                    <form id="cancel_form_<?= $rdv['id'] ?>" action="cancel_rdv.php" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                        <button type="button" onclick="confirmCancelRdv(<?= $rdv['id'] ?>)" class="text-rose-600 hover:text-rose-700 font-bold inline-flex items-center gap-1 transition">
                                            <i class="bi bi-x-circle"></i> Annuler
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- SECTION 5 : FORMULAIRE DE RENDEZ-VOUS - Design Blanc & Bleu Professionnel -->
    <section class="py-24 bg-white relative overflow-hidden" id="rendez_vous">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Colonne Gauche : Recommandations pour le patient -->
                <div class="lg:col-span-5 space-y-6">
                    <span class="inline-block px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">
                        Consultation Médicale
                    </span>

                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Prendre rendez-vous en ligne
                    </h2>

                    <p class="text-slate-600 text-base leading-relaxed">
                        Remplissez le formulaire rapide ci-contre pour planifier votre consultation avec notre équipe médicale en quelques clics.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 flex items-start gap-3.5">
                            <div class="h-8 w-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">Confirmation rapide</p>
                                <p class="text-xs text-slate-500 mt-0.5">Votre créneau est transmis directement à notre secrétariat médical.</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-3.5">
                            <div class="h-8 w-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-person-vcard"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">À apporter le jour J</p>
                                <p class="text-xs text-slate-500 mt-0.5">Votre carte d'identité (CNI) et vos éventuels résultats d'analyses.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne Droite : Formulaire d'origine (cni, date, terms) avec nouveau design -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 sm:p-10 relative">
                        <div class="absolute -top-3 right-8 bg-blue-600 text-white text-[11px] font-bold px-3 py-1 rounded-full shadow-sm">
                            Formulaire Sécurisé
                        </div>

                        <!-- Formulaire d'origine : action="rendezVous.php" method="POST" -->
                        <form action="rendezVous.php" method="POST" class="space-y-6">
                            
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Demande de Rendez-vous</h3>
                                <p class="text-xs text-slate-500 mt-1">Veuillez renseigner vos coordonnées d'identification.</p>
                            </div>

                                                        <!-- Choix du Médecin -->
                            <div>
                                <label for="doctor_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Médecin traitant *
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <select id="doctor_id" 
                                            name="doctor_id" 
                                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600 transition" 
                                            required>
                                        <?php if (empty($doctorsList)): ?>
                                            <option value="">Aucun médecin disponible pour le moment</option>
                                        <?php else: ?>
                                            <?php foreach ($doctorsList as $doc): ?>
                                                <option value="<?= (int)$doc['id'] ?>">Dr. <?= htmlspecialchars($doc['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($doc['specialite'] ?: 'Médecine Générale', ENT_QUOTES, 'UTF-8') ?>)</option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Champ CNI d'origine -->
                            <div>
                                <label for="cni" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Numéro CNI *
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <input id="cni" 
                                           name="cni" 
                                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600 transition" 
                                           type="text" 
                                           placeholder="Entrez votre numéro CNI" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Champ Date d'origine -->
                                <div>
                                    <label for="date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                        Date souhaitée *
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                            <i class="bi bi-calendar3"></i>
                                        </div>
                                        <input id="date" 
                                               name="date" 
                                               min="<?= date('Y-m-d') ?>"
                                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600 transition" 
                                               type="date" 
                                               required>
                                    </div>
                                </div>

                                <!-- Choix du créneau horaire (1 heure de consultation) -->
                                <div>
                                    <label for="heure" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                        Créneau horaire *
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <select id="heure" 
                                                name="heure" 
                                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600 transition"
                                                required>
                                            <option value="08:00">08:00 - 09:00 (Matin)</option>
                                            <option value="09:00" selected>09:00 - 10:00 (Matin)</option>
                                            <option value="10:00">10:00 - 11:00 (Matin)</option>
                                            <option value="11:00">11:00 - 12:00 (Matin)</option>
                                            <option value="14:00">14:00 - 15:00 (Après-midi)</option>
                                            <option value="15:00">15:00 - 16:00 (Après-midi)</option>
                                            <option value="16:00">16:00 - 17:00 (Après-midi)</option>
                                            <option value="17:00">17:00 - 18:00 (Après-midi)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Case à cocher d'origine (terms) -->
                            <label class="flex items-start gap-3 pt-2 cursor-pointer select-none">
                                <input class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                       type="checkbox" 
                                       name="terms" 
                                       value="1" 
                                       required>
                                <span class="text-xs text-slate-500 leading-normal">
                                    En soumettant cette demande, vous acceptez notre politique concernant vos données personnelles et l'enregistrement de votre rendez-vous.
                                </span>
                            </label>

                            <!-- Bouton de soumission d'origine -->
                            <button type="submit" 
                                    class="w-full py-4 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-2xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/35 transition-all flex items-center justify-center gap-2">
                                <i class="bi bi-send-check"></i>
                                <span>Envoyer la demande</span>
                            </button>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Pied de Page (Footer) Épuré & Professionnel -->
    <footer class="bg-slate-900 text-slate-400 pt-16 pb-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-12 border-b border-slate-800">
                
                <!-- Colonne Marque -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold">
                            <img class="h-5 w-5 brightness-0 invert" src="../../assets/images/logo.svg" alt="ClinicManager">
                        </div>
                        <span class="text-xl font-bold text-white">Clinic<span class="text-blue-500">Manager</span></span>
                    </div>
                    <p class="text-xs leading-relaxed max-w-sm text-slate-400">
                        Votre partenaire santé au quotidien. Plateforme moderne de gestion hospitalière, consultations spécialisées et prise de rendez-vous sécurisée.
                    </p>
                </div>

                <!-- Colonne Liens Rapides -->
                <div>
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Accès Rapide</h4>
                    <ul class="space-y-2.5 text-xs">
                        <li><a href="#apropos" class="hover:text-blue-400 transition">À propos de la clinique</a></li>
                        <li><a href="#services" class="hover:text-blue-400 transition">Nos pôles de soins</a></li>
                        <li><a href="#equipe" class="hover:text-blue-400 transition">Médecins spécialistes</a></li>
                        <li><a href="#rendez_vous" class="hover:text-blue-400 transition">Prendre rendez-vous</a></li>
                    </ul>
                </div>

                <!-- Colonne Réseaux & Contact -->
                <div>
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Nous Suivre</h4>
                    <div class="flex gap-3 text-lg text-slate-400">
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-4">Urgences médicales : 24h/24, 7j/7</p>
                </div>

            </div>

            <!-- Bas de page -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> ClinicManager. Tous droits réservés.</p>
                <div class="flex gap-6">
                    <span>Confidentialité des données</span>
                    <span>Système certifié</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bouton Flottant Scroll to Top -->
    <button id="scrollToTopBtn" aria-label="Remonter en haut" class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-12 h-12 bg-blue-600 text-white rounded-2xl shadow-xl shadow-blue-600/30 opacity-0 pointer-events-none hover:bg-blue-700 hover:scale-105 transition-all duration-300">
        <i class="bi bi-arrow-up text-lg"></i>
    </button>

    <!-- Scripts JavaScript -->
    <script>
        AOS.init({
            duration: 800,
            once: true
        });

        // Dropdown menu du profil patient
        const profilBtn = document.querySelector(".Profil");
        const menuDropdown = document.querySelector(".menu");
        
        if (profilBtn && menuDropdown) {
            profilBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                menuDropdown.classList.toggle('hidden');
            });

            document.addEventListener("click", function(e) {
                if (!menuDropdown.contains(e.target) && !profilBtn.contains(e.target)) {
                    menuDropdown.classList.add('hidden');
                }
            });
        }

        // ==========================================
        // GESTION DU SYSTÈME DE NOTIFICATIONS PATIENT
        // ==========================================
        const notifBellBtn = document.getElementById("notifBellBtn");
        const notifDropdown = document.getElementById("notifDropdown");

        if (notifBellBtn && notifDropdown) {
            notifBellBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('hidden');
            });

            document.addEventListener("click", function(e) {
                if (!notifDropdown.contains(e.target) && !notifBellBtn.contains(e.target)) {
                    notifDropdown.classList.add('hidden');
                }
            });
        }

        // Toast SweetAlert2 élégant et discret pour les messages ponctuels importants
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });

        // Marquer une seule notification comme lue via AJAX
        function markSingleAsRead(notifId) {
            fetch('mark_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'notification_id=' + encodeURIComponent(notifId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById('notif_card_' + notifId);
                    if (card) {
                        card.classList.remove('bg-blue-50/40', 'notif-unread');
                    }
                    const btn = document.getElementById('btn_read_' + notifId);
                    if (btn) {
                        btn.remove();
                    }
                    updateBadgeCount();
                }
            })
            .catch(err => console.error("Erreur mise à jour notification:", err));
        }

        // Tout marquer comme lu via AJAX
        function markAllNotificationsAsRead() {
            fetch('mark_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'mark_all=1'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Supprimer les styles non lus
                    document.querySelectorAll('.notif-unread').forEach(el => {
                        el.classList.remove('bg-blue-50/40', 'notif-unread');
                    });
                    document.querySelectorAll('[id^="btn_read_"]').forEach(btn => btn.remove());
                    
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.remove();

                    const unreadBadge = document.getElementById('unreadCountBadge');
                    if (unreadBadge) unreadBadge.remove();

                    const markAllForm = document.getElementById('markAllForm');
                    if (markAllForm) markAllForm.remove();

                    Toast.fire({
                        icon: 'success',
                        title: 'Toutes les notifications sont marquées comme lues'
                    });
                }
            })
            .catch(err => {
                // Fallback soumission formulaire classique si fetch échoue
                const f = document.getElementById('markAllForm');
                if (f) f.submit();
            });
        }

        // Mettre à jour le badge de compteur de notifications
        function updateBadgeCount() {
            const badge = document.getElementById('notifBadge');
            const unreadBadge = document.getElementById('unreadCountBadge');
            const remaining = document.querySelectorAll('.notif-unread').length;

            if (remaining <= 0) {
                if (badge) badge.remove();
                if (unreadBadge) unreadBadge.remove();
                const markAllForm = document.getElementById('markAllForm');
                if (markAllForm) markAllForm.remove();
            } else {
                if (badge) badge.textContent = remaining > 9 ? '9+' : remaining;
                if (unreadBadge) unreadBadge.textContent = remaining + (remaining > 1 ? ' non lues' : ' non lue');
            }
        }

        // Accès direct au rendez-vous depuis une notification avec mise en surbrillance
        function navigateToAppointment(rdvId, notifId, isUnread) {
            if (isUnread) {
                markSingleAsRead(notifId);
            }
            if (notifDropdown) {
                notifDropdown.classList.add('hidden');
            }
            setTimeout(() => {
                highlightAppointment(rdvId);
            }, 100);
        }

        // Surbrillance douce du rendez-vous ciblé
        function highlightAppointment(rdvId) {
            const el = document.getElementById('rdv_' + rdvId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-4', 'ring-blue-500', 'ring-offset-2', 'shadow-2xl');
                setTimeout(() => {
                    el.classList.remove('ring-4', 'ring-blue-500', 'ring-offset-2');
                }, 2800);
            }
        }

        // Confirmation d'annulation de rendez-vous avec SweetAlert2 (ponctuel et professionnel)
        function confirmCancelRdv(rdvId) {
            Swal.fire({
                title: 'Annuler ce rendez-vous ?',
                text: 'Votre demande sera marquée comme annulée auprès du secrétariat médical.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Oui, annuler la demande',
                cancelButtonText: 'Conserver',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
                    cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('cancel_form_' + rdvId);
                    if (form) form.submit();
                }
            });
        }

        // Vérification de l'URL hash au chargement pour surligner le RDV si demandé
        window.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#rdv_')) {
                const id = hash.replace('#rdv_', '');
                setTimeout(() => highlightAppointment(id), 400);
            }
        });

        // Bouton de retour en haut de page (Scroll To Top)
        const scrollToTopBtn = document.getElementById("scrollToTopBtn");

        window.addEventListener("scroll", function() {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.remove("opacity-0", "pointer-events-none");
                scrollToTopBtn.classList.add("opacity-100", "pointer-events-auto");
            } else {
                scrollToTopBtn.classList.remove("opacity-100", "pointer-events-auto");
                scrollToTopBtn.classList.add("opacity-0", "pointer-events-none");
            }
        });

        scrollToTopBtn.addEventListener("click", function() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });

        // ==========================================
        // SLIDER / CARROUSEL DES MÉDECINS
        // ==========================================
        (function() {
            const track = document.getElementById('doctorSliderTrack');
            const viewport = document.getElementById('doctorSliderViewport');
            const prevBtn = document.getElementById('sliderPrevBtn');
            const nextBtn = document.getElementById('sliderNextBtn');
            const dotsContainer = document.getElementById('doctorSliderDots');
            const slides = document.querySelectorAll('.slider-item');

            if (!track || slides.length === 0) return;

            let currentIndex = 0;
            let autoPlayTimer = null;
            const autoPlayDelay = 4500; // 4.5 secondes

            // Détecter le nombre de cartes visibles selon la largeur
            function getVisibleSlides() {
                const width = window.innerWidth;
                if (width >= 1024) return 3; // Desktop
                if (width >= 640) return 2;  // Tablette
                return 1;                    // Mobile
            }

            function getMaxIndex() {
                const visible = getVisibleSlides();
                return Math.max(0, slides.length - visible);
            }

            // Génération dynamique des indicateurs de pagination
            function renderDots() {
                if (!dotsContainer) return;
                dotsContainer.innerHTML = '';
                const maxIdx = getMaxIndex();
                const totalDots = maxIdx + 1;

                if (totalDots <= 1) return;

                for (let i = 0; i < totalDots; i++) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.setAttribute('aria-label', 'Aller au groupe ' + (i + 1));
                    dot.className = (i === currentIndex)
                        ? 'h-2.5 w-8 rounded-full bg-blue-600 transition-all duration-300'
                        : 'h-2.5 w-2.5 rounded-full bg-slate-300 hover:bg-slate-400 transition-all duration-300';
                    
                    dot.addEventListener('click', () => {
                        goToSlide(i);
                        resetAutoPlay();
                    });
                    dotsContainer.appendChild(dot);
                }
            }

            // Met à jour la position du carrousel et l'état des indicateurs
            function updateSlider() {
                const maxIdx = getMaxIndex();
                if (currentIndex > maxIdx) currentIndex = maxIdx;
                if (currentIndex < 0) currentIndex = 0;

                const visible = getVisibleSlides();
                const slidePercent = 100 / visible;
                const offset = -(currentIndex * slidePercent);

                track.style.transform = `translateX(${offset}%)`;

                // Mise à jour des dots
                if (dotsContainer) {
                    const dots = dotsContainer.children;
                    for (let i = 0; i < dots.length; i++) {
                        if (i === currentIndex) {
                            dots[i].className = 'h-2.5 w-8 rounded-full bg-blue-600 transition-all duration-300';
                        } else {
                            dots[i].className = 'h-2.5 w-2.5 rounded-full bg-slate-300 hover:bg-slate-400 transition-all duration-300';
                        }
                    }
                }
            }

            function goToSlide(index) {
                const maxIdx = getMaxIndex();
                if (index > maxIdx) {
                    currentIndex = 0; // Boucle au début
                } else if (index < 0) {
                    currentIndex = maxIdx; // Boucle à la fin
                } else {
                    currentIndex = index;
                }
                updateSlider();
            }

            function nextSlide() {
                goToSlide(currentIndex + 1);
            }

            function prevSlide() {
                goToSlide(currentIndex - 1);
            }

            // Écouteurs des boutons de navigation
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    nextSlide();
                    resetAutoPlay();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prevSlide();
                    resetAutoPlay();
                });
            }

            // Défilement automatique
            function startAutoPlay() {
                stopAutoPlay();
                autoPlayTimer = setInterval(nextSlide, autoPlayDelay);
            }

            function stopAutoPlay() {
                if (autoPlayTimer) {
                    clearInterval(autoPlayTimer);
                    autoPlayTimer = null;
                }
            }

            function resetAutoPlay() {
                stopAutoPlay();
                startAutoPlay();
            }

            // Pause au survol de la souris
            viewport.addEventListener('mouseenter', stopAutoPlay);
            viewport.addEventListener('mouseleave', startAutoPlay);

            // Support du Swipe tactile sur smartphone / tablette
            let touchStartX = 0;
            let touchEndX = 0;

            viewport.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoPlay();
            }, { passive: true });

            viewport.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                const diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                }
                startAutoPlay();
            }, { passive: true });

            // Adaptation au redimensionnement de l'écran
            window.addEventListener('resize', () => {
                renderDots();
                updateSlider();
            });

            // Initialisation
            renderDots();
            updateSlider();
            startAutoPlay();
        })();
    </script>

</body>
</html>
