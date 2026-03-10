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

            // Création de la connexion
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Requête préparée PDO
            $stmt = $pdo->prepare("SELECT user_id, user_name, password, type_user_id FROM utilisateurs WHERE user_name = :identifiant");
            $stmt->execute(['identifiant' => $identifiant]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                // Comparaison en texte clair pour vos tests
                if ($password === $user_data['password']) {
                    $_SESSION['user_id'] = $user_data['user_id'];
                    $_SESSION['user_name'] = $user_data['user_name'];
                    $_SESSION['type_user_id'] = $user_data['type_user_id'];
                    
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
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: admin_dk" value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
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