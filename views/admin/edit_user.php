
<?php
session_start();
require_once('../../db.php');

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../login/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

if (!$edit_id || $edit_id < 1) {
    http_response_code(400);
    exit('Identifiant utilisateur invalide.');
}

$rolesQuery = $pdo->query('SELECT id, name FROM role ORDER BY id');
$roles = $rolesQuery->fetchAll(PDO::FETCH_ASSOC);

$editQuery = $pdo->prepare('
    SELECT u.id, u.full_name, u.email, u.role_id,
           u.status, u.is_confirmed, r.name AS role_name
    FROM "user" u
    JOIN role r ON u.role_id = r.id
    WHERE u.id = :id
');
$editQuery->execute(['id' => $edit_id]);
$user = $editQuery->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('Utilisateur non trouvé.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        $error = 'La session a expiré. Veuillez réessayer.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $role_id = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $is_confirmed = isset($_POST['is_confirmed']);

        if ($full_name === '' || mb_strlen($full_name) > 255) {
            $error = 'Veuillez saisir un nom complet valide.';
        } elseif (!$role_id || !in_array(
            (int)$role_id,
            array_map(
                static fn($role) => (int)$role['id'],
                $roles
            ),
            true
        )) {
            $error = 'Le rôle sélectionné est invalide.';
        } elseif (!in_array($status, ['actif', 'archivé'], true)) {
            $error = 'Le statut sélectionné est invalide.';
        } else {
            try {
                $updateQuery = $pdo->prepare('
                    UPDATE "user"
                    SET full_name = :full_name,
                        role_id = :role_id,
                        status = :status,
                        is_confirmed = :is_confirmed,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ');

                $updateQuery->bindValue(
                    ':full_name',
                    $full_name,
                    PDO::PARAM_STR
                );
                $updateQuery->bindValue(
                    ':role_id',
                    (int)$role_id,
                    PDO::PARAM_INT
                );
                $updateQuery->bindValue(
                    ':status',
                    $status,
                    PDO::PARAM_STR
                );
                $updateQuery->bindValue(
                    ':is_confirmed',
                    $is_confirmed,
                    PDO::PARAM_BOOL
                );
                $updateQuery->bindValue(
                    ':id',
                    (int)$edit_id,
                    PDO::PARAM_INT
                );

                $updateQuery->execute();

                header('Location: admin.php?updated=1');
                exit;
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Impossible de modifier cet utilisateur pour le moment.';
            }
        }

        // Garder les valeurs saisies si le formulaire contient une erreur
        $user['full_name'] = $full_name;
        $user['role_id'] = $role_id ?: $user['role_id'];
        $user['status'] = $status;
        $user['is_confirmed'] = $is_confirmed;
    }
}

$isConfirmed = in_array(
    $user['is_confirmed'],
    [true, 1, '1', 't', 'true'],
    true
);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modifier utilisateur | ClinicManager</title>

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

        h1, h2 {
            font-family: 'Manrope', sans-serif;
        }

        .input-field {
            transition: border-color .2s, box-shadow .2s;
        }

        .input-field:focus {
            border-color: #1687d9;
            box-shadow: 0 0 0 4px rgba(22, 135, 217, .10);
            outline: none;
        }

        .save-button {
            transition: transform .2s, box-shadow .2s;
        }

        .save-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(13, 110, 190, .20);
        }
    </style>
</head>

<body class="min-h-screen text-slate-800">

