<?php
// views/doctor/contentAdmin.php - Gestion des Rendez-vous côté Médecin
require_once __DIR__ . '/../../includes/auth.php';

$csrfToken = getCsrfToken();
$results_per_page = 6;

// Rendez-vous globaux
$page_all = isset($_GET['page_all']) ? max(1, (int)$_GET['page_all']) : 1;
$start_from_all = ($page_all - 1) * $results_per_page;

$sql_total_all = "SELECT COUNT(*) FROM rendez_vous";
$total_records_all = (int)$pdo->query($sql_total_all)->fetchColumn();
$total_pages_all = max(1, (int)ceil($total_records_all / $results_per_page));

$sql_all = "
    SELECT r.id, r.cni, r.date_rendez_vous, r.heure_rendez_vous, r.heure_fin, r.motif, r.statut, r.is_confirmed,
           r.note_medecin, r.date AS date_demande,
           u.full_name AS patient_name, u.email AS patient_email,
           d.full_name AS doctor_name
    FROM rendez_vous r
    JOIN \"user\" u ON r.user_id = u.id
    LEFT JOIN \"user\" d ON r.doctor_id = d.id
    ORDER BY r.date_rendez_vous DESC
    LIMIT $results_per_page OFFSET $start_from_all
";
$stmt_all = $pdo->query($sql_all);
$rows_all = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// Rendez-vous d'aujourd'hui
$page_today = isset($_GET['page_today']) ? max(1, (int)$_GET['page_today']) : 1;
$start_from_today = ($page_today - 1) * $results_per_page;

$sql_total_today = "SELECT COUNT(*) FROM rendez_vous WHERE date_rendez_vous::date = CURRENT_DATE";
$total_records_today = (int)$pdo->query($sql_total_today)->fetchColumn();
$total_pages_today = max(1, (int)ceil($total_records_today / $results_per_page));

$sql_today = "
    SELECT r.id, r.cni, r.date_rendez_vous, r.heure_rendez_vous, r.heure_fin, r.motif, r.statut, r.is_confirmed,
           r.note_medecin, r.date AS date_demande,
           u.full_name AS patient_name, u.email AS patient_email,
           d.full_name AS doctor_name
    FROM rendez_vous r
    JOIN \"user\" u ON r.user_id = u.id
    LEFT JOIN \"user\" d ON r.doctor_id = d.id
    WHERE r.date_rendez_vous::date = CURRENT_DATE
    ORDER BY r.heure_rendez_vous ASC
    LIMIT $results_per_page OFFSET $start_from_today
";
$stmt_today = $pdo->query($sql_today);
$rows_today = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

