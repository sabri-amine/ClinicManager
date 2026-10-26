<?php 
    include("../../db.php");

    $sql = "SELECT u.full_name, d.dossier_id, d.date_dossie, d.diagnostic, d.traitement, c.num AS \"N° Chambre\", c.type, c.statut AS \"Statut Chambre\"
    FROM \"user\" u
    JOIN dossier_medical d ON u.id = d.user_id
    JOIN chambre c ON d.dossier_id = c.chambre_id";

    $sqlState = $pdo->query($sql);
    $results = $sqlState->fetchAll(PDO::FETCH_ASSOC);

    $content_dossier = '
    <div class="w-full overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs bg-white dark:bg-gray-800">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Liste des Dossiers Médicaux</h3>
            <span class="text-xs font-semibold px-3 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 rounded-full">
                ' . count($results) . ' Enregistrements
            </span>
        </div>
        <div class="w-full overflow-x-auto">
            <table class="w-full whitespace-no-wrap text-left border-collapse">
                <thead>
                    <tr class="text-xs font-semibold tracking-wider text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700 bg-gray-50/75 dark:text-gray-400 dark:bg-gray-700/50">
                        <th class="px-5 py-3.5">Patient</th>
                        <th class="px-5 py-3.5">Date Dossier</th>
                        <th class="px-5 py-3.5">Diagnostic</th>
                        <th class="px-5 py-3.5">Traitement</th>
                        <th class="px-5 py-3.5">N° Chambre</th>
                        <th class="px-5 py-3.5">Type</th>
                        <th class="px-5 py-3.5">Statut</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">';

    if (empty($results)) {
        $content_dossier .= '
            <tr>
                <td colspan="8" class="px-5 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                    Aucun dossier médical trouvé.
                </td>
            </tr>';
    } else {
        foreach ($results as $row) {
            $statutClass = ($row['Statut Chambre'] === 'Disponible') 
                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200' 
                : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200';

            $content_dossier .= '
            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">' . htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['date_dossie'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm max-w-xs truncate" title="' . htmlspecialchars($row['diagnostic'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['diagnostic'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm max-w-xs truncate" title="' . htmlspecialchars($row['traitement'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['traitement'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm font-medium">' . htmlspecialchars($row['N° Chambre'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="px-5 py-4 text-sm">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border ' . $statutClass . '">
                        ' . htmlspecialchars($row['Statut Chambre'], ENT_QUOTES, 'UTF-8') . '
                    </span>
                </td>
                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-2">
                        <a href="edit_dossier.php?dossier_id=' . htmlspecialchars($row['dossier_id'], ENT_QUOTES, 'UTF-8') . '" class="p-2 text-blue-600 bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-100 rounded-xl transition" title="Modifier">
                            <i class="bi bi-pencil-square text-base"></i>
                        </a>
                        <a href="delete_dossier.php?dossier_id=' . htmlspecialchars($row['dossier_id'], ENT_QUOTES, 'UTF-8') . '" class="p-2 text-red-600 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 rounded-xl transition" title="Supprimer" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce dossier ?\');">
                            <i class="bi bi-trash-fill text-base"></i>
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
?>