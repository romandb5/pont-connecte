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
            $pdo = new PDO("mysql:host=db;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT USER_ID, USER_NAME, PASSWORD, TYPE_USER_ID FROM USERS WHERE USER_NAME = :identifiant");
            $stmt->execute(['identifiant' => $identifiant]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                if ($password === $user_data['PASSWORD']) {
                    
                    $_SESSION['user_id'] = $user_data['USER_ID'];
                    $_SESSION['user_name'] = $user_data['USER_NAME'];
                    $_SESSION['type_user_id'] = $user_data['TYPE_USER_ID'];
                    
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
            $erreur = "Erreur de base de données : " . $e->getMessage();
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
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
</head>
<body class="auth-body">
    
    <div class="auth-card">
        <img src="assets/logo%20pont.png" alt="Logo PontConnect" class="auth-logo">
        
        <h1>Connexion</h1>
        <p class="intro">Accès sécurisé au système de gestion</p>

        <?php if (!empty($erreur)): ?>
            <div class="alert-box error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
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
        
        <a href="register.php" class="auth-link">Pas encore de compte ? S'inscrire</a>
    </div>

</body>
</html>