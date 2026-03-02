<?php
session_start();

$erreur = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($identifiant) && !empty($password)) {
        if ($identifiant === 'admin' && $password === 'Admin@2026') {
            $_SESSION['user'] = 'admin';
            header("Location: index.php");
            exit();
        } else {
            $erreur = "Identifiant ou mot de passe incorrect.";
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
    <link rel="stylesheet" href="css/style2.css">
</head>
<body class="login-page">

    <main class="main-content full-width">
        <div class="hero-card login-card">
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
                    <label for="identifiant">Identifiant alphanumérique</label>
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: admin_port">
                </div>

                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <small class="password-hint">8 car. min, 1 majuscule, 1 chiffre, 1 car. spécial.</small>
                </div>

                <button type="submit" class="btn-submit">Se Connecter</button>
            </form>
            
            <a href="index.php" class="back-link">← Retour à l'accueil</a>
        </div>
    </main>

</body>
</html>