<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db.php';

// Pagination Settings
$results_per_page = 4;

// For All Appointments
$page_all = isset($_GET['page_all']) ? max(1, (int)$_GET['page_all']) : 1;
$start_from_all = ($page_all - 1) * $results_per_page;

$sql_total_all = "SELECT COUNT(*) FROM \"rendez_vous\"";
$stmt_total_all = $pdo->query($sql_total_all);
$total_records_all = $stmt_total_all->fetchColumn();
$total_pages_all = max(1, ceil($total_records_all / $results_per_page));

$sql_all = "SELECT u.full_name, r.id, r.cni, r.date_rendez_vous, r.date, r.is_confirmed
            FROM \"user\" u
            JOIN \"rendez_vous\" r
            ON u.id = r.user_id
            LIMIT $results_per_page OFFSET $start_from_all";
$sqlState_all = $pdo->query($sql_all);

// For Today's Appointments
$page_today = isset($_GET['page_today']) ? max(1, (int)$_GET['page_today']) : 1;
$start_from_today = ($page_today - 1) * $results_per_page;

$sql_total_today = "SELECT COUNT(*) FROM \"rendez_vous\" WHERE date::date = CURRENT_DATE";
$stmt_total_today = $pdo->query($sql_total_today);
$total_records_today = $stmt_total_today->fetchColumn();
$total_pages_today = max(1, ceil($total_records_today / $results_per_page));

$sql_today = "SELECT u.full_name, r.id, r.cni, r.date_rendez_vous, r.date, r.is_confirmed
            FROM \"user\" u
            JOIN \"rendez_vous\" r
            ON u.id = r.user_id
            WHERE r.date::date = CURRENT_DATE
            LIMIT $results_per_page OFFSET $start_from_today";
$sqlState_today = $pdo->query($sql_today);

// Content for All Appointments
$content = '
<div class="w-full overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs bg-white dark:bg-gray-800">
    <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Tous les Rendez-vous</h3>
        <span class="text-xs font-semibold px-3 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 rounded-full">
            ' . $total_records_all . ' Total
        </span>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap text-left border-collapse">
            <thead>
                <tr class="text-xs font-semibold tracking-wider text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700 bg-gray-50/75 dark:text-gray-400 dark:bg-gray-700/50">
                    <th class="px-5 py-3.5">Client</th>
                    <th class="px-5 py-3.5">CNI</th>
                    <th class="px-5 py-3.5">Statut</th>
                    <th class="px-5 py-3.5">Date</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">';

while ($row = $sqlState_all->fetch(PDO::FETCH_ASSOC)) {
    $isApproved = ($row['is_confirmed'] == 1);
    $statusClass = $isApproved 
        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200' 
        : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200';
    
    $content .= '
    <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
            ' . htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') . '
        </td>
        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['cni'], ENT_QUOTES, 'UTF-8') . '</td>
        <td class="px-5 py-4 text-sm">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' . $statusClass . '">
                ' . ($isApproved ? 'Approved' : 'Pending') . '
            </span>
        </td>
        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['date_rendez_vous'], ENT_QUOTES, 'UTF-8') . '</td>
        <td class="px-5 py-4 text-sm text-right">
            <form method="POST" action="" class="inline-flex items-center gap-2">
                <input type="hidden" name="id" value="' . $row['id'] . '" />
                <button type="submit" class="px-3.5 py-1.5 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-600 dark:text-purple-300 text-xs font-semibold rounded-xl transition shadow-2xs">
                    Basculer Statut
                </button>
            </form>
        </td>
    </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
</div>';

// Pagination for All
if ($total_pages_all > 1) {
    $content .= '<div class="flex justify-center mt-6">
        <ul class="inline-flex items-center gap-1.5">';
    
    if ($page_all > 1) {
        $content .= '<li><a href="?page_all=' . ($page_all - 1) . '" class="px-3.5 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 transition">Précédent</a></li>';
    }
    
    for ($i = 1; $i <= $total_pages_all; $i++) {
        $activeClass = ($i == $page_all) ? 'bg-purple-600 text-white border-purple-600 shadow-md shadow-purple-600/20' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50';
        $content .= '<li><a href="?page_all=' . $i . '" class="px-3.5 py-2 text-xs font-semibold border rounded-xl transition ' . $activeClass . '">' . $i . '</a></li>';
    }
    
    if ($page_all < $total_pages_all) {
        $content .= '<li><a href="?page_all=' . ($page_all + 1) . '" class="px-3.5 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 transition">Suivant</a></li>';
    }
    
    $content .= '</ul></div>';
}


// Content for Today's Appointments
$content_today = '
<div class="w-full overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs bg-white dark:bg-gray-800">
    <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Rendez-vous d\'aujourd\'hui</h3>
        <span class="text-xs font-semibold px-3 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full">
            ' . $total_records_today . ' Aujourd\'hui
        </span>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap text-left border-collapse">
            <thead>
                <tr class="text-xs font-semibold tracking-wider text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700 bg-gray-50/75 dark:text-gray-400 dark:bg-gray-700/50">
                    <th class="px-5 py-3.5">Client</th>
                    <th class="px-5 py-3.5">CNI</th>
                    <th class="px-5 py-3.5">Statut</th>
                    <th class="px-5 py-3.5">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">';

if (empty($total_records_today)) {
    $content_today .= '
        <tr>
            <td colspan="4" class="px-5 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                Aucun rendez-vous prévu pour aujourd\'hui.
            </td>
        </tr>';
} else {
    while ($row = $sqlState_today->fetch(PDO::FETCH_ASSOC)) {
        $isApproved = ($row['is_confirmed'] == 1);
        $statusClass = $isApproved 
            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200' 
            : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200';

        $content_today .= '
        <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
            <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                ' . htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') . '
            </td>
            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['cni'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="px-5 py-4 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' . $statusClass . '">
                    ' . ($isApproved ? 'Approved' : 'Pending') . '
                </span>
            </td>
            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['date_rendez_vous'], ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
    }
}

$content_today .= '
            </tbody>
        </table>
    </div>
</div>';

// Pagination for Today
if ($total_pages_today > 1) {
    $content_today .= '<div class="flex justify-center mt-6">
        <ul class="inline-flex items-center gap-1.5">';
    
    if ($page_today > 1) {
        $content_today .= '<li><a href="?page_today=' . ($page_today - 1) . '" class="px-3.5 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 transition">Précédent</a></li>';
    }
    
    for ($i = 1; $i <= $total_pages_today; $i++) {
        $activeClass = ($i == $page_today) ? 'bg-purple-600 text-white border-purple-600 shadow-md shadow-purple-600/20' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50';
        $content_today .= '<li><a href="?page_today=' . $i . '" class="px-3.5 py-2 text-xs font-semibold border rounded-xl transition ' . $activeClass . '">' . $i . '</a></li>';
    }
    
    if ($page_today < $total_pages_today) {
        $content_today .= '<li><a href="?page_today=' . ($page_today + 1) . '" class="px-3.5 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 transition">Suivant</a></li>';
    }
    
    $content_today .= '</ul></div>';
}

// Include layout file to display content
include_once '../layouts/masterDoctor.php';
?>