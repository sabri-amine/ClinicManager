
<?php
session_start();
require_once('../../db.php');

// Protection de la page administrateur
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../login/login.php');
    exit;
}

$limit = 5;

$page = max(1, (int)($_GET['page'] ?? 1));
$archivePage = max(1, (int)($_GET['archive_page'] ?? 1));

function isUserConfirmed($value): bool
{
    return in_array($value, [true, 1, '1', 't', 'true'], true);
}

function escapeHtml($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Nombre d'utilisateurs actifs
$countActive = $pdo->query(
    "SELECT COUNT(*) FROM \"user\" WHERE status = 'actif'"
);
$totalActive = (int)$countActive->fetchColumn();

$totalPages = max(1, (int)ceil($totalActive / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Liste des utilisateurs actifs
$sql = $pdo->prepare("
    SELECT u.id AS id_user, u.full_name, u.email,
           u.is_confirmed, u.status, r.name AS role_name
    FROM \"user\" u
    JOIN role r ON u.role_id = r.id
    WHERE u.status = 'actif'
    ORDER BY u.id DESC
    LIMIT :limit OFFSET :offset
");
$sql->bindValue(':limit', $limit, PDO::PARAM_INT);
$sql->bindValue(':offset', $offset, PDO::PARAM_INT);
$sql->execute();
$users = $sql->fetchAll(PDO::FETCH_ASSOC);

// Tableau des utilisateurs actifs
$content = '
<section class="space-y-6">

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">
                Administration
            </p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-900">
                Gestion des utilisateurs
            </h2>
            <p class="mt-2 text-sm text-slate-500">
                Gérez les comptes et les confirmations des utilisateurs.
            </p>
        </div>

        <div class="flex items-center gap-3 rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="10" cy="7" r="4"/>
                    <path d="M20 8v6M23 11h-6"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Utilisateurs actifs</p>
                <p class="text-2xl font-extrabold text-slate-900">' . $totalActive . '</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Utilisateurs actifs</h3>
                <p class="mt-1 text-sm text-slate-500">Liste des comptes actuellement actifs.</p>
            </div>
            <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                ' . $totalActive . ' compte(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Utilisateur</th>
                        <th class="px-6 py-4 font-semibold">Rôle</th>
                        <th class="px-6 py-4 font-semibold">Statut</th>
                        <th class="px-6 py-4 font-semibold">Confirmation</th>
                        <th class="px-6 py-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">';

if (empty($users)) {
    $content .= '
        <tr>
            <td colspan="5" class="px-6 py-12 text-center">
                <p class="font-semibold text-slate-700">Aucun utilisateur actif</p>
                <p class="mt-1 text-sm text-slate-400">Les comptes actifs apparaîtront ici.</p>
            </td>
        </tr>';
}

foreach ($users as $user) {
    $id = (int)$user['id_user'];
    $name = escapeHtml($user['full_name']);
    $email = escapeHtml($user['email']);
    $role = escapeHtml($user['role_name']);
    $confirmed = isUserConfirmed($user['is_confirmed']);

    $confirmationBadge = $confirmed
        ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Confirmé</span>'
        : '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>En attente</span>';

    $content .= '
        <tr class="transition hover:bg-slate-50/80">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-700">
                        ' . escapeHtml(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) . '
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">' . $name . '</p>
                        <p class="mt-1 text-xs text-slate-500">' . $email . '</p>
                        <p class="mt-1 text-xs text-slate-400">ID : ' . $id . '</p>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">' . $role . '</span>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Actif</span>
            </td>
            <td class="px-6 py-4">' . $confirmationBadge . '</td>
            <td class="px-6 py-4">
                <div class="flex justify-end gap-2">
                    <a href="edit_user.php?edit_id=' . $id . '"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                       Modifier
                    </a>
                    <a href="delete_user.php?id=' . $id . '"
                       class="inline-flex items-center rounded-lg border border-red-100 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                       onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cet utilisateur ?\')">
                       Supprimer
                    </a>
                </div>
            </td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>

        <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center">
            <p class="text-sm text-slate-500">
                Page ' . $page . ' sur ' . $totalPages . '
            </p>
            <div class="flex gap-2">
                <a href="?page=' . max(1, $page - 1) . '"
                   class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium ' . ($page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">
                    Précédent
                </a>
                <a href="?page=' . min($totalPages, $page + 1) . '"
                   class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium ' . ($page >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">
                    Suivant
                </a>
            </div>
        </div>
    </div>
</section>';

// ------------------------------------
// Utilisateurs archivés
// ------------------------------------

$countArchive = $pdo->query(
    "SELECT COUNT(*) FROM \"user\" WHERE status = 'archivé'"
);
$totalArchive = (int)$countArchive->fetchColumn();

$totalArchivePages = max(1, (int)ceil($totalArchive / $limit));
$archivePage = min($archivePage, $totalArchivePages);
$archiveOffset = ($archivePage - 1) * $limit;

$sqlArchive = $pdo->prepare("
    SELECT u.id AS id_user, u.full_name, u.email,
           u.is_confirmed, u.status, r.name AS role_name
    FROM \"user\" u
    JOIN role r ON u.role_id = r.id
    WHERE u.status = 'archivé'
    ORDER BY u.id DESC
    LIMIT :limit OFFSET :offset
");
$sqlArchive->bindValue(':limit', $limit, PDO::PARAM_INT);
$sqlArchive->bindValue(':offset', $archiveOffset, PDO::PARAM_INT);
$sqlArchive->execute();
$usersArchive = $sqlArchive->fetchAll(PDO::FETCH_ASSOC);

$userArchif = '
<section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Utilisateurs archivés</h3>
            <p class="mt-1 text-sm text-slate-500">Consultez les comptes archivés.</p>
        </div>
        <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
            ' . $totalArchive . ' compte(s)
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-6 py-4 font-semibold">Utilisateur</th>
                    <th class="px-6 py-4 font-semibold">Rôle</th>
                    <th class="px-6 py-4 font-semibold">Confirmation</th>
                    <th class="px-6 py-4 text-right font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">';

if (empty($usersArchive)) {
    $userArchif .= '
        <tr>
            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                Aucun utilisateur archivé.
            </td>
        </tr>';
}

foreach ($usersArchive as $user) {
    $id = (int)$user['id_user'];
    $name = escapeHtml($user['full_name']);
    $email = escapeHtml($user['email']);
    $role = escapeHtml($user['role_name']);

    $confirmation = isUserConfirmed($user['is_confirmed'])
        ? '<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Confirmé</span>'
        : '<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">En attente</span>';

    $userArchif .= '
        <tr class="transition hover:bg-slate-50/80">
            <td class="px-6 py-4">
                <p class="font-semibold text-slate-800">' . $name . '</p>
                <p class="mt-1 text-xs text-slate-500">' . $email . '</p>
                <p class="mt-1 text-xs text-slate-400">ID : ' . $id . '</p>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">' . $role . '</span>
            </td>
            <td class="px-6 py-4">' . $confirmation . '</td>
            <td class="px-6 py-4 text-right">
                <a href="edit_user.php?edit_id=' . $id . '"
                   class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                    Modifier
                </a>
            </td>
        </tr>';
}

$userArchif .= '
            </tbody>
        </table>
    </div>

    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center">
        <p class="text-sm text-slate-500">
            Page ' . $archivePage . ' sur ' . $totalArchivePages . '
        </p>
        <div class="flex gap-2">
            <a href="?archive_page=' . max(1, $archivePage - 1) . '"
               class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium ' . ($archivePage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">
                Précédent
            </a>
            <a href="?archive_page=' . min($totalArchivePages, $archivePage + 1) . '"
               class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium ' . ($archivePage >= $totalArchivePages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">
                Suivant
            </a>
        </div>
    </div>
</section>';

$title = 'Gestion des utilisateurs';

include '../layouts/masterAdmin.php';
?>