<main class="min-h-screen px-4 py-8 sm:px-6">

    <div class="mx-auto max-w-3xl">

        <!-- En-tête -->
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="26" height="26" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round">
                        <path d="M12 3v18M3 12h18"/>
                    </svg>
                </div>

                <div>
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900">
                        Clinic<span class="text-blue-600">Manager</span>
                    </h1>
                    <p class="text-xs text-slate-500">Administration médicale</p>
                </div>
            </div>

            <a href="admin.php"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="17" height="17" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M9 12h12"/>
                </svg>
                Retour
            </a>
        </header>

        <!-- Titre -->
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">
                Gestion des comptes
            </p>

            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                Modifier l'utilisateur
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Modifiez les informations et les autorisations de ce compte.
            </p>
        </div>

        <!-- Carte -->
        <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(24,50,90,0.07)]">

            <div class="flex items-center gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-5 sm:px-8">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-xl font-extrabold text-blue-700">
                    <?= h(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?>
                </div>

                <div class="min-w-0">
                    <p class="truncate font-bold text-slate-900">
                        <?= h($user['full_name']) ?>
                    </p>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        <?= h($user['email']) ?>
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Identifiant : #<?= (int)$user['id'] ?>
                    </p>
                </div>
            </div>

            <div class="p-6 sm:p-8">

                <?php if ($error !== ''): ?>
                    <div role="alert"
                         class="mb-6 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_user.php?edit_id=<?= (int)$edit_id ?>" class="space-y-6">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($_SESSION['csrf_token']) ?>"
                    >

                    <!-- Nom -->
                    <div>
                        <label for="full_name" class="mb-2 block text-sm font-semibold text-slate-700">
                            Nom complet
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            maxlength="255"
                            value="<?= h($user['full_name']) ?>"
                            required
                            class="input-field h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm"
                            placeholder="Nom et prénom"
                        >
                    </div>

                    <!-- Email en lecture seule -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                            Adresse e-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            value="<?= h($user['email']) ?>"
                            readonly
                            class="h-12 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm text-slate-500"
                        >

                        <p class="mt-2 text-xs text-slate-400">
                            L'adresse e-mail ne peut pas être modifiée depuis cette page.
                        </p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">

                        <!-- Rôle -->
                        <div>
                            <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">
                                Rôle utilisateur
                            </label>

                            <select
                                id="role"
                                name="role"
                                required
                                class="input-field h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm"
                            >
                                <?php foreach ($roles as $role): ?>
                                    <option
                                        value="<?= (int)$role['id'] ?>"
                                        <?= (int)$user['role_id'] === (int)$role['id'] ? 'selected' : '' ?>
                                    >
                                        <?= h(ucfirst($role['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Statut -->
                        <div>
                            <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">
                                Statut du compte
                            </label>

                            <select
                                id="status"
                                name="status"
                                required
                                class="input-field h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm"
                            >
                                <option value="actif" <?= $user['status'] === 'actif' ? 'selected' : '' ?>>
                                    Actif
                                </option>

                                <option value="archivé" <?= $user['status'] === 'archivé' ? 'selected' : '' ?>>
                                    Archivé
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Confirmation -->
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                id="is_confirmed"
                                name="is_confirmed"
                                value="1"
                                <?= $isConfirmed ? 'checked' : '' ?>
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >

                            <div>
                                <label for="is_confirmed" class="cursor-pointer text-sm font-bold text-slate-800">
                                    Confirmer le compte
                                </label>

                                <p class="mt-1 text-sm leading-5 text-slate-500">
                                    Autorise l'utilisateur standard à se connecter si son compte est également actif.
                                </p>

                                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $isConfirmed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                    <?= $isConfirmed ? 'Compte confirmé' : 'En attente de confirmation' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                        <a
                            href="admin.php"
                            class="inline-flex h-12 items-center justify-center rounded-xl border border-slate-200 px-6 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Annuler
                        </a>

                        <button
                            type="submit"
                            class="save-button inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-blue-600 px-7 text-sm font-bold text-white"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path d="M17 21v-8H7v8M7 3v5h8"/>
                            </svg>
                            Enregistrer les modifications
                        </button>
                    </div>

                </form>
            </div>
        </section>

        <p class="mt-6 text-center text-xs text-slate-400">
            © <?= date('Y') ?> ClinicManager · Administration sécurisée
        </p>

    </div>
</main>

</body>
</html>
