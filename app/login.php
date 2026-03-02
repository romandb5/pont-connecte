<?php
session_start();

// Si l'utilisateur est déjà connecté, on l'envoie sur l'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = $_POST['identifiant'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($identifiant) && !empty($password)) {
        // Connexion à la base de données
        $conn = new mysqli('db', 'Etudiant', 'P@ssword', 'pontconnecte');
        
        if (!$conn->connect_error) {
            // Requête préparée pour chercher l'utilisateur
            $stmt = $conn->prepare("SELECT user_id, user_name, password, type_user_id FROM utilisateurs WHERE user_name = ?");
            $stmt->bind_param("s", $identifiant);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Vérification du mot de passe haché
                if (password_verify($password, $user['password'])) {
                    // Mot de passe correct ! On crée la session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['user_name'];
                    $_SESSION['type_user_id'] = $user['type_user_id'];
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $erreur = "Mot de passe incorrect.";
                }
            } else {
                $erreur = "Identifiant introuvable.";
            }
            $stmt->close();
        } else {
            $erreur = "Erreur de connexion à la base de données.";
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
                <img src="assets/logo%20pont.png" alt="Logo PontConnect" style="max-width: 200px;">
            </div>
            
            <h1>Connexion</h1>
            <p class="intro" style="margin-top:0; padding:0; font-size:1rem;">Accès sécurisé au système de gestion</p>

            <?php if (!empty($erreur)): ?>
                <div class="error-msg"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="identifiant">Identifiant</label>
                    <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: admin_dk">
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