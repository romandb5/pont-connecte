<?php
session_start();
$erreur = "";
$succes = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=db;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO utilisateurs (user_name, email, password, date_creation, type_user_id) 
                VALUES (:user, :email, :pass, NOW(), 2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user'  => $identifiant,
            'email' => $email,
            'pass'  => $password
        ]);
        $succes = "Compte créé avec succès !";
    } catch (PDOException $e) {
        $erreur = "Erreur : " . $e->getMessage();
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
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: operateur01" value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="email">Adresse Email</label>
                    <input type="email" id="email" name="email" required placeholder="Ex: marin@port-dunkerque.fr" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <span class="password-hint">8 car. min, 1 majuscule, 1 chiffre, 1 car. spécial.</span>
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