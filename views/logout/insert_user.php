
<?php
session_start();
require_once('../../db.php');

if (empty($_SESSION['csrf_token'])) {
    die("Le token CSRF n'est pas défini.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: logout.php');
    exit;
}

if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Token CSRF invalide.");
}

$fullname = trim($_POST['fullname'] ?? '');
$email = filter_var(
    trim($_POST['email'] ?? ''),
    FILTER_VALIDATE_EMAIL
);
$password = $_POST['password'] ?? '';

if ($fullname === '' || !$email || $password === '') {
    die("Veuillez remplir correctement tous les champs.");
}

try {
    // Récupérer le rôle user
    $roleStmt = $pdo->prepare(
        'SELECT id FROM "role" WHERE name = :name'
    );
    $roleName = 'user';
    $roleStmt->execute(['name' => $roleName]);
    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        die("Le rôle user n'existe pas dans la table role.");
    }

    // Vérifier si l'email existe déjà
    $checkStmt = $pdo->prepare(
        'SELECT id FROM "user" WHERE email = :email'
    );
    $checkStmt->execute(['email' => $email]);

    if ($checkStmt->fetch()) {
        die("Cette adresse email est déjà utilisée.");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Créer le compte
    $sql = 'INSERT INTO "user"
            (full_name, email, password, role_id, status, is_confirmed)
            VALUES
            (:full_name, :email, :password, :role_id, :status, :is_confirmed)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'full_name' => $fullname,
        'email' => $email,
        'password' => $hashedPassword,
        'role_id' => $role['id'],
        'status' => 'actif',
        'is_confirmed' => 'false'
    ]);

    $_SESSION['success_message'] =
        "Inscription réussie ! Votre compte doit être confirmé avant connexion.";

    unset($_SESSION['csrf_token']);

    header('Location: ../login/login.php');
    exit;

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>
