<?php
// tests/test_single_active_rdv.php - Tests complets : un seul RDV actif par médecin, fixation du créneau par le médecin, notification et non-chevauchement
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=================================================================\n";
echo "   CLINICMANAGER - TESTS DU NOUVEAU SYSTÈME DE RENDEZ-VOUS\n";
echo "   (Un seul RDV actif / Fixation par le médecin / Notifications)\n";
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

// 1. Récupération du médecin et création de patients de test dédiés
$docStmt = $pdo->query('SELECT id, full_name FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'doctor\') LIMIT 1');
$doctor = $docStmt->fetch(PDO::FETCH_ASSOC);

it("Présence d'au moins un médecin pour les tests", function() use ($doctor) {
    return !empty($doctor['id']);
});

$doctorId = (int)$doctor['id'];

// Nettoyage préventif des anciens utilisateurs de test
$pdo->exec("DELETE FROM notification WHERE user_id IN (SELECT id FROM \"user\" WHERE email LIKE '%_test_rdv@clinicmanager.com')");
$pdo->exec("DELETE FROM rendez_vous WHERE user_id IN (SELECT id FROM \"user\" WHERE email LIKE '%_test_rdv@clinicmanager.com')");
$pdo->exec("DELETE FROM \"user\" WHERE email LIKE '%_test_rdv@clinicmanager.com'");

// Créer deux patients dédiés pour garantir un environnement de test isolé et prédictible
$insPA = $pdo->prepare('
    INSERT INTO "user" (full_name, email, password, role_id, status)
    VALUES (\'Patient Test A\', \'patient_a_test_rdv@clinicmanager.com\', \'$2y$10$abcdef\', (SELECT id FROM role WHERE name = \'user\'), \'actif\')
    RETURNING id
');
$insPA->execute();
$patientAId = (int)$insPA->fetchColumn();

$insPB = $pdo->prepare('
    INSERT INTO "user" (full_name, email, password, role_id, status)
    VALUES (\'Patient Test B\', \'patient_b_test_rdv@clinicmanager.com\', \'$2y$10$abcdef\', (SELECT id FROM role WHERE name = \'user\'), \'actif\')
    RETURNING id
');
$insPB->execute();
$patientBId = (int)$insPB->fetchColumn();

// Créer temporairement un second médecin si un seul médecin existe
$doctor2Id = null;
$createdTempDoctor2 = false;
$doc2Stmt = $pdo->query("SELECT id FROM \"user\" WHERE role_id = (SELECT id FROM role WHERE name = 'doctor') AND id != $doctorId LIMIT 1");
$doc2 = $doc2Stmt->fetch(PDO::FETCH_ASSOC);
if ($doc2) {
    $doctor2Id = (int)$doc2['id'];
} else {
    $insDoc = $pdo->prepare('
        INSERT INTO "user" (full_name, email, password, role_id, status)
        VALUES (\'Dr Second Test\', \'dr2_test_unique@clinicmanager.com\', \'$2y$10$abcdef\', (SELECT id FROM role WHERE name = \'doctor\'), \'actif\')
        RETURNING id
    ');
    $insDoc->execute();
    $doctor2Id = (int)$insDoc->fetchColumn();
    $createdTempDoctor2 = true;
}

$rdvAId = null;
$testDate = date('Y-m-d', strtotime('+12 days'));

// 2. Patient A soumet sa première demande chez le Dr 1 (En attente)
it("Patient A soumet une demande de rendez-vous chez Dr 1 (statut: en_attente)", function() use ($pdo, $patientAId, $doctorId, $testDate, &$rdvAId) {
    // S'assurer qu'il n'a pas d'ancien RDV de test résiduel
    $pdo->prepare("DELETE FROM rendez_vous WHERE user_id = :uid AND doctor_id = :did AND cni LIKE 'CNI_TEST%'")->execute([':uid' => $patientAId, ':did' => $doctorId]);

    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_TEST_A1\', :dt, \'09:00:00\', \'10:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $stmt->execute([':dt' => $testDate . ' 09:00:00', ':uid' => $patientAId, ':doc_id' => $doctorId]);
    $rdvAId = (int)$stmt->fetchColumn();
    return $rdvAId > 0;
});

// 3. Règle 1 : Patient A tente de créer une DEUXIÈME demande chez le même Dr 1 -> BLOQUÉ côté serveur
it("Serveur PHP : hasActiveAppointmentWithDoctor bloque un 2ème RDV actif chez le même médecin", function() use ($pdo, $patientAId, $doctorId) {
    $hasActive = hasActiveAppointmentWithDoctor($pdo, $patientAId, $doctorId);
    return $hasActive !== false;
});

// 4. Règle 1 : PostgreSQL bloque également via l'index unique partiel
it("PostgreSQL : index uq_active_rdv_patient_doctor bloque l'insertion d'un 2ème RDV actif", function() use ($pdo, $patientAId, $doctorId, $testDate) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
            VALUES (\'CNI_TEST_A2\', :dt, \'11:00:00\', \'12:00:00\', :uid, :doc_id, \'en_attente\', false)
        ');
        $stmt->execute([':dt' => $testDate . ' 11:00:00', ':uid' => $patientAId, ':doc_id' => $doctorId]);
        return false; // Échec du test car l'insertion devait échouer !
    } catch (PDOException $e) {
        return strpos($e->getMessage(), 'uq_active_rdv_patient_doctor') !== false;
    }
});

// 5. Patient A peut prendre un rendez-vous chez un AUTRE médecin (Dr 2)
$rdvA2Id = null;
it("Patient A peut créer un rendez-vous chez un AUTRE médecin (Dr 2)", function() use ($pdo, $patientAId, $doctor2Id, $testDate, &$rdvA2Id) {
    $hasActiveWithDoc2 = hasActiveAppointmentWithDoctor($pdo, $patientAId, $doctor2Id);
    if ($hasActiveWithDoc2) {
        $pdo->prepare("DELETE FROM rendez_vous WHERE user_id = :uid AND doctor_id = :did")->execute([':uid' => $patientAId, ':did' => $doctor2Id]);
    }

    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_TEST_A_DOC2\', :dt, \'14:00:00\', \'15:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $stmt->execute([':dt' => $testDate . ' 14:00:00', ':uid' => $patientAId, ':doc_id' => $doctor2Id]);
    $rdvA2Id = (int)$stmt->fetchColumn();
    return $rdvA2Id > 0;
});

// 6. Le médecin examine la demande du Patient A, l'accepte et fixe la date et l'heure exactes
it("Médecin accepte la demande et fixe la date et l'heure (statut devient confirmé)", function() use ($pdo, $rdvAId, $doctorId, $patientAId, $testDate, $doctor) {
    $dateFixee = $testDate;
    $heureDebut = '10:00:00';
    $heureFin = '11:00:00';

    // Vérifier disponibilité
    $free = isDoctorSlotAvailable($pdo, $doctorId, $dateFixee, $heureDebut, $heureFin, $rdvAId);
    if (!$free) return false;

    // Enregistrer acceptation & fixation
    $pdo->prepare('
        UPDATE rendez_vous
        SET statut = \'confirme\',
            is_confirmed = true,
            date_rendez_vous = :dt,
            heure_rendez_vous = :deb,
            heure_fin = :fin
        WHERE id = :id
    ')->execute([
        ':dt'  => $dateFixee . ' ' . $heureDebut,
        ':deb' => $heureDebut,
        ':fin' => $heureFin,
        ':id'  => $rdvAId
    ]);

    // Notification automatique exacte requise
    $dateFr = date('d/m/Y', strtotime($dateFixee));
    $heureFr = substr($heureDebut, 0, 5);
    $titre = "Demande de rendez-vous acceptée";
    $msg = "Votre demande de rendez-vous a été acceptée. Votre rendez-vous avec le Dr " . $doctor['full_name'] . " est prévu le $dateFr à $heureFr.";

    addNotification($pdo, $patientAId, $titre, $msg, 'success', $rdvAId);

    $check = $pdo->query("SELECT statut, is_confirmed, heure_rendez_vous FROM rendez_vous WHERE id = $rdvAId")->fetch(PDO::FETCH_ASSOC);
    return $check['statut'] === 'confirme' && $check['is_confirmed'] === true && $check['heure_rendez_vous'] === '10:00:00';
});

// 7. Vérification de la notification reçue par le patient dans PostgreSQL
it("Notification automatique de confirmation enregistrée dans PostgreSQL pour le patient", function() use ($pdo, $patientAId, $rdvAId) {
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE user_id = :uid AND rendez_vous_id = :rdv_id ORDER BY id DESC LIMIT 1");
    $stmt->execute([':uid' => $patientAId, ':rdv_id' => $rdvAId]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($notif) && strpos($notif['message'], 'acceptée') !== false && strpos($notif['message'], '10:00') !== false;
});

// 8. Le médecin ne peut JAMAIS confirmer deux rendez-vous qui se chevauchent
it("Interdiction de chevauchement : impossible de confirmer un autre RDV sur le même créneau", function() use ($pdo, $doctorId, $patientBId, $testDate) {
    // Créer une demande pour patient B
    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_TEST_B1\', :dt, \'10:00:00\', \'11:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $stmt->execute([':dt' => $testDate . ' 10:00:00', ':uid' => $patientBId, ':doc_id' => $doctorId]);
    $rdvBId = (int)$stmt->fetchColumn();

    // 1. isDoctorSlotAvailable doit renvoyer false pour ce créneau (10h-11h)
    $available = isDoctorSlotAvailable($pdo, $doctorId, $testDate, '10:00', '11:00', $rdvBId);

    // 2. Si on tente de forcer le statut 'confirme' sur 10h-11h, le trigger PostgreSQL doit lever une exception
    $triggerBlocked = false;
    try {
        $pdo->prepare("UPDATE rendez_vous SET statut = 'confirme', is_confirmed = true WHERE id = :id")->execute([':id' => $rdvBId]);
    } catch (PDOException $e) {
        $triggerBlocked = strpos($e->getMessage(), 'Double réservation détectée') !== false;
    }

    // Nettoyage rdvB
    $pdo->prepare("DELETE FROM rendez_vous WHERE id = :id")->execute([':id' => $rdvBId]);

    return ($available === false) && $triggerBlocked;
});

// 9. Une fois le rendez-vous terminé (statut 'termine'), le patient peut en reprendre un nouveau chez le même médecin
it("Consultation terminée : le patient peut à nouveau demander un rendez-vous chez Dr 1", function() use ($pdo, $rdvAId, $patientAId, $doctorId, $testDate) {
    // Marquer rdvA comme terminé
    $pdo->prepare("UPDATE rendez_vous SET statut = 'termine' WHERE id = :id")->execute([':id' => $rdvAId]);

    // hasActiveAppointmentWithDoctor doit maintenant être libre (false)
    $hasActive = hasActiveAppointmentWithDoctor($pdo, $patientAId, $doctorId);

    // Patient A peut désormais insérer une nouvelle demande
    $stmt = $pdo->prepare('
        INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, heure_fin, user_id, doctor_id, statut, is_confirmed)
        VALUES (\'CNI_TEST_A_NEW\', :dt, \'16:00:00\', \'17:00:00\', :uid, :doc_id, \'en_attente\', false)
        RETURNING id
    ');
    $newDate = date('Y-m-d', strtotime('+15 days'));
    $stmt->execute([':dt' => $newDate . ' 16:00:00', ':uid' => $patientAId, ':doc_id' => $doctorId]);
    $newRdvId = (int)$stmt->fetchColumn();

    // Nettoyer ce nouveau rendez-vous
    $pdo->prepare("DELETE FROM rendez_vous WHERE id = :id")->execute([':id' => $newRdvId]);

    return ($hasActive === false) && ($newRdvId > 0);
});

// Nettoyage complet des données de test
$pdo->prepare("DELETE FROM notification WHERE user_id IN (:pa, :pb)")->execute([':pa' => $patientAId, ':pb' => $patientBId]);
$pdo->prepare("DELETE FROM rendez_vous WHERE user_id IN (:pa, :pb)")->execute([':pa' => $patientAId, ':pb' => $patientBId]);
$pdo->prepare('DELETE FROM "user" WHERE id IN (:pa, :pb)')->execute([':pa' => $patientAId, ':pb' => $patientBId]);

if ($createdTempDoctor2 && $doctor2Id) {
    $pdo->prepare('DELETE FROM "user" WHERE id = :id')->execute([':id' => $doctor2Id]);
}

it("Nettoyage des données de test", function() use ($pdo, $patientAId) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM \"user\" WHERE id = $patientAId")->fetchColumn();
    return (int)$cnt === 0;
});

echo "\n-----------------------------------------------------------------\n";
echo "RÉSULTATS : Total: $testsTotal | Réussis: $testsPassed | Échoués: $testsFailed\n";
echo "-----------------------------------------------------------------\n";
if ($testsFailed === 0) {
    echo " TOUS LES TESTS SONT VALIDES AVEC SUCCÈS !\n";
} else {
    echo " CERTAINS TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}
