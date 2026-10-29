<?php
// tests/test_appointment_proposals.php - Suite de tests complets des propositions de créneaux et anti-double réservation
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=================================================================\n";
echo "   CLINICMANAGER - TESTS DES PROPOSITIONS & ANTI-DOUBLE RÉSERVATION\n";
echo "=================================================================\n\n";

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

// Récupérer un médecin de test et deux patients
$doctor = $pdo->query('SELECT id, full_name FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'doctor\') LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$patients = $pdo->query('SELECT id, full_name FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'user\') LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);

$doctorId = (int)$doctor['id'];
$patientAId = (int)$patients[0]['id'];
$patientBId = (int)($patients[1]['id'] ?? $patients[0]['id']);

$testDate1 = date('Y-m-d', strtotime('+10 days'));
$testDate2 = date('Y-m-d', strtotime('+11 days'));

// 1. Patient A réserve le créneau testDate1 de 09:00 à 10:00
$rdvAId = null;
it("Patient A réserve un créneau ($testDate1 de 09:00 à 10:00)", function() use ($pdo, $doctorId, $patientAId, $testDate1, &$rdvAId) {
    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_A\', :date_rdv, \'09:00:00\', \'10:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $stmt->execute([
        ':date_rdv' => $testDate1 . ' 09:00:00',
        ':uid'      => $patientAId,
        ':doc_id'   => $doctorId
    ]);
    $rdvAId = (int)$stmt->fetchColumn();
    return $rdvAId > 0;
});

// 2. Vérification côté serveur : isDoctorSlotAvailable bloque la réservation du même créneau
it("Serveur PHP : isDoctorSlotAvailable détecte le créneau occupé pour Patient B", function() use ($pdo, $doctorId, $testDate1) {
    $available = isDoctorSlotAvailable($pdo, $doctorId, $testDate1, '09:00', '10:00');
    return $available === false;
});

// 3. PostgreSQL Trigger : Tentative d'insertion directe bloquée par le trigger PostgreSQL
it("PostgreSQL Trigger : Interdiction stricte de double réservation par le trigger", function() use ($pdo, $doctorId, $patientBId, $testDate1) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
            VALUES (\'CNI_B\', :date_rdv, \'09:00:00\', \'10:00:00\', :uid, :doc_id, \'en_attente\', false)
        ');
        $stmt->execute([
            ':date_rdv' => $testDate1 . ' 09:00:00',
            ':uid'      => $patientBId,
            ':doc_id'   => $doctorId
        ]);
        return false; // Devait lever une exception !
    } catch (PDOException $e) {
        return strpos($e->getMessage(), 'Double réservation détectée') !== false;
    }
});

// 4. Patient B réserve le créneau consécutif (10:00 à 11:00) : autorisé sans conflit
$rdvBId = null;
it("Patient B réserve un créneau consécutif autorisé ($testDate1 de 10:00 à 11:00)", function() use ($pdo, $doctorId, $patientBId, $testDate1, &$rdvBId) {
    $isFree = isDoctorSlotAvailable($pdo, $doctorId, $testDate1, '10:00', '11:00');
    if (!$isFree) return false;

    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_B\', :date_rdv, \'10:00:00\', \'11:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $stmt->execute([
        ':date_rdv' => $testDate1 . ' 10:00:00',
        ':uid'      => $patientBId,
        ':doc_id'   => $doctorId
    ]);
    $rdvBId = (int)$stmt->fetchColumn();
    return $rdvBId > 0;
});

