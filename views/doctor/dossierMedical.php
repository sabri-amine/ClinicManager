<?php
// views/doctor/dossierMedical.php - Liste des dossiers médicaux
require_once __DIR__ . '/../../includes/auth.php';

$csrfToken = getCsrfToken();

$sql = '
    SELECT u.full_name AS patient_name, u.email AS patient_email,
           d.dossier_id, d.date_dossie, d.diagnostic, d.traitement,
           doc.full_name AS doctor_name,
           c.num AS num_chambre, c.type AS type_chambre, c.statut AS statut_chambre
    FROM dossier_medical d
    JOIN "user" u ON d.user_id = u.id
    LEFT JOIN "user" doc ON d.doctor_id = doc.id
    LEFT JOIN chambre c ON d.dossier_id = c.dossier_id
    ORDER BY d.dossier_id DESC
';

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content_dossier = '
<div class="w-full overflow-hidden rounded-2xl border border-slate-200/80 shadow-xs bg-white">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Dossiers Médicaux</h3>
            <p class="text-xs text-slate-500">Comptes-rendus médicaux, diagnostics et affectations</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold px-3 py-1 bg-blue-50 text-blue-700 rounded-full">
                ' . count($results) . ' dossier(s)
            </span>
            <a href="form.php" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="bi bi-plus-lg"></i> Nouveau dossier
            </a>
        </div>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/70">
                    <th class="px-5 py-3.5">ID</th>
                    <th class="px-5 py-3.5">Patient</th>
                    <th class="px-5 py-3.5">Médecin</th>
                    <th class="px-5 py-3.5">Date</th>
                    <th class="px-5 py-3.5">Diagnostic</th>
                    <th class="px-5 py-3.5">Traitement</th>
                    <th class="px-5 py-3.5">Chambre</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">';

if (empty($results)) {
    $content_dossier .= '
        <tr>
            <td colspan="8" class="px-5 py-10 text-center text-slate-400 text-sm">
                Aucun dossier médical enregistré pour le moment.
            </td>
        </tr>';
} else {
    foreach ($results as $row) {
        $chambreInfo = !empty($row['num_chambre']) 
            ? ('<span class="text-xs font-semibold text-slate-700">N° ' . e($row['num_chambre']) . ' <span class="text-[11px] text-slate-400">(' . e($row['type_chambre']) . ')</span></span>') 
            : '<span class="text-xs text-slate-400 italic">Non attribuée</span>';

        $content_dossier .= '
        <tr class="text-slate-700 hover:bg-slate-50/70 transition">
            <td class="px-5 py-4 font-bold text-slate-900 text-xs">#' . (int)$row['dossier_id'] . '</td>
            <td class="px-5 py-4">
                <p class="font-semibold text-slate-900 text-sm">' . e($row['patient_name']) . '</p>
                <p class="text-xs text-slate-400">' . e($row['patient_email']) . '</p>
            </td>
            <td class="px-5 py-4 text-xs font-medium text-slate-600">
                Dr. ' . e($row['doctor_name'] ?? 'Non spécifié') . '
            </td>
            <td class="px-5 py-4 text-xs text-slate-500 whitespace-nowrap">
                ' . date('d/m/Y', strtotime($row['date_dossie'])) . '
            </td>
            <td class="px-5 py-4 text-xs text-slate-600 max-w-[200px] truncate" title="' . e($row['diagnostic']) . '">
                ' . e($row['diagnostic']) . '
            </td>
            <td class="px-5 py-4 text-xs text-slate-600 max-w-[200px] truncate" title="' . e($row['traitement']) . '">
                ' . e($row['traitement']) . '
            </td>
            <td class="px-5 py-4 whitespace-nowrap">' . $chambreInfo . '</td>
            <td class="px-5 py-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                    <a href="edit_dossier.php?dossier_id=' . (int)$row['dossier_id'] . '" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition" title="Modifier ce dossier">
                        <i class="bi bi-pencil-square text-sm"></i>
                    </a>
                    <a href="delete_dossier.php?dossier_id=' . (int)$row['dossier_id'] . '&csrf_token=' . e($csrfToken) . '" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition" title="Supprimer" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer définitivement ce dossier ?\');">
                        <i class="bi bi-trash-fill text-sm"></i>
                    </a>
                </div>
            </td>
        </tr>';
    }
}

$content_dossier .= '
            </tbody>
        </table>
    </div>
</div>';
