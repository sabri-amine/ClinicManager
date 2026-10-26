
<?php
session_start();
require_once('../../db.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Créer un compte | ClinicManager</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f7fb;
        }

        h1, h2, h3 {
            font-family: 'Manrope', sans-serif;
        }

        .register-card {
            box-shadow: 0 24px 80px rgba(24, 50, 90, 0.08);
        }

        .medical-pattern {
            background-image: radial-gradient(
                rgba(255, 255, 255, 0.12) 1px,
                transparent 1px
            );
            background-size: 22px 22px;
        }

        .input-field {
            transition: border-color .2s, box-shadow .2s;
        }

        .input-field:focus {
            border-color: #1687d9;
            box-shadow: 0 0 0 4px rgba(22, 135, 217, .10);
            outline: none;
        }

        .register-button {
            transition: transform .2s, box-shadow .2s;
        }

        .register-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(13, 110, 190, .20);
        }
    </style>
</head>

<body class="min-h-screen text-slate-800">

<main class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">

    <div class="w-full max-w-[1050px]">

        <!-- Logo -->
        <header class="mb-8 flex items-center justify-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="27" height="27" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round">
                    <path d="M12 3v18M3 12h18"/>
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Clinic<span class="text-blue-600">Manager</span>
                </h1>
                <p class="text-xs font-medium tracking-wide text-slate-500">
                    GESTION MÉDICALE INTELLIGENTE
                </p>
            </div>
        </header>

        <!-- Carte principale -->
        <section class="register-card overflow-hidden rounded-[28px] border border-slate-200/80 bg-white">

            <div class="grid md:grid-cols-2">

                <!-- Partie gauche -->
                <div class="medical-pattern relative hidden overflow-hidden bg-[#102f50] p-10 text-white md:flex md:flex-col md:justify-between lg:p-12">

                    <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/10"></div>
                    <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full border border-white/10"></div>
                    <div class="absolute -bottom-24 -left-20 h-64 w-64 rounded-full bg-blue-500/10"></div>

                    <div class="relative z-10">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 text-xs font-semibold text-blue-100">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Rejoignez ClinicManager
                        </span>

                        <h2 class="mt-10 text-3xl font-extrabold leading-tight lg:text-4xl">
                            Votre espace<br>
                            médical commence<br>
                            <span class="text-sky-300">ici.</span>
                        </h2>

                        <p class="mt-5 max-w-sm text-sm leading-7 text-slate-300">
                            Créez votre compte pour accéder à une plateforme conçue pour simplifier l'organisation et le suivi médical.
                        </p>
                    </div>

                    <div class="relative z-10 mt-12 space-y-5">

                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="22" height="22" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Inscription simple</p>
                                <p class="mt-1 text-xs text-slate-300">Quelques informations suffisent.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="22" height="22" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Compte protégé</p>
                                <p class="mt-1 text-xs text-slate-300">Vos identifiants restent confidentiels.</p>
                            </div>
                        </div>

                        <div class="border-t border-white/15 pt-5 text-xs text-slate-400">
                            © <?= date('Y') ?> ClinicManager. Tous droits réservés.
                        </div>
                    </div>
                </div>

                <!-- Partie droite : formulaire -->
                <div class="p-6 sm:p-10 lg:p-12">

                    <div class="mb-8">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 md:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="25" height="25" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round">
                                <path d="M12 3v18M3 12h18"/>
                            </svg>
                        </div>

                        <p class="mb-2 text-sm font-semibold text-blue-600">
                            NOUVEAU COMPTE
                        </p>

                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">
                            Créer un compte
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Remplissez les informations ci-dessous pour commencer.
                        </p>
                    </div>

                    <form action="insert_user.php" method="POST" id="signupForm" class="space-y-5">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <!-- Nom complet -->
                        <div>
                            <label for="fullname" class="mb-2 block text-sm font-semibold text-slate-700">
                                Nom complet
                            </label>

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="19" height="19" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="4"/>
                                        <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                                    </svg>
                                </div>

                                <input
                                    type="text"
                                    id="fullname"
                                    name="fullname"
                                    required
                                    minlength="2"
                                    maxlength="255"
                                    autocomplete="name"
                                    placeholder="Votre nom complet"
                                    class="input-field h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-sm text-slate-800 placeholder:text-slate-400"
                                >
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                                Adresse e-mail
                            </label>

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="19" height="19" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <path d="m3 7 9 6 9-6"/>
                                    </svg>
                                </div>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                    placeholder="nom@exemple.com"
                                    class="input-field h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-sm text-slate-800 placeholder:text-slate-400"
                                >
                            </div>
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                                Mot de passe
                            </label>

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="19" height="19" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <rect x="4" y="10" width="16" height="11" rx="2"/>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                </div>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 caractères"
                                    class="input-field h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-12 text-sm text-slate-800 placeholder:text-slate-400"
                                >

                                <button
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Afficher le mot de passe"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 transition hover:text-blue-600"
                                >
                                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg"
                                         width="20" height="20" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <p class="mt-2 text-xs text-slate-400">
                                Choisissez un mot de passe sécurisé.
                            </p>
                        </div>

                        <!-- Bouton -->
                        <button
                            type="submit"
                            class="register-button flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white"
                        >
                            Créer mon compte

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>

                    </form>

                    <div class="my-7 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-100"></div>
                        <span class="text-xs text-slate-400">Déjà inscrit ?</span>
                        <div class="h-px flex-1 bg-slate-100"></div>
                    </div>

                    <p class="text-center text-sm text-slate-500">
                        Vous avez déjà un compte ?
                        <a href="../login/login.php"
                           class="font-bold text-blue-600 transition hover:text-blue-800">
                            Se connecter
                        </a>
                    </p>

                    <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        Inscription sécurisée
                    </div>

                </div>
            </div>
        </section>

        <p class="mt-5 text-center text-xs text-slate-400">
            ClinicManager · Plateforme de gestion médicale
        </p>

    </div>
</main>

<script src="../../assets/js/main.js"></script>

<script>
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    toggleButton.addEventListener('click', function () {
        const hidden = passwordInput.type === 'password';
        passwordInput.type = hidden ? 'text' : 'password';

        toggleButton.setAttribute(
            'aria-label',
            hidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
        );

        document.getElementById('eyeIcon').innerHTML = hidden
            ? '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.1 3.9"/><path d="M6.6 6.6C3.6 8.4 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.9-.5"/>'
            : '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    });
</script>

</body>
</html>