// 5. Le médecin ne peut pas recevoir Patient A et lui propose le créneau testDate2 de 09:00 à 10:00
it("Médecin propose une nouvelle date et heure pour Patient A ($testDate2 de 09:00 à 10:00)", function() use ($pdo, $doctorId, $rdvAId, $testDate2, $patientAId) {
    // Vérifier disponibilité
    $free = isDoctorSlotAvailable($pdo, $doctorId, $testDate2, '09:00', '10:00', $rdvAId);
    if (!$free) return false;

    $upd = $pdo->prepare('
        UPDATE rendez_vous
        SET statut = \'propose\',
            date_proposee = :pdate,
            heure_debut_proposee = \'09:00:00\',
            heure_fin_proposee = \'10:00:00\',
            note_medecin = \'Indisponible le matin, proposé le lendemain\'
        WHERE id = :id
    ');
    $upd->execute([':pdate' => $testDate2, ':id' => $rdvAId]);

    // Notification patient
    addNotification($pdo, $patientAId, "Nouveau créneau proposé", "Dr propose $testDate2 09h-10h", "info", $rdvAId);

    $check = $pdo->query("SELECT statut, date_proposee FROM rendez_vous WHERE id = $rdvAId")->fetch(PDO::FETCH_ASSOC);
    return $check['statut'] === 'propose' && $check['date_proposee'] === $testDate2;
});

// 6. Tant que la proposition est en attente, le créneau proposé est verrouillé pour les autres
it("Verrouillage du créneau proposé ($testDate2 de 09:00 à 10:00) : inaccessible aux tiers", function() use ($pdo, $doctorId, $testDate2) {
    $free = isDoctorSlotAvailable($pdo, $doctorId, $testDate2, '09:00', '10:00');
    return $free === false; // Détecté comme occupé car déjà proposé !
});

// 7. Patient A accepte le créneau proposé
it("Patient A accepte la proposition : le rendez-vous devient confirmé sur la nouvelle date", function() use ($pdo, $rdvAId, $testDate2, $patientAId) {
    $upd = $pdo->prepare('
        UPDATE rendez_vous
        SET statut = \'confirme\',
            is_confirmed = true,
            date_rendez_vous = :ndatetime,
            heure_rendez_vous = \'09:00:00\',
            heure_fin = \'10:00:00\',
            date_proposee = NULL,
            heure_debut_proposee = NULL,
            heure_fin_proposee = NULL
        WHERE id = :id
    ');
    $upd->execute([
        ':ndatetime' => $testDate2 . ' 09:00:00',
        ':id'        => $rdvAId
    ]);

    $check = $pdo->query("SELECT statut, is_confirmed, date_rendez_vous::date as dt FROM rendez_vous WHERE id = $rdvAId")->fetch(PDO::FETCH_ASSOC);
    return $check['statut'] === 'confirme' && $check['is_confirmed'] === true && $check['dt'] === $testDate2;
});

// 8. L'ancien créneau ($testDate1 09:00 à 10:00) est maintenant libéré pour d'autres patients
it("Libération de l'ancien créneau initial ($testDate1 de 09:00 à 10:00)", function() use ($pdo, $doctorId, $testDate1) {
    $free = isDoctorSlotAvailable($pdo, $doctorId, $testDate1, '09:00', '10:00');
    return $free === true; // L'ancien créneau est de nouveau libre !
});

// 9. Test du refus de proposition par un patient
it("Patient décline une proposition : le créneau proposé est immédiatement libéré", function() use ($pdo, $doctorId, $patientBId, $testDate1) {
    // Mettre rdvB en 'propose' sur un créneau test
    $propDateTest = date('Y-m-d', strtotime('+15 days'));
    $pdo->prepare('UPDATE rendez_vous SET statut = \'propose\', date_proposee = :pd, heure_debut_proposee = \'14:00:00\', heure_fin_proposee = \'15:00:00\' WHERE id = :id')
        ->execute([':pd' => $propDateTest, ':id' => $GLOBALS['rdvBId']]);

    // Vérifier que le créneau est verrouillé
    $locked = !isDoctorSlotAvailable($pdo, $doctorId, $propDateTest, '14:00', '15:00');

    // Le patient refuse
    $pdo->prepare('UPDATE rendez_vous SET statut = \'refuse_par_patient\', date_proposee = NULL, heure_debut_proposee = NULL, heure_fin_proposee = NULL WHERE id = :id')
        ->execute([':id' => $GLOBALS['rdvBId']]);

    // Vérifier que le créneau est désormais libéré
    $released = isDoctorSlotAvailable($pdo, $doctorId, $propDateTest, '14:00', '15:00');

    return $locked && $released;
});

// Nettoyage des données de test
if ($rdvAId) {
    $pdo->prepare('DELETE FROM notification WHERE rendez_vous_id = :id')->execute([':id' => $rdvAId]);
    $pdo->prepare('DELETE FROM rendez_vous WHERE id = :id')->execute([':id' => $rdvAId]);
}
if ($rdvBId) {
    $pdo->prepare('DELETE FROM notification WHERE rendez_vous_id = :id')->execute([':id' => $rdvBId]);
    $pdo->prepare('DELETE FROM rendez_vous WHERE id = :id')->execute([':id' => $rdvBId]);
}

it("Nettoyage des réservations de test", function() use ($pdo, $rdvAId, $rdvBId) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE id IN ($rdvAId, $rdvBId)")->fetchColumn();
    return (int)$cnt === 0;
});

echo "\n-----------------------------------------------------------------\n";
echo "RÉSULTATS : Total: $testsTotal | Réussis: $testsPassed | Échoués: $testsFailed\n";
echo "-----------------------------------------------------------------\n";
if ($testsFailed === 0) {
    echo " TOUS LES TESTS DE PROPOSITIONS ET ANTI-DOUBLE RÉSERVATION SONT VALIDES !\n";
} else {
    echo " CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}