function renderAppointmentTable(array $rows, int $totalCount, string $title, string $pageParam, int $currentPage, int $totalPages, string $csrfToken): string {
    $today = date('Y-m-d');

    $html = '
    <div class="w-full overflow-hidden rounded-2xl border border-slate-200/80 shadow-xs bg-white">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-900">' . e($title) . '</h3>
                <p class="text-xs text-slate-500">Examinez les demandes en attente, acceptez-les et fixez la date et l\'heure de consultation</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 bg-blue-50 text-blue-700 rounded-full">
                ' . $totalCount . ' RDV
            </span>
        </div>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/70">
                        <th class="px-5 py-3.5">Patient</th>
                        <th class="px-5 py-3.5">CNI</th>
                        <th class="px-5 py-3.5">Date Souhaitée / Fixée</th>
                        <th class="px-5 py-3.5">Statut Actuel</th>
                        <th class="px-5 py-3.5 text-right">Actions Médecin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">';

    if (empty($rows)) {
        $html .= '
            <tr>
                <td colspan="5" class="px-5 py-10 text-center text-slate-400 text-sm">
                    Aucun rendez-vous dans cette section.
                </td>
            </tr>';
    } else {
        foreach ($rows as $row) {
            $st = $row['statut'] ?? ($row['is_confirmed'] ? 'confirme' : 'en_attente');
            
            // Badges de statut
            if ($st === 'confirme') {
                $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><i class="bi bi-check-circle-fill text-emerald-600"></i>Confirmé</span>';
            } elseif ($st === 'termine') {
                $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"><i class="bi bi-check2-all text-slate-500"></i>Terminé</span>';
            } elseif ($st === 'refuse') {
                $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200"><i class="bi bi-x-circle text-rose-600"></i>Refusé</span>';
            } elseif ($st === 'annule') {
                $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><i class="bi bi-slash-circle text-slate-500"></i>Annulé</span>';
            } else {
                $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse"><i class="bi bi-hourglass-split text-amber-600"></i>En attente</span>';
            }

            $dateRdvVal = date('Y-m-d', strtotime($row['date_rendez_vous']));
            $dateFormatted = date('d/m/Y', strtotime($row['date_rendez_vous']));
            $heureDebutStr = $row['heure_rendez_vous'] ? substr($row['heure_rendez_vous'], 0, 5) : '09:00';
            $heureFinStr = $row['heure_fin'] ? substr($row['heure_fin'], 0, 5) : date('H:i', strtotime($heureDebutStr . ' + 1 hour'));

            $html .= '
            <tr class="text-slate-700 hover:bg-slate-50/70 transition">
                <td class="px-5 py-4">
                    <p class="font-bold text-slate-900">' . e($row['patient_name']) . '</p>
                    <p class="text-xs text-slate-400">' . e($row['patient_email']) . '</p>
                </td>
                <td class="px-5 py-4 text-xs font-medium text-slate-600">' . e($row['cni']) . '</td>
                <td class="px-5 py-4 text-xs text-slate-700 whitespace-nowrap">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                        <i class="bi bi-calendar3 text-blue-600"></i>' . $dateFormatted . '
                    </div>
                    <div class="text-slate-500 text-[11px] mt-0.5">
                        Créneau : ' . $heureDebutStr . ' - ' . $heureFinStr . '
                    </div>
                </td>
                <td class="px-5 py-4">
                    ' . $statusBadge . '
                </td>
                <td class="px-5 py-4 text-right">
                    <form method="POST" action="" class="inline-flex flex-col items-end gap-2">
                        <input type="hidden" name="csrf_token" value="' . e($csrfToken) . '" />
                        <input type="hidden" name="id" value="' . (int)$row['id'] . '" />';

            if ($st === 'en_attente') {
                // LE MÉDECIN ACCEPTE ET FIXE LA DATE & L'HEURE EXACTES
                $html .= '
                        <div class="flex flex-wrap items-center justify-end gap-1.5 p-2 rounded-2xl bg-blue-50/60 border border-blue-100">
                            <span class="text-[11px] font-bold text-slate-700">Fixer consultation :</span>
                            <input type="date" name="fix_date" min="' . $today . '" value="' . $dateRdvVal . '" class="text-xs border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white text-slate-800 font-semibold focus:outline-none focus:border-blue-600 shadow-2xs" required />
                            
                            <select name="fix_heure" class="text-xs border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white text-slate-800 font-semibold focus:outline-none focus:border-blue-600 shadow-2xs">
                                <option value="08:00" ' . ($heureDebutStr === '08:00' ? 'selected' : '') . '>08:00 - 09:00</option>
                                <option value="09:00" ' . ($heureDebutStr === '09:00' ? 'selected' : '') . '>09:00 - 10:00</option>
                                <option value="10:00" ' . ($heureDebutStr === '10:00' ? 'selected' : '') . '>10:00 - 11:00</option>
                                <option value="11:00" ' . ($heureDebutStr === '11:00' ? 'selected' : '') . '>11:00 - 12:00</option>
                                <option value="14:00" ' . ($heureDebutStr === '14:00' ? 'selected' : '') . '>14:00 - 15:00</option>
                                <option value="15:00" ' . ($heureDebutStr === '15:00' ? 'selected' : '') . '>15:00 - 16:00</option>
                                <option value="16:00" ' . ($heureDebutStr === '16:00' ? 'selected' : '') . '>16:00 - 17:00</option>
                                <option value="17:00" ' . ($heureDebutStr === '17:00' ? 'selected' : '') . '>17:00 - 18:00</option>
                            </select>

                            <button type="submit" name="action_btn" value="accepter" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1">
                                <i class="bi bi-check-circle-fill"></i> Accepter et fixer
                            </button>

                            <button type="submit" name="action_btn" value="refuser" class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 text-xs font-bold rounded-xl border border-rose-200 transition">
                                Refuser
                            </button>
                        </div>';
            } elseif ($st === 'confirme') {
                $html .= '
                        <div class="flex items-center gap-1.5">
                            <button type="submit" name="action_btn" value="termine" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1">
                                <i class="bi bi-check2"></i> Marquer terminé
                            </button>
                            <button type="submit" name="action_btn" value="annule" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition">
                                Annuler
                            </button>
                        </div>';
            } else {
                $html .= '<span class="text-xs text-slate-400 font-medium">Aucune action requise</span>';
            }

            $html .= '
                    </form>
                </td>
            </tr>';
        }
    }

    $html .= '
                </tbody>
            </table>
        </div>';

    if ($totalPages > 1) {
        $prev = max(1, $currentPage - 1);
        $next = min($totalPages, $currentPage + 1);
        $html .= '
        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Page ' . $currentPage . ' sur ' . $totalPages . '</span>
            <div class="flex gap-2">
                <a href="?' . $pageParam . '=' . $prev . '" class="px-3 py-1.5 rounded-lg border border-slate-200 ' . ($currentPage <= 1 ? 'opacity-40 pointer-events-none' : 'hover:bg-slate-50') . '">Précédent</a>
                <a href="?' . $pageParam . '=' . $next . '" class="px-3 py-1.5 rounded-lg border border-slate-200 ' . ($currentPage >= $totalPages ? 'opacity-40 pointer-events-none' : 'hover:bg-slate-50') . '">Suivant</a>
            </div>
        </div>';
    }

    $html .= '</div>';
    return $html;
}

$content = renderAppointmentTable($rows_all, $total_records_all, "Toutes les Demandes de Rendez-vous", "page_all", $page_all, $total_pages_all, $csrfToken);
$content_today = renderAppointmentTable($rows_today, $total_records_today, "Rendez-vous Confirmés Aujourd'hui", "page_today", $page_today, $total_pages_today, $csrfToken);