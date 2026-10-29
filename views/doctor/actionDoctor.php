<?php
// views/doctor/actionDoctor.php - Traitement ajout dossier médical, ordonnance & chambre
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: doctor.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    die("Session expirée ou jeton CSRF invalide.");
}

$doctorId     = (int)($_SESSION['user_id'] ?? 0);
$patientId    = filter_input(INPUT_POST, 'patient', FILTER_VALIDATE_INT);
$dateDossier  = trim($_POST['date_dossier'] ?? '');
$diagnostic   = trim($_POST['diagnostic'] ?? '');
$traitement   = trim($_POST['traitement'] ?? '');

$medicaments  = trim($_POST['medicaments'] ?? '');
$posologie    = trim($_POST['posologie'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');

$chambreNum   = trim($_POST['num'] ?? '');
$chambreType  = trim($_POST['type'] ?? '');
$chambreStatut= trim($_POST['statut'] ?? 'Disponible');

if (!$patientId || empty($dateDossier) || empty($diagnostic) || empty($traitement)) {
    die("Le patient, la date, le diagnostic et le traitement sont obligatoires.");
}

try {
    $pdo->beginTransaction();

    // 1. Insertion du dossier médical
    $sql1 = '
        INSERT INTO dossier_medical (date_dossie, diagnostic, traitement, user_id, doctor_id)
        VALUES (:date_dossie, :diagnostic, :traitement, :user_id, :doctor_id)
        RETURNING dossier_id
    ';
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([
        ':date_dossie' => $dateDossier,
        ':diagnostic'  => $diagnostic,
        ':traitement'  => $traitement,
        ':user_id'     => $patientId,
        ':doctor_id'   => $doctorId
    ]);
    
    $dossierId = $stmt1->fetchColumn();

    if (!$dossierId) {
        throw new Exception("Impossible de créer le dossier médical.");
    }

    // 2. Attribution d'une chambre (si renseignée)
    if (!empty($chambreNum)) {
        $sqlChambre = '
            INSERT INTO chambre (num, type, statut, dossier_id)
            VALUES (:num, :type, :statut, :dossier_id)
        ';
        $stmtChambre = $pdo->prepare($sqlChambre);
        $stmtChambre->execute([
            ':num'        => (int)$chambreNum,
            ':type'       => $chambreType ?: 'Simple',
            ':statut'     => $chambreStatut ?: 'Occupée',
            ':dossier_id' => $dossierId
        ]);
    }

    // 3. Création de l'ordonnance (si renseignée)
    if (!empty($medicaments)) {
        $sqlOrd = '
            INSERT INTO ordonnance (dossier_id, doctor_id, user_id, medicaments, posologie, instructions)
            VALUES (:dossier_id, :doctor_id, :user_id, :medicaments, :posologie, :instructions)
        ';
        $stmtOrd = $pdo->prepare($sqlOrd);
        $stmtOrd->execute([
            ':dossier_id'   => $dossierId,
            ':doctor_id'    => $doctorId,
            ':user_id'      => $patientId,
            ':medicaments'  => $medicaments,
            ':posologie'    => $posologie,
            ':instructions' => $instructions
        ]);
    }

    // 4. Notification au patient
    $docName = $_SESSION['full_name'] ?? 'Votre médecin';
    addNotification(
        $pdo,
        $patientId,
        "Nouveau dossier médical & prescription",
        "Le Dr. $docName a enregistré votre compte-rendu médical de consultation du $dateDossier.",
        "dossier"
    );

    $pdo->commit();
    header("Location: doctor.php?created=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur création dossier médical : " . $e->getMessage());
    die("Une erreur est survenue lors de l'enregistrement : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
