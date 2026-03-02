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

// --- NOUVELLE GESTION DU BADGE DE STATUT ---
$badge_class = "";
$badge_text = "";

if ($conn->connect_error) {
    $badge_class = "error";
    $badge_text = "● Erreur BDD";
} else {
    $badge_class = "success";
    $badge_text = "● Système en ligne";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Accueil</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    
    <style>
        .user-profile {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .avatar {
            font-size: 24px;
            background: white;
            border-radius: 50%;
            width: 40px; height: 40px;
            display: flex; justify-content: center; align-items: center;
        }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: bold; color: var(--dk-green); font-size: 1.1rem; }
        .user-role { font-size: 0.8rem; color: rgba(255,255,255,0.7); }
        
        .sidebar-footer { margin-top: auto; }
        .btn-logout {
            display: block; text-align: center; text-decoration: none;
            background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;
            padding: 15px; border-radius: 10px; font-weight: bold;
            transition: all 0.3s ease; border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .btn-logout:hover { background-color: #e74c3c; color: white; }

        /* Correction pour empiler correctement le contenu central */
        .hero-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">
            <h2 style="margin-bottom: 30px;">PontConnect</h2>
        </div>

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

    <div class="status-badge <?= $badge_class ?>">
        <span class="status-icon"><?= $badge_text ?></span>
    </div>

</body>
</html>