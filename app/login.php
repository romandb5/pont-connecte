<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($identifiant) && !empty($password)) {
        try {
            // Configuration PDO
            $host = 'db';
            $db   = 'pontconnecte';
            $user = 'Etudiant';
            $pass = 'P@ssword';

            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // --- MISE À JOUR : On interroge la nouvelle table USERS ---
            $stmt = $pdo->prepare("SELECT USER_ID, USER_NAME, PASSWORD, TYPE_USER_ID FROM USERS WHERE USER_NAME = :identifiant");
            $stmt->execute(['identifiant' => $identifiant]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                // Comparaison du mot de passe en texte clair (selon vos données de test)
                if ($password === $user_data['PASSWORD']) {
                    
                    // On remplit la session (je garde les clés en minuscules pour ne pas casser le reste de votre site)
                    $_SESSION['user_id'] = $user_data['USER_ID'];
                    $_SESSION['user_name'] = $user_data['USER_NAME'];
                    $_SESSION['type_user_id'] = $user_data['TYPE_USER_ID'];
                    
                    // --- BONUS : Mise à jour de la colonne LAST_SIGN ---
                    $update_sign = $pdo->prepare("UPDATE USERS SET LAST_SIGN = NOW() WHERE USER_ID = :id");
                    $update_sign->execute(['id' => $user_data['USER_ID']]);

                    header("Location: index.php");
                    exit();
                } else {
                    $erreur = "Mot de passe incorrect.";
                }
            } else {
                $erreur = "Identifiant inconnu.";
            }

        } catch (PDOException $e) {
            // Message d'erreur personnalisé
            if (strpos($e->getMessage(), 'could not find driver') !== false) {
                $erreur = "Le driver PDO MySQL n'est pas activé dans Docker.";
            } else {
                $erreur = "Erreur de base de données : " . $e->getMessage();
            }
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Connexion</title>
    <link rel="stylesheet" href="css/style2.css?v=<?= time(); ?>">
</head>
<body class="login-page">
    <main class="main-content">
        <div class="login-card">
            <div class="logo-box">
                <img src="assets/logo%20pont.png" alt="Logo PontConnect">
            </div>
            <h1>Connexion</h1>
            <p class="intro">Accès sécurisé au système de gestion</p>

            <?php if (!empty($erreur)): ?>
                <div class="error-msg"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="identifiant">Identifiant</label>
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: admin" value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-submit">Se Connecter</button>
            </form>
            <a href="register.php" class="back-link">Pas encore de compte ? S'inscrire</a>
        </div>
    </main>
</body>
</html>