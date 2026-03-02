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
    if (!preg_match('/^[a-zA-Z0-9]+$/', $identifiant)) {
        $erreur = "L'identifiant doit être uniquement alphanumérique.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    }
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
        $erreur = "Le mot de passe ne respecte pas les critères de sécurité (8 car. min, 1 majuscule, 1 chiffre, 1 spécial).";
    } 
    elseif ($password !== $confirm_password) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // 2. Connexion à la base de données
        $conn = new mysqli('db', 'Etudiant', 'P@ssword', 'pontconnecte');
        
        if ($conn->connect_error) {
            $erreur = "Erreur de connexion à la base de données.";
        } else {
            // 3. Vérification de l'existence de l'utilisateur ou de l'email
            $check = $conn->prepare("SELECT user_id FROM utilisateurs WHERE user_name = ? OR email = ?");
            $check->bind_param("ss", $identifiant, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $erreur = "L'identifiant ou l'email est déjà utilisé.";
            } else {
                // 4. Préparation des données pour l'insertion
                $type_user_defaut = 2; // Rôle 'Utilisateur' par défaut
                $date_now = date('Y-m-d H:i:s');

                // --- MODIFICATION : Insertion du mot de passe en CLAIR ---
                $insert = $conn->prepare("INSERT INTO utilisateurs (user_name, email, password, date_creation, type_user_id) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("ssssi", $identifiant, $email, $password, $date_now, $type_user_defaut);
                
                if ($insert->execute()) {
                    $succes = "Compte créé avec succès ! Vous pouvez vous connecter.";
                } else {
                    $erreur = "Erreur technique lors de la création.";
                }
                $insert->close();
            }
            $check->close();
            $conn->close();
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