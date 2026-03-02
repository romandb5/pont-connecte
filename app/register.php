<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$erreur = "";
$succes = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9]+$/', $identifiant)) {
        $erreur = "L'identifiant doit être uniquement alphanumérique.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
        $erreur = "Le mot de passe ne respecte pas les critères de sécurité.";
    } elseif ($password !== $confirm_password) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $conn = new mysqli('db', 'Etudiant', 'P@ssword', 'pontconnecte');
        
        if (!$conn->connect_error) {
            // On vérifie si l'utilisateur existe déjà
            $check = $conn->prepare("SELECT user_id FROM utilisateurs WHERE user_name = ?");
            $check->bind_param("s", $identifiant);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $erreur = "Cet identifiant est déjà utilisé.";
            } else {
                // Hachage sécurisé du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $email_fictif = $identifiant . "@port-dunkerque.fr"; // Remplissage du champ email obligatoire
                $type_user_defaut = 2; // Type 2 = Utilisateur standard
                $date_creation = date('Y-m-d H:i:s');

                // Insertion en BDD
                $insert = $conn->prepare("INSERT INTO utilisateurs (user_name, email, password, date_creation, type_user_id) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("ssssi", $identifiant, $email_fictif, $hashed_password, $date_creation, $type_user_defaut);
                
                if ($insert->execute()) {
                    $succes = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                } else {
                    $erreur = "Erreur lors de la création du compte.";
                }
                $insert->close();
            }
            $check->close();
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
    <link rel="stylesheet" href="css/login.css?v=<?= time(); ?>">
</head>
<body class="login-page">

    <main class="main-content full-width">
        <div class="hero-card login-card">
            <div class="logo-box">
                <img src="assets/logo%20pont.png" alt="Logo PontConnect" style="max-width: 200px;">
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
                    <label for="identifiant">Identifiant alphanumérique</label>
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: operateur01">
                </div>

                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <small class="password-hint">8 car. min, 1 majuscule, 1 chiffre, 1 car. spécial.</small>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-submit">S'inscrire</button>
            </form>
            
            <a href="login.php" class="back-link">← Déjà un compte ? Se connecter</a>
        </div>
    </main>

</body>
</html>