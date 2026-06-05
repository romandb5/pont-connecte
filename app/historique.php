<?php
session_start();

// ==========================================
// 1. SÉCURITÉ ET RESTRICTION D'ACCÈS
// ==========================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Seuls l'Admin (1) et l'Opérateur (2) ont accès à l'historique
if ($_SESSION['type_user_id'] != 1 && $_SESSION['type_user_id'] != 2) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['user_name'];
$role_label = ($_SESSION['type_user_id'] == 1) ? 'Administrateur' : 'Opérateur Pont';

// ==========================================
// 2. CONNEXION BDD ET RÉCUPÉRATION
// ==========================================
try {
    $pdo = new PDO("mysql:host=db-web;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // On filtre avec "m.VALEUR > 0" pour n'avoir QUE les détections.
    // On limite à 50 résultats pour ne pas charger la base de données inutilement.
    $sql = "SELECT m.DATE_MESURE as date_heure, m.VALEUR as navire_present 
            FROM MESURES_CAPTEURS m
            JOIN CAPTEURS c ON m.CAPTEUR_ID = c.CAPTEUR_ID
            WHERE (c.TYPE_CAPTEUR LIKE '%presence%' 
               OR c.LIBELLE_CAPTEUR LIKE '%presence%' 
               OR c.LIBELLE_CAPTEUR LIKE '%infrarouge%')
              AND m.VALEUR > 0 
            ORDER BY m.DATE_MESURE DESC 
            LIMIT 50";
            
    $stmt = $pdo->query($sql);
    $passages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Historique des Passages</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    <style>
        /* Styles spécifiques au tableau d'historique */
        .table-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 0; /* Retiré pour que le sticky header marche bien */
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-top: 5px solid var(--dk-yellow, #FFB703);
            margin-top: 30px;
            
            /* Bloque la hauteur et ajoute un scroll interne */
            max-height: 500px; 
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        /* En-tête collante : Elle reste visible quand on descend dans le tableau */
        thead th {
            background-color: #f8f9fa;
            color: var(--dk-blue-deep, #003366);
            font-weight: bold;
            font-size: 1.05rem;
            position: sticky;
            top: 0;
            z-index: 1;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
        }

        tr:hover { background-color: #f1f5f9; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }

        .status-yes { background-color: #d4edda; color: #155724; }
        
        .id-passage {
            color: #6c757d;
            font-family: monospace;
            font-weight: bold;
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
                <li><a href="historique.php" class="active">Historique passages</a></li>
                <li><a href="maintenance.php">Maintenance</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">Déconnexion</a></div>
    </aside>

    <main class="main-content">
        <div class="logo-central-container">
            <img src="assets/logo%20pont.png" alt="Logo PontConnect">
        </div>

        <div class="welcome-banner">
            <h1>HISTORIQUE DES PASSAGES 📋</h1>
            <p>Seules les détections confirmées de navires sont affichées ci-dessous.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° Enregistrement</th>
                        <th>Date et Heure de passage</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($passages)): ?>
                        <?php $compteur = 1; ?>
                        <?php foreach ($passages as $passage): ?>
                            <tr>
                                <td class="id-passage">#<?= sprintf("%03d", $compteur++) ?></td>
                                <td>
                                    <?php 
                                        $date = new DateTime($passage['date_heure'], new DateTimeZone('UTC'));
                                        $date->setTimezone(new DateTimeZone('Europe/Paris'));
                                        echo $date->format('d/m/Y à H:i:s'); 
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-yes">Navire Détecté</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #777; padding: 40px;">
                                Aucun navire n'a encore été détecté.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>