<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'pont_connecte_db';
$username = 'votre_utilisateur';
$password = 'votre_mot_de_passe';

// Connexion à la base de données avec PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération de l'historique
$sql = "SELECT id_passage, date_heure, navire_present FROM historique_passages ORDER BY date_heure DESC";
try {
    $stmt = $pdo->query($sql);
    $passages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Passages - PONTCONNECT</title>
    <style>
        :root {
            --sidebar-bg: #0b2b4d;
            --sidebar-hover: #16406e;
            --accent-yellow: #ffb703;
            --text-light: #ffffff;
            --header-bg: #004085;
            --body-bg: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background: repeating-linear-gradient(
                -45deg,
                #f0f4f8,
                #f0f4f8 15px,
                #ffffff 15px,
                #ffffff 30px
            );
        }

        /* --- MENU LATÉRAL --- */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .brand {
            padding: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            background-color: #1a426e;
            margin: 0 15px 20px 15px;
            padding: 10px;
            border-radius: 8px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        .user-info p {
            font-size: 0.9rem;
        }
        .user-info .role {
            font-size: 0.75rem;
            color: #aaa;
        }
        .user-info .name {
            color: var(--accent-yellow);
            font-weight: bold;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
        }

        .nav-item {
            padding: 15px 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: 0.3s;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
        }

        .nav-item.active {
            background-color: var(--accent-yellow);
            color: var(--sidebar-bg);
            font-weight: bold;
            border-radius: 0 20px 20px 0;
            width: 90%;
        }

        /* --- CONTENU PRINCIPAL --- */
        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .header-card {
            background-color: var(--header-bg);
            color: var(--text-light);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header-card h1 {
            color: var(--accent-yellow);
            font-size: 2rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .header-card p {
            font-size: 1rem;
        }

        /* --- TABLEAU --- */
        .table-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-top: 4px solid var(--accent-yellow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: var(--sidebar-bg);
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-yes {
            background-color: #d4edda;
            color: #155724;
        }

        .status-no {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">PONTCONNECT</div>
        
        <div class="user-profile">
            <div class="user-avatar">👤</div>
            <div class="user-info">
                <p class="name">admin</p>
                <p class="role">Administrateur</p>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <li><a href="#" class="nav-item">Accueil</a></li>
                <li><a href="#" class="nav-item">Réservation</a></li>
                <li><a href="#" class="nav-item">Gestion Capteur</a></li>
                <li><a href="#" class="nav-item active">Historique</a></li>
                <li><a href="#" class="nav-item">Aide</a></li>
                <li><a href="#" class="nav-item">Contact</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        
        <div class="header-card">
            <h1>HISTORIQUE DES PASSAGES 📋</h1>
            <p>Visualisation des horodatages et de la présence des navires.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° Enregistrement</th>
                        <th>Date et Heure</th>
                        <th>Présence d'un Navire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($passages)): ?>
                        <?php foreach ($passages as $passage): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($passage['id_passage']) ?></td>
                                <td><?= date('d/m/Y à H:i:s', strtotime($passage['date_heure'])) ?></td>
                                <td>
                                    <?php if ($passage['navire_present']): ?>
                                        <span class="status-badge status-yes">Navire Détecté</span>
                                    <?php else: ?>
                                        <span class="status-badge status-no">Aucun Navire</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #777;">Aucune donnée disponible dans l'historique.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>