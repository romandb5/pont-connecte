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
    <style>
        :root {
            --dk-blue: #003366;       /* Bleu marine profond */
            --dk-green: #00cba9;      /* Vert d'eau tonique / Cyan */
            --dk-bg-light: #f0f4f7;   /* Gris très clair bleuté */
            --dk-text-grey: #5a6c7d;  /* Gris texte doux */
        }

        /* --- RESET ET BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--dk-bg-light);
        }

        /* --- BARRE LATÉRALE (SIDEBAR) --- */
        .sidebar {
            width: 260px;
            background-color: var(--dk-blue);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 50px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        /* Petit détail vert sous le titre du menu */
        .sidebar-header h2::after {
            content: '';
            display: block;
            width: 40px;
            height: 3px;
            background-color: var(--dk-green);
            margin: 15px auto 0;
            border-radius: 2px;
        }

        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 8px; }

        .nav-links a {
            text-decoration: none;
            color: rgba(255,255,255,0.7); /* Blanc cassé au repos */
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 3px solid transparent; /* Bordure invisible par défaut */
        }

        /* Effet au survol et lien actif : Touche de vert */
        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255,255,255,0.05); /* Fond très léger */
            color: var(--dk-green); /* Le texte devient vert */
            border-left-color: var(--dk-green); /* La bordure gauche devient verte */
            padding-left: 20px; /* Petit décalage */
        }


        /* --- CONTENU PRINCIPAL --- */
        .main-content {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .logo-box {
            width: 180px;
            margin-bottom: 30px;
            /* Optionnel : légère ombre portée sur le logo */
            filter: drop-shadow(0 5px 5px rgba(0,0,0,0.1));
        }
        .logo-box img { width: 100%; height: auto; display: block;}

        h1 {
            color: var(--dk-blue);
            margin-bottom: 20px;
            font-size: 2.8rem;
            font-weight: 800;
            position: relative;
        }
        
        /* Soulignement vert stylisé sous le titre principal */
        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--dk-green);
            border-radius: 2px;
        }

        p.intro {
            color: var(--dk-text-grey);
            font-size: 1.2rem;
            max-width: 650px;
            line-height: 1.6;
            margin-top: 25px;
        }
        
        /* --- BADGE STATUT BDD --- */
        .status-badge {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: white;
            padding: 10px 18px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-icon { font-size: 1.2rem; }
        /* Utilisation du vert Dunkerque pour le succès */
        .status-badge.success { color: var(--dk-green); border: 2px solid var(--dk-green); }
        .status-badge.error { color: #e74c3c; border: 2px solid #e74c3c; }
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