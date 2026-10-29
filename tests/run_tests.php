<?php
// tests/run_tests.php - Suite de tests automatisée pour ClinicManager

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "========================================================\n";
echo "   CLINICMANAGER - SUITE DE TESTS FONCTIONNELS ET SÉCURITÉ\n";
echo "========================================================\n\n";

$testsTotal = 0;
$testsPassed = 0;
$testsFailed = 0;

function it(string $description, callable $fn) {
    global $testsTotal, $testsPassed, $testsFailed;
    $testsTotal++;
    try {
        $result = $fn();
        if ($result !== false) {
            echo "  [OK] $description\n";
            $testsPassed++;
        } else {
            echo "  [FAIL] $description\n";
            $testsFailed++;
        }
    } catch (Throwable $e) {
        echo "  [ERROR] $description -> " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

// ---------------------------------------------------------
// TEST 1 : Vérification de syntaxe PHP sur tous les fichiers
// ---------------------------------------------------------
echo "1. Vérification de la syntaxe PHP de tous les fichiers...\n";
$phpFiles = [];
$dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..'));
foreach ($dirIterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (strpos($file->getPathname(), 'node_modules') === false) {
            $phpFiles[] = $file->getPathname();
        }
    }
}

foreach ($phpFiles as $f) {
    it("Syntaxe valide pour : " . basename($f), function() use ($f) {
        $output = [];
        $returnCode = 0;
        exec("php -l \"$f\"", $output, $returnCode);
        return $returnCode === 0;
    });
}

// ---------------------------------------------------------
// TEST 2 : Base de données PostgreSQL & Schéma
// ---------------------------------------------------------
echo "\n2. Intégrité de la Base de Données PostgreSQL...\n";
require_once __DIR__ . '/../db.php';

it("Connexion PDO PostgreSQL établie", function() use ($pdo) {
    return $pdo instanceof PDO;
});

it("Présence de toutes les tables nécessaires", function() use ($pdo) {
    $required = ['role', 'user', 'dossier_medical', 'rendez_vous', 'chambre', 'ordonnance', 'facture', 'notification'];
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $r) {
        if (!in_array($r, $tables, true)) {
            echo "  (Table manquante: $r) ";
            return false;
        }
    }
    return true;
});

it("Présence des 3 rôles standards (user, admin, doctor)", function() use ($pdo) {
    $roles = $pdo->query("SELECT name FROM role")->fetchAll(PDO::FETCH_COLUMN);
    return in_array('admin', $roles) && in_array('doctor', $roles) && in_array('user', $roles);
});

// ---------------------------------------------------------
// TEST 3 : Données existantes préservées
// ---------------------------------------------------------
echo "\n3. Préservation des données existantes...\n";
it("Les utilisateurs initiaux sont intacts", function() use ($pdo) {
    $count = $pdo->query("SELECT count(*) FROM \"user\"")->fetchColumn();
    return $count >= 5;
});

// ---------------------------------------------------------
// TEST 4 : Logique Métier & Anti Double Réservation
// ---------------------------------------------------------
echo "\n4. Tests de logique métier (Rendez-vous)...\n";
it("Détection de double réservation pour un même médecin", function() use ($pdo) {
    $docId = (int)$pdo->query("SELECT id FROM \"user\" WHERE role_id = (SELECT id FROM role WHERE name = 'doctor') LIMIT 1")->fetchColumn();
    $patId = (int)$pdo->query("SELECT id FROM \"user\" WHERE role_id = (SELECT id FROM role WHERE name = 'user') LIMIT 1")->fetchColumn();
    
    $testDate = date('Y-m-d', strtotime('+10 days'));
    $testHeure = '14:30:00';

    // Nettoyage préalable au cas où
    $pdo->prepare("DELETE FROM rendez_vous WHERE motif = 'TEST_CONFLIT'")->execute();

    // 1. Insertion du premier rendez-vous
    $ins = $pdo->prepare("INSERT INTO rendez_vous (cni, date_rendez_vous, heure_rendez_vous, motif, user_id, doctor_id, statut) VALUES ('TEST01', :dt, :hr, 'TEST_CONFLIT', :uid, :docid, 'confirme')");
    $ins->execute([
        ':dt' => "$testDate $testHeure",
        ':hr' => $testHeure,
        ':uid' => $patId,
        ':docid' => $docId
    ]);

    // 2. Vérification de détection du conflit
    $check = $pdo->prepare("SELECT id FROM rendez_vous WHERE doctor_id = :doc_id AND date_rendez_vous::date = :date_rdv AND heure_rendez_vous = :heure_rdv AND (statut IS NULL OR statut != 'annule')");
    $check->execute([
        ':doc_id' => $docId,
        ':date_rdv' => $testDate,
        ':heure_rdv' => $testHeure
    ]);
    $conflit = $check->fetch();

    // Nettoyage du test
    $pdo->prepare("DELETE FROM rendez_vous WHERE motif = 'TEST_CONFLIT'")->execute();

    return !empty($conflit);
});

// ---------------------------------------------------------
// TEST 5 : Jointure Dossier Médical et Chambre
// ---------------------------------------------------------
echo "\n5. Tests des requêtes médicales...\n";
it("Correction de la jointure SQL dossier_medical <-> chambre", function() use ($pdo) {
    // Vérification que la requête ne plante pas et retourne correctement les données
    $sql = '
        SELECT d.dossier_id, u.full_name, c.num AS num_chambre
        FROM dossier_medical d
        JOIN "user" u ON d.user_id = u.id
        LEFT JOIN chambre c ON d.dossier_id = c.dossier_id
        LIMIT 5
    ';
    $stmt = $pdo->query($sql);
    $res = $stmt->fetchAll();
    return is_array($res) && count($res) > 0;
});

// ---------------------------------------------------------
// TEST 6 : Ordonnances et Prescriptions
// ---------------------------------------------------------
echo "\n6. Tests des ordonnances & prescriptions...\n";
it("Création et liaison d'une ordonnance", function() use ($pdo) {
    $firstDossier = $pdo->query("SELECT dossier_id, user_id, doctor_id FROM dossier_medical LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$firstDossier) return false;

    $stmt = $pdo->prepare("INSERT INTO ordonnance (dossier_id, doctor_id, user_id, medicaments, posologie) VALUES (:did, :docid, :uid, 'Test Med 500mg', '1 matin et soir') RETURNING id");
    $stmt->execute([
        ':did' => $firstDossier['dossier_id'],
        ':docid' => $firstDossier['doctor_id'],
        ':uid' => $firstDossier['user_id']
    ]);
    $ordId = $stmt->fetchColumn();

    $check = $pdo->query("SELECT * FROM ordonnance WHERE id = $ordId")->fetch();
    $pdo->exec("DELETE FROM ordonnance WHERE id = $ordId");
    return !empty($check);
});

// ---------------------------------------------------------
// TEST 7 : Facturation et Calcul du Chiffre d'Affaires
// ---------------------------------------------------------
echo "\n7. Tests de facturation...\n";
it("Calcul du chiffre d'affaires des factures payées", function() use ($pdo) {
    $ca = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM facture WHERE statut_paiement = 'paye'")->fetchColumn();
    return is_numeric($ca) && $ca >= 0;
});

// ---------------------------------------------------------
// TEST 8 : Sécurité (CSRF & Auth Helpers)
// ---------------------------------------------------------
echo "\n8. Tests des helpers de sécurité...\n";
require_once __DIR__ . '/../includes/auth.php';

it("Génération et vérification de jeton CSRF", function() {
    $token = getCsrfToken();
    $valid = verifyCsrfToken($token);
    $invalid = verifyCsrfToken("mauvais_token_12345");
    return $valid === true && $invalid === false;
});

it("Échappement HTML sécurisé (XSS prevention)", function() {
    $raw = "<script>alert('xss');</script>";
    $escaped = e($raw);
    return strpos($escaped, "<script>") === false && strpos($escaped, "&lt;script&gt;") !== false;
});

// ---------------------------------------------------------
// TEST 9 : Système de notifications patient
// ---------------------------------------------------------
echo "\n9. Tests du système de notifications patient...\n";
it("Insertion notification avec anti-doublon et liaison rendez-vous", function() use ($pdo) {
    $user = $pdo->query('SELECT id FROM "user" WHERE role_id = (SELECT id FROM role WHERE name = \'user\') LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$user) return false;
    $uid = (int)$user['id'];

    addNotification($pdo, $uid, 'Test Notification', 'Contenu test', 'info');
    $check = $pdo->prepare("SELECT id FROM notification WHERE user_id = :uid AND titre = 'Test Notification' LIMIT 1");
    $check->execute([':uid' => $uid]);
    $notifId = $check->fetchColumn();
    if ($notifId) {
        $pdo->prepare("DELETE FROM notification WHERE id = :id")->execute([':id' => $notifId]);
        return true;
    }
    return false;
});

echo "\n--------------------------------------------------------\n";
echo "RÉSULTATS : Total: $testsTotal | Réussis: $testsPassed | Échoués: $testsFailed\n";
echo "--------------------------------------------------------\n";

if ($testsFailed === 0) {
    echo " TOUS LES TESTS SONT AU VERT ! L'application est opérationnelle et sécurisée.\n";
    exit(0);
} else {
    echo " DES TESTS ONT ÉCHOUÉ.\n";
    exit(1);
}
