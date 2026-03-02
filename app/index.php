<?php
session_start();

$username = isset($_SESSION['user']) ? $_SESSION['user'] : 'Invité';
$role = ($username === 'admin') ? 'Administrateur' : 'Utilisateur';

$host = 'db';
$user = 'Etudiant';
$pass = 'P@ssword';
$db   = 'pontconnecte';

error_reporting(E_ALL ^ E_WARNING);
$conn = new mysqli($host, $user, $pass, $db);

$db_status = "";
if ($conn->connect_error) {
    $db_status = "<span class='status-error'>● Erreur BDD</span>";
} else {
    $db_status = "<span class='status-success'>● Système en ligne</span>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Accueil</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
</head>
<body>

    <nav class="sidebar">
        <h2>PontConnect</h2>

        <div class="user-profile">
            <div class="avatar">👤</div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($username) ?></span>
                <span class="user-role"><?= $role ?></span>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="index.php" class="active">Accueil</a></li>
            <li><a href="reservation.php">Réservation</a></li>
            <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
            <li><a href="aide.php">Aide</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="hero-card">
            <div class="logo-box">
                <img src="assets/logo%20pont.png" alt="Logo PontConnect">
            </div>
            
            <h1>Bienvenue à Bord</h1>
            <p class="intro">
                La solution centralisée pour la gestion intelligente des infrastructures maritimes et fluviales.
            </p>
        </div>
    </main>

    <div class="status-badge">
        <?= $db_status ?>
    </div>

</body>
</html>