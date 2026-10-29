<?php
// views/admin/admin.php - Gestion des utilisateurs pour l'administrateur
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$csrfToken = getCsrfToken();
$limit = 5;

$page = max(1, (int)($_GET['page'] ?? 1));
$archivePage = max(1, (int)($_GET['archive_page'] ?? 1));

function isUserConfirmed($value): bool
{
    return in_array($value, [true, 1, '1', 't', 'true'], true);
}

// Nombre d'utilisateurs actifs
$countActive = $pdo->query("SELECT COUNT(*) FROM \"user\" WHERE status = 'actif'");
$totalActive = (int)$countActive->fetchColumn();

$totalPages = max(1, (int)ceil($totalActive / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Liste des utilisateurs actifs
$sql = $pdo->prepare("
    SELECT u.id AS id_user, u.full_name, u.email, u.telephone, u.specialite,
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
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Administration</p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-900">Utilisateurs Actifs</h2>
            <p class="mt-1 text-xs text-slate-500">Gérez les comptes, les rôles et les confirmations d\'accès.</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-blue-100 bg-blue-50 px-5 py-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white">
                <i class="bi bi-people-fill text-lg"></i>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-500">Comptes Actifs</p>
                <p class="text-xl font-extrabold text-slate-900">' . $totalActive . '</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Utilisateur</th>
                        <th class="px-6 py-4">Rôle</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">Confirmation</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">';

if (empty($users)) {
    $content .= '
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                Aucun utilisateur actif trouvé.
            </td>
        </tr>';
}

foreach ($users as $user) {
    $id = (int)$user['id_user'];
    $name = e($user['full_name']);
    $email = e($user['email']);
    $role = e($user['role_name']);
    $confirmed = isUserConfirmed($user['is_confirmed']);

    $confirmationBadge = $confirmed
        ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Confirmé</span>'
        : '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>En attente</span>';

    $roleBadgeColor = match($role) {
        'admin' => 'bg-purple-50 text-purple-700 border-purple-200',
        'doctor' => 'bg-blue-50 text-blue-700 border-blue-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200'
    };

    $content .= '
        <tr class="hover:bg-slate-50/70 transition">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-blue-700 text-sm shadow-inner">
                        ' . e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) . '
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">' . $name . '</p>
                        <p class="text-xs text-slate-400">' . $email . '</p>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-lg border px-2.5 py-1 text-xs font-semibold ' . $roleBadgeColor . '">' . ucfirst($role) . '</span>
            </td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Actif
                </span>
            </td>
            <td class="px-6 py-4">' . $confirmationBadge . '</td>
            <td class="px-6 py-4">
                <div class="flex justify-end items-center gap-2">
                    <a href="edit_user.php?edit_id=' . $id . '" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 transition">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <form method="POST" action="delete_user.php" onsubmit="return confirm(\'Voulez-vous archiver ce compte ? Les données seront préservées.\');">
                        <input type="hidden" name="csrf_token" value="' . e($csrfToken) . '">
                        <input type="hidden" name="id" value="' . $id . '">
                        <input type="hidden" name="action" value="archive">
                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition">
                            <i class="bi bi-archive"></i> Archiver
                        </button>
                    </form>
                </div>
            </td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>

        <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center text-xs text-slate-500">
            <p>Page ' . $page . ' sur ' . $totalPages . '</p>
            <div class="flex gap-2">
                <a href="?page=' . max(1, $page - 1) . '" class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium ' . ($page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">Précédent</a>
                <a href="?page=' . min($totalPages, $page + 1) . '" class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium ' . ($page >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">Suivant</a>
            </div>
        </div>
    </div>
</section>';

// ------------------------------------
// Utilisateurs archivés
// ------------------------------------
$countArchive = $pdo->query("SELECT COUNT(*) FROM \"user\" WHERE status = 'archivé'");
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
<section class="space-y-6">
    <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Comptes Utilisateurs Archivés</h3>
            <p class="mt-1 text-xs text-slate-500">Ces comptes sont désactivés mais leurs données médicales restent conservées.</p>
        </div>
        <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
            ' . $totalArchive . ' compte(s) archivé(s)
        </span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Utilisateur</th>
                        <th class="px-6 py-4">Rôle</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">';

if (empty($usersArchive)) {
    $userArchif .= '
        <tr>
            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">
                Aucun utilisateur archivé.
            </td>
        </tr>';
}

foreach ($usersArchive as $user) {
    $id = (int)$user['id_user'];
    $name = e($user['full_name']);
    $email = e($user['email']);
    $role = e($user['role_name']);

    $userArchif .= '
        <tr class="hover:bg-slate-50/70 transition">
            <td class="px-6 py-4">
                <p class="font-semibold text-slate-800">' . $name . '</p>
                <p class="text-xs text-slate-400">' . $email . '</p>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">' . ucfirst($role) . '</span>
            </td>
            <td class="px-6 py-4">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Archivé</span>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                    <form method="POST" action="delete_user.php">
                        <input type="hidden" name="csrf_token" value="' . e($csrfToken) . '">
                        <input type="hidden" name="id" value="' . $id . '">
                        <input type="hidden" name="action" value="restore">
                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurer
                        </button>
                    </form>
                    <a href="edit_user.php?edit_id=' . $id . '" class="inline-flex rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Modifier
                    </a>
                </div>
            </td>
        </tr>';
}

$userArchif .= '
                </tbody>
            </table>
        </div>

        <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center text-xs text-slate-500">
            <p>Page ' . $archivePage . ' sur ' . $totalArchivePages . '</p>
            <div class="flex gap-2">
                <a href="?archive_page=' . max(1, $archivePage - 1) . '" class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium ' . ($archivePage <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">Précédent</a>
                <a href="?archive_page=' . min($totalArchivePages, $archivePage + 1) . '" class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium ' . ($archivePage >= $totalArchivePages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50') . '">Suivant</a>
            </div>
        </div>
    </div>
</section>';

$title = 'Gestion des Utilisateurs';
include __DIR__ . '/../layouts/masterAdmin.php';
