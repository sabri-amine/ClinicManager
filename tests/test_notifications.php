<?php
// tests/test_notifications.php - Test end-to-end du système de notifications patient
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "========================================================\n";
echo "   TEST DU SYSTÈME DE NOTIFICATIONS PATIENT CLINICMANAGER\n";
echo "========================================================\n\n";

$testsTotal = 0;
$testsPassed = 0;
$testsFailed = 0;

function it(string $desc, callable $fn) {
    global $testsTotal, $testsPassed, $testsFailed;
    $testsTotal++;
    try {
        $res = $fn();
        if ($res !== false) {
            echo "  [OK] $desc\n";
            $testsPassed++;
        } else {
            echo "  [FAIL] $desc\n";
            $testsFailed++;
        }
    } catch (Throwable $e) {
        echo "  [ERROR] $desc -> " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// 1. Récupérer un patient et un médecin de test
$patient = $pdo->query('SELECT id, full_name, email FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'user\') LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$doctor = $pdo->query('SELECT id, full_name, email FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'doctor\') LIMIT 1')->fetch(PDO::FETCH_ASSOC);

it("Patient et Médecin de test identifiés", function() use ($patient, $doctor) {
    return !empty($patient['id']) && !empty($doctor['id']);
});

$patientId = (int)$patient['id'];
$doctorId = (int)$doctor['id'];

// 2. Création d'un rendez-vous de test
$testDate = date('Y-m-d H:i:s', strtotime('+5 days 10:00:00'));
$stmt = $pdo->prepare('
    INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, user_id, doctor_id, statut, is_confirmed)
    VALUES (\'TEST9999\', :date, \'10:00:00\', :uid, :docid, \'en_attente\', false)
    RETURNING id
');
$stmt->execute([':date' => $testDate, ':uid' => $patientId, ':docid' => $doctorId]);
$testRdvId = (int)$stmt->fetchColumn();

it("Création d'un rendez-vous de test (#$testRdvId)", function() use ($testRdvId) {
    return $testRdvId > 0;
});

// 3. Simulation Action Médecin : Confirmation
it("Action Médecin : Rendez-vous confirmé avec notification patient", function() use ($pdo, $patientId, $doctorId, $testRdvId) {
    $doctorName = "Dr. Test";
    $dateStr = date('d/m/Y');
    $titreNotif = "Rendez-vous confirmé";
    $msgNotif = "Votre rendez-vous prévu le {$dateStr} à 10:00 a été confirmé par {$doctorName}.";
    
    // Mettre à jour le rendez-vous
    $pdo->prepare("UPDATE rendez_vous SET statut = 'confirme', is_confirmed = true WHERE id = :id")->execute([':id' => $testRdvId]);
    
    // Ajouter notification
    addNotification($pdo, $patientId, $titreNotif, $msgNotif, 'success', $testRdvId);

    // Vérifier en base
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id AND type = 'success' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':uid' => $patientId, ':rdv_id' => $testRdvId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($notif) && $notif['titre'] === $titreNotif && $notif['is_read'] === false;
});

// 4. Test protection anti-doublon
it("Protection anti-doublon des notifications", function() use ($pdo, $patientId, $testRdvId) {
    $countBefore = (int)$pdo->query("SELECT COUNT(*) FROM notification WHERE rendez_vous_id = $testRdvId AND type = 'success'")->fetchColumn();
    // Tenter de réinsérer le même type de notification pour le même RDV
    addNotification($pdo, $patientId, "Rendez-vous confirmé", "Message doublon", 'success', $testRdvId);
    $countAfter = (int)$pdo->query("SELECT COUNT(*) FROM notification WHERE rendez_vous_id = $testRdvId AND type = 'success'")->fetchColumn();
    
    return $countBefore === $countAfter; // Aucun doublon inséré !
});

// 5. Simulation Action Médecin : Refus avec motif
it("Action Médecin : Rendez-vous refusé avec motif", function() use ($pdo, $patientId, $testRdvId) {
    $motif = "Absence pour intervention chirurgicale urgente.";
    $titreNotif = "Rendez-vous refusé";
    $msgNotif = "Votre rendez-vous n'a pas pu être validé. Motif : " . $motif;

    $pdo->prepare("UPDATE rendez_vous SET statut = 'refuse', note_medecin = :motif, is_confirmed = false WHERE id = :id")
        ->execute([':motif' => $motif, ':id' => $testRdvId]);

    addNotification($pdo, $patientId, $titreNotif, $msgNotif, 'danger', $testRdvId);

    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id AND type = 'danger' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':uid' => $patientId, ':rdv_id' => $testRdvId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($notif) && strpos($notif['message'], $motif) !== false;
});

// 6. Simulation Action Médecin : Indisponible
it("Action Médecin : Médecin indisponible", function() use ($pdo, $patientId, $testRdvId) {
    $titreNotif = "Médecin indisponible";
    $msgNotif = "Le médecin n'est pas disponible sur le créneau convenu. Veuillez choisir une autre date.";

    $pdo->prepare("UPDATE rendez_vous SET statut = 'indisponible', is_confirmed = false WHERE id = :id")
        ->execute([':id' => $testRdvId]);

    addNotification($pdo, $patientId, $titreNotif, $msgNotif, 'warning', $testRdvId);

    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id AND type = 'warning' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':uid' => $patientId, ':rdv_id' => $testRdvId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($notif) && $notif['titre'] === $titreNotif;
});

// 7. Simulation Action Médecin : Modification de date / heure
it("Action Médecin : Rendez-vous modifié (nouvelle date et heure)", function() use ($pdo, $patientId, $testRdvId) {
    $newDate = date('Y-m-d', strtotime('+7 days'));
    $newHeure = '14:30';
    $titreNotif = "Rendez-vous reporté / modifié";
    $msgNotif = "Votre rendez-vous a été reprogrammé au " . date('d/m/Y', strtotime($newDate)) . " à {$newHeure}.";

    $pdo->prepare("UPDATE rendez_vous SET statut = 'modifie', date_rendez_vous = :ndate, heure_rendez_vous = :nheure, is_confirmed = true WHERE id = :id")
        ->execute([':ndate' => $newDate . ' ' . $newHeure . ':00', ':nheure' => $newHeure . ':00', ':id' => $testRdvId]);

    addNotification($pdo, $patientId, $titreNotif, $msgNotif, 'info', $testRdvId);

    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id AND type = 'info' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':uid' => $patientId, ':rdv_id' => $testRdvId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($notif) && strpos($notif['message'], '14:30') !== false;
});

// 8. Test de marquage comme lu (unique)
it("Marquage d'une notification comme lue (is_read = true)", function() use ($pdo, $patientId, $testRdvId) {
    $stmt = $pdo->prepare("SELECT id FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id AND is_read = false LIMIT 1");
    $stmt->execute([':uid' => $patientId, ':rdv_id' => $testRdvId]);
    $notifId = (int)$stmt->fetchColumn();

    if (!$notifId) return false;

    $upd = $pdo->prepare("UPDATE notification SET is_read = true WHERE id = :id AND user_id = :uid");
    $upd->execute([':id' => $notifId, ':uid' => $patientId]);

    $check = $pdo->prepare("SELECT is_read FROM notification WHERE id = :id");
    $check->execute([':id' => $notifId]);
    return $check->fetchColumn() === true;
});

// 9. Test de marquage de toutes les notifications comme lues
it("Marquage de toutes les notifications du patient comme lues", function() use ($pdo, $patientId) {
    $upd = $pdo->prepare("UPDATE notification SET is_read = true WHERE user_id = :uid");
    $upd->execute([':uid' => $patientId]);

    $unread = (int)$pdo->query("SELECT COUNT(*) FROM notification WHERE user_id = $patientId AND is_read = false")->fetchColumn();
    return $unread === 0;
});

// 10. Nettoyage des données de test
$pdo->prepare("DELETE FROM notification WHERE rendez_vous_id = :rdv_id")->execute([':rdv_id' => $testRdvId]);
$pdo->prepare("DELETE FROM rendez_vous WHERE id = :rdv_id")->execute([':rdv_id' => $testRdvId]);

it("Nettoyage des données de test réussi", function() use ($pdo, $testRdvId) {
    $rdvExists = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE id = $testRdvId")->fetchColumn();
    return (int)$rdvExists === 0;
});

echo "\n--------------------------------------------------------\n";
echo "RÉSULTATS : Total: $testsTotal | Réussis: $testsPassed | Échoués: $testsFailed\n";
echo "--------------------------------------------------------\n";
if ($testsFailed === 0) {
    echo " TOUS LES TESTS DE NOTIFICATION SONT VALIDES !\n";
} else {
    echo " CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}

