<?php
session_start();

// ==========================================
// 1. SÉCURITÉ ET RESTRICTION D'ACCÈS
// ==========================================

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Restriction d'accès : Seuls Admin (1) et Opérateur Pont (2) peuvent entrer
if ($_SESSION['type_user_id'] != 1 && $_SESSION['type_user_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];

// Détermination du libellé du rôle pour l'affichage
$role_id = $_SESSION['type_user_id'];
$role_label = ($role_id == 1) ? 'Administrateur' : 'Opérateur Pont';

// ==========================================
// 2. RÉCUPÉRATION DES DONNÉES CAPTEURS
// ==========================================

try {
    $pdo = new PDO("mysql:host=db-web;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la liste des capteurs enregistrés
    $stmt = $pdo->query("SELECT * FROM CAPTEURS");
    $capteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $donnees_graphiques = [];

    foreach ($capteurs as $capteur) {
        $id = $capteur['CAPTEUR_ID'];
        
        // On récupère les 15 dernières mesures pour chaque capteur pour avoir un historique visible
        $stmt_mesures = $pdo->prepare("SELECT VALEUR, DATE_MESURE 
                                       FROM MESURES_CAPTEURS 
                                       WHERE CAPTEUR_ID = :id 
                                       ORDER BY DATE_MESURE DESC LIMIT 15");
        $stmt_mesures->execute(['id' => $id]);
        $mesures = array_reverse($stmt_mesures->fetchAll(PDO::FETCH_ASSOC)); // Chronologie : du plus ancien au plus récent

        $labels = [];
        $valeurs = [];
        foreach ($mesures as $m) {
            $labels[] = date('H:i', strtotime($m['DATE_MESURE'])); // Format Heure:Minute
            $valeurs[] = $m['VALEUR'];
        }

        $donnees_graphiques[$id] = [
            'nom' => $capteur['LIBELLE_CAPTEUR'],
            'type' => $capteur['TYPE_CAPTEUR'],
            'unite' => $capteur['UNITE_MESURE'],
            'labels' => $labels,
            'valeurs' => $valeurs
        ];
    }

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Monitoring</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles spécifiques pour la grille de graphiques */
        .capteurs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-top: 5px solid var(--dk-yellow);
        }
        .chart-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .chart-info h3 { color: var(--dk-blue-deep); margin: 0; font-size: 1.1rem; }
        .current-value { 
            font-weight: bold; 
            background: #f0f4f8; 
            padding: 5px 12px; 
            border-radius: 20px; 
            color: var(--dk-blue-deep);
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>PONTCONNECT</h2>
        </div>
        
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
            <li><a href="gestion-capteur.php" class="active">Gestion Capteur</a></li>
            <li><a href="#">Aide</a></li>
            <li><a href="#">Contact</a></li>
        </ul>

        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">Déconnexion</a></div>
    </aside>

    <main class="main-content">
        
        <div class="logo-central-container">
            <img src="assets/logo%20pont.png" alt="Logo PontConnect">
        </div>

        <div class="welcome-banner">
            <h1>MONITORING TECHNIQUE 📊</h1>
            <p>Visualisation en temps réel des données structurelles du pont.</p>
        </div>

        <div class="capteurs-grid">
            <?php foreach ($donnees_graphiques as $id => $data): ?>
                <div class="chart-card">
                    <div class="chart-info">
                        <h3><?= htmlspecialchars($data['nom']) ?> (<?= ucfirst($data['type']) ?>)</h3>
                        <div class="current-value">
                            <?php 
                                $derniere_valeur = end($data['valeurs']);
                                echo ($derniere_valeur !== false) ? $derniere_valeur . ' ' . $data['unite'] : 'N/A';
                            ?>
                        </div>
                    </div>
                    <canvas id="chart-<?= $id ?>" height="200"></canvas>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Récupération des données envoyées par PHP
        const sensors = <?= json_encode($donnees_graphiques) ?>;

        // On boucle sur chaque capteur pour créer son graphique
        Object.keys(sensors).forEach(id => {
            const ctx = document.getElementById(`chart-${id}`).getContext('2d');
            const s = sensors[id];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: s.labels,
                    datasets: [{
                        label: s.nom,
                        data: s.valeurs,
                        borderColor: '#003366',
                        backgroundColor: 'rgba(255, 183, 3, 0.1)',
                        fill: true,
                        tension: 0.3, // Courbe légèrement arrondie
                        borderWidth: 3,
                        pointBackgroundColor: '#FFB703',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: s.unite }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });

        // Actualisation automatique toutes les 30 secondes pour simuler le temps réel
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>