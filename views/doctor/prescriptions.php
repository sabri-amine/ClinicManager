<?php
// views/doctor/prescriptions.php - Gestion des Ordonnances Médicales
require_once __DIR__ . '/../../includes/auth.php';

$sql = '
    SELECT o.id, o.medicaments, o.posologie, o.instructions, o.date_prescription,
           u.full_name AS patient_name, u.email AS patient_email,
           d.full_name AS doctor_name, o.dossier_id
    FROM ordonnance o
    JOIN "user" u ON o.user_id = u.id
    LEFT JOIN "user" d ON o.doctor_id = d.id
    ORDER BY o.date_prescription DESC
';
$stmt = $pdo->query($sql);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content_prescriptions = '
<div class="w-full overflow-hidden rounded-2xl border border-slate-200/80 shadow-xs bg-white">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Prescriptions & Ordonnances Médicales</h3>
            <p class="text-xs text-slate-500">Traitements médicamenteux prescrits aux patients</p>
        </div>
        <span class="text-xs font-semibold px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full">
            ' . count($prescriptions) . ' ordonnance(s)
        </span>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/70">
                    <th class="px-5 py-3.5">Réf</th>
                    <th class="px-5 py-3.5">Patient</th>
                    <th class="px-5 py-3.5">Médecin</th>
                    <th class="px-5 py-3.5">Date</th>
                    <th class="px-5 py-3.5">Médicaments</th>
                    <th class="px-5 py-3.5">Posologie & Instructions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">';

if (empty($prescriptions)) {
    $content_prescriptions .= '
        <tr>
            <td colspan="6" class="px-5 py-10 text-center text-slate-400 text-sm">
                Aucune ordonnance prescrite pour le moment.
            </td>
        </tr>';
} else {
    foreach ($prescriptions as $p) {
        $content_prescriptions .= '
        <tr class="text-slate-700 hover:bg-slate-50/70 transition">
            <td class="px-5 py-4 font-bold text-slate-900 text-xs">ORD-' . (int)$p['id'] . '</td>
            <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">' . e($p['patient_name']) . '</p>
                <p class="text-xs text-slate-400">' . e($p['patient_email']) . '</p>
            </td>
            <td class="px-5 py-4 text-xs font-medium text-slate-600">Dr. ' . e($p['doctor_name'] ?? 'Médecin') . '</td>
            <td class="px-5 py-4 text-xs text-slate-500 whitespace-nowrap">' . date('d/m/Y', strtotime($p['date_prescription'])) . '</td>
            <td class="px-5 py-4 text-xs font-semibold text-slate-800">' . e($p['medicaments']) . '</td>
            <td class="px-5 py-4 text-xs text-slate-600">
                <span class="block">' . e($p['posologie']) . '</span>
                ' . (!empty($p['instructions']) ? ('<span class="block text-slate-400 italic">' . e($p['instructions']) . '</span>') : '') . '
            </td>
        </tr>';
    }
}

$content_prescriptions .= '
            </tbody>
        </table>
    </div>
</div>';

