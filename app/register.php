<?php
session_start();

// Si l'utilisateur est déjà connecté, on l'envoie sur l'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$erreur = "";
$succes = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validation de base
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifiant)) { // J'ai ajouté l'autorisations des underscores (_)
        $erreur = "L'identifiant doit être alphanumérique (les underscores sont autorisés).";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    }
    elseif ($password !== $confirm_password) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            // 2. Connexion PDO
            $pdo = new PDO("mysql:host=db;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 3. Vérification de l'existence dans la nouvelle table USERS
            $check = $pdo->prepare("SELECT USER_ID FROM USERS WHERE USER_NAME = :user OR EMAIL = :email");
            $check->execute(['user' => $identifiant, 'email' => $email]);
            
            if ($check->rowCount() > 0) {
                $erreur = "L'identifiant ou l'email est déjà utilisé.";
            } else {
                // 4. Préparation des données
                $type_user_defaut = 4; // Rôle 'Plaisancier' par défaut dans vos nouvelles données

                // --- MISE À JOUR : Insertion dans la table USERS ---
                // On utilise NOW() pour remplir la colonne CREATED_AT automatiquement
                $sql = "INSERT INTO USERS (USER_NAME, EMAIL, PASSWORD, CREATED_AT, TYPE_USER_ID) 
                        VALUES (:user, :email, :pass, NOW(), :type)";
                
                $insert = $pdo->prepare($sql);
                $insert->execute([
                    'user' => $identifiant,
                    'email' => $email,
                    'pass' => $password, // Mots de passe en clair pour vos tests
                    'type' => $type_user_defaut
                ]);

                $succes = "Compte créé avec succès ! Vous pouvez vous connecter.";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur technique : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Inscription</title>
    <link rel="stylesheet" href="css/style3.css?v=<?= time(); ?>">
</head>
<body class="login-page">
    <main class="main-content">
        <div class="login-card">
            <div class="logo-box">
                <img src="assets/logo%20pont.png" alt="Logo PontConnect">
            </div>
            <h1>Créer un compte</h1>
            <p class="intro">Rejoignez l'interface de gestion</p>

            <?php if (!empty($erreur)): ?>
                <div class="error-msg"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            <?php if (!empty($succes)): ?>
                <div class="success-msg"><?= htmlspecialchars($succes) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="identifiant">Identifiant</label>
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: marin_du_nord" value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="email">Adresse Email</label>
                    <input type="email" id="email" name="email" required placeholder="Ex: marin@port-dunkerque.fr" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <span class="password-hint">Pour vos tests actuels, n'importe quel mot de passe fonctionne.</span>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirmation</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-submit">S'inscrire</button>
            </form>
            <a href="login.php" class="back-link">← Déjà un compte ? Se connecter</a>
        </div>
    </main>
</body>
</html>