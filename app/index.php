<?php
$host = 'db';
$user = 'Etudiant';
$pass = 'P@ssword';
$db   = 'pontconnecte';

$conn = new mysqli($host, $user, $pass, $db);

$db_status = "";
if ($conn->connect_error) {
    $db_status = "<span style='color:red'>Erreur BDD: " . $conn->connect_error . "</span>";
} else {
    $db_status = "<span style='color:#4CAF50'>● Système en ligne</span>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Accueil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="sidebar">
        <h2>PontConnect</h2>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Accueil</a></li>
            <li><a href="reservation.php">Réservation</a></li>
            <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
            <li><a href="aide.php">Aide</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="logo-box">
            <img src="assets/logo pont.png" alt="Logo PontConnect">
        </div>
        
        <h1>Bienvenue à Bord</h1>
        <p class="intro">
            La solution centralisée pour la gestion intelligente des infrastructures maritimes et fluviales.
        </p>
    </main>

    <div class="status-badge">
        <?= $db_status ?>
    </div>

</body>
</html>