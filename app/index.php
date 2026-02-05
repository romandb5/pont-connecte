<?php
// --- 1. CONNEXION BASE DE DONNÉES ---
$host = 'db';
$user = 'Etudiant';
$pass = 'P@ssword';
$db   = 'pontconnecte';

$conn = new mysqli($host, $user, $pass, $db);

// Vérification silencieuse (on n'affiche l'erreur que si ça plante vraiment)
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
    <style>
        /* --- CSS : STYLE DE LA PAGE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        /* Barre latérale */
        .sidebar {
            width: 260px;
            background-color: #003366; /* Bleu Marine du logo */
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .sidebar h2 {
            font-size: 1.4rem;
            margin-bottom: 40px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 20px;
        }

        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 10px; }

        .nav-links a {
            text-decoration: none;
            color: rgba(255,255,255,0.8);
            display: block;
            padding: 12px 15px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        /* Contenu Principal */
        .main-content {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .logo-box {
            width: 200px;
            margin-bottom: 20px;
        }
        .logo-box img { width: 100%; }

        h1 { color: #003366; margin-bottom: 15px; font-size: 2.5rem; }
        p.intro { color: #666; font-size: 1.2rem; max-width: 600px; line-height: 1.6; }
        
        /* Indicateur de statut BDD en bas à droite */
        .status-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>
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