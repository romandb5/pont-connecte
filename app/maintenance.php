<?php
session_start();

// ==========================================
// 1. SÉCURITÉ ET RESTRICTION D'ACCÈS
// ==========================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Seuls l'Admin (1) et l'Opérateur (2) ont accès à la documentation de maintenance
if ($_SESSION['type_user_id'] != 1 && $_SESSION['type_user_id'] != 2) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['user_name'];
$role_label = ($_SESSION['type_user_id'] == 1) ? 'Administrateur' : 'Opérateur Pont';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Maintenance</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    
    <style>
        /* Styles spécifiques pour la carte de maintenance */
        .maintenance-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-top: 5px solid var(--dk-yellow, #FFB703);
            margin-top: 30px;
            text-align: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .maintenance-card h2 {
            color: var(--dk-blue-deep, #003366);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .maintenance-card p {
            color: #555;
            line-height: 1.7;
            margin-bottom: 35px;
            font-size: 1.1rem;
            text-align: justify;
        }

        .alert-box-info {
            background-color: #e9f2fb;
            border-left: 4px solid #003366;
            padding: 15px 20px;
            margin-bottom: 30px;
            text-align: left;
            border-radius: 4px;
            color: #003366;
            font-weight: bold;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            background-color: #003366;
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.2);
        }

        .btn-download:hover {
            background-color: #FFB703;
            color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 183, 3, 0.3);
        }

        .btn-download .icon {
            font-size: 1.4rem;
            margin-right: 12px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><h2>PONTCONNECT</h2></div>
        
        <div class="user-profile">
            <div class="avatar">👤</div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($username) ?></span>
                <span class="user-role"><?= $role_label ?></span>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="reservation.php">Réservation</a></li>
            <?php if ($_SESSION['type_user_id'] == 1 || $_SESSION['type_user_id'] == 2): ?>
                <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
                <li><a href="historique.php">Historique passages</a></li>
                <li><a href="maintenance.php" class="active">Maintenance</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">Déconnexion</a></div>
    </aside>

    <main class="main-content">
        <div class="logo-central-container">
            <img src="assets/logo%20pont.png" alt="Logo PontConnect">
        </div>

        <div class="welcome-banner">
            <h1>MAINTENANCE MATÉRIELLE 🛠️</h1>
            <p>Protocoles d'entretien et de recalibrage des équipements IoT.</p>
        </div>

        <div class="maintenance-card">
            <h2>Guide de Maintenance des Capteurs</h2>
            
            <div class="alert-box-info">
                ℹ️ La précision des données remontées par le réseau LoRaWAN dépend directement de l'état physique de l'installation.
            </div>

            <p>
                L'intégrité structurelle du pont et la fiabilité des alertes de sécurité reposent sur le bon fonctionnement de notre flotte de capteurs (accéléromètres MMA8451, sondes de niveau d'eau, et détecteurs infrarouges). Soumis aux intempéries marines et aux fortes vibrations, ces équipements nécessitent un suivi rigoureux.
                <br><br>
                Afin de prévenir toute dérive des mesures (comme le glissement de l'accélération résiduelle ou l'encrassement des lentilles optiques), des opérations d'étalonnage logiciel et de nettoyage physique doivent être réalisées périodiquement. Vous trouverez toutes les procédures de diagnostic étape par étape, les schémas de câblage I2C et les protocoles de remplacement dans le manuel officiel ci-dessous.
            </p>
            
            <a href="assets/guide_maintenance.pdf" target="_blank" class="btn-download">
                <span class="icon">📄</span> Afficher le Guide Complet (PDF)
            </a>
        </div>
    </main>

</body>
</html>