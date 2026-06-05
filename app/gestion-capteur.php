<?php
session_start();

// ==========================================
// 1. SÉCURITÉ ET RESTRICTION D'ACCÈS
// ==========================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['type_user_id'] != 1 && $_SESSION['type_user_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$role_label = ($_SESSION['type_user_id'] == 1) ? 'Administrateur' : 'Opérateur Pont';

// ==========================================
// 2. DÉFINITION DES SEUILS DE SÉCURITÉ
// ==========================================
// Vous pouvez modifier ces valeurs selon les vraies limites de votre pont
$seuils_securite = [
    'vibration' => 0.40,       // Max 0.40 m/s2 (au-delà, risque sismique/structurel)
    'qualite_eau' => 340.00,   // Max 340 ppm (alerte pollution)
    'temperature' => 30.00,    // Max 30 °C (surchauffe matériel)
    'profondeur' => 60000.00   // Max 60000 mm (alerte inondation)
];

// Tableau pour stocker les messages d'alerte
$alertes_actives = [];

// ==========================================
// 3. RÉCUPÉRATION DES DONNÉES CAPTEURS
// ==========================================
try {
    $pdo = new PDO("mysql:host=db-web;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM CAPTEURS");
    $capteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $donnees_graphiques = [];

    foreach ($capteurs as $capteur) {
        $id = $capteur['CAPTEUR_ID'];
        $type = $capteur['TYPE_CAPTEUR'];
        
        $stmt_mesures = $pdo->prepare("SELECT VALEUR, DATE_MESURE 
                                       FROM MESURES_CAPTEURS 
                                       WHERE CAPTEUR_ID = :id 
                                       ORDER BY DATE_MESURE DESC LIMIT 15");
        $stmt_mesures->execute(['id' => $id]);
        $mesures = array_reverse($stmt_mesures->fetchAll(PDO::FETCH_ASSOC));

        $labels = [];
        $valeurs = [];
        foreach ($mesures as $m) {
            // 1. On lit la date en temps universel
            $date = new DateTime($m['DATE_MESURE'], new DateTimeZone('UTC'));
            // 2. On convertit cette date à l'heure française
            $date->setTimezone(new DateTimeZone('Europe/Paris'));
            // 3. On extrait l'heure, les minutes ET LES SECONDES pour le graphique
            $labels[] = $date->format('H:i:s');
            $valeurs[] = $m['VALEUR'];
        }

        // Vérification de l'alerte sur la dernière valeur
        $derniere_valeur = end($valeurs);
        $seuil = $seuils_securite[$type] ?? null;
        $en_alerte = false;

        if ($seuil !== null && $derniere_valeur !== false && $derniere_valeur > $seuil) {
            $en_alerte = true;
            $alertes_actives[] = "Le capteur " . $capteur['LIBELLE_CAPTEUR'] . " a dépassé la limite de sécurité (" . $derniere_valeur . " > " . $seuil . " " . $capteur['UNITE_MESURE'] . ").";
        }

        $donnees_graphiques[$id] = [
            'nom' => $capteur['LIBELLE_CAPTEUR'],
            'type' => $type,
            'unite' => $capteur['UNITE_MESURE'],
            'labels' => $labels,
            'valeurs' => $valeurs,
            'seuil' => $seuil,
            'en_alerte' => $en_alerte
        ];
    }

    // =========================================================
    // MOTEUR AJAX : Si JS demande des données, on envoie du JSON et on s'arrête là
    // =========================================================
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['capteurs' => $donnees_graphiques, 'alertes' => $alertes_actives]);
        exit(); // On bloque l'affichage du HTML en dessous
    }

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
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
            transition: 0.3s;
        }
        
        /* Styles en cas d'Alerte */
        .chart-card.alerte-danger {
            border-top: 5px solid #dc3545;
            animation: pulse-red 2s infinite;
        }
        .current-value.alerte-text {
            background: #dc3545;
            color: white;
        }
        
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
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
            transition: 0.3s;
        }
        
        /* Bannière d'alerte globale */
        .global-alert-box {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        .global-alert-box h3 { margin-top: 0; margin-bottom: 10px; }
        .global-alert-box ul { margin: 0; padding-left: 20px; font-weight: bold; }
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
            <li><a href="gestion-capteur.php" class="active">Gestion Capteur</a></li>
            <li><a href="historique.php">Historique passages</a></li>
            <li><a href="maintenance.php">Maintenance</a></li>
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

        <?php if (!empty($alertes_actives)): ?>
            <div class="global-alert-box" id="zone-alerte">
                <h3>🚨 ALERTE DE SÉCURITÉ CRITIQUE</h3>
                <ul>
                    <?php foreach ($alertes_actives as $alerte): ?>
                        <li><?= htmlspecialchars($alerte) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="capteurs-grid">
            <?php foreach ($donnees_graphiques as $id => $data): ?>
                <div class="chart-card <?= $data['en_alerte'] ? 'alerte-danger' : '' ?>" id="card-<?= $id ?>">
                    <div class="chart-info">
                        <h3><?= htmlspecialchars($data['nom']) ?> (<?= ucfirst($data['type']) ?>)</h3>
                        <div class="current-value <?= $data['en_alerte'] ? 'alerte-text' : '' ?>" id="val-<?= $id ?>">
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
        const chartInstances = {};

        // 1. DESSIN INITIAL DES GRAPHIQUES
        function initialiserGraphiques() {
            let sensors = <?= json_encode($donnees_graphiques) ?>;
            
            Object.keys(sensors).forEach(id => {
                const ctx = document.getElementById(`chart-${id}`).getContext('2d');
                const s = sensors[id];

                const graphDatasets = [{
                    label: s.nom,
                    data: s.valeurs,
                    // Si en alerte, la courbe devient rouge, sinon elle reste bleue
                    borderColor: s.en_alerte ? '#dc3545' : '#003366',
                    backgroundColor: s.en_alerte ? 'rgba(220, 53, 69, 0.1)' : 'rgba(255, 183, 3, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 3,
                    pointBackgroundColor: s.en_alerte ? '#dc3545' : '#FFB703',
                    pointRadius: 4
                }];

                // 2. Dataset visuel (La ligne rouge en pointillés pour la limite)
                if (s.seuil !== null) {
                    graphDatasets.push({
                        label: 'Limite Sécurité',
                        data: Array(s.labels.length).fill(s.seuil),
                        borderColor: '#ff0000',
                        borderWidth: 2,
                        borderDash: [5, 5], // Fait des pointillés
                        fill: false,
                        pointRadius: 0 // Cache les petits ronds sur cette ligne
                    });
                }

                // Sauvegarde de l'instance du graphique dans notre objet
                chartInstances[id] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: s.labels,
                        datasets: graphDatasets
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { title: { display: true, text: s.unite } },
                            x: { grid: { display: false } }
                        },
                        animation: { duration: 500 } // Animation fluide lors des mises à jour
                    }
                });
            });
        }

        // Lancement au démarrage de la page
        initialiserGraphiques();

        // 2. MOTEUR AJAX (Mise à jour invisible toutes les 10 secondes)
        setInterval(() => {
            fetch('gestion-capteur.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    const newData = data.capteurs;
                    
                    Object.keys(newData).forEach(id => {
                        if(chartInstances[id]) {
                            const s = newData[id];
                            const chart = chartInstances[id];

                            // A. Mise à jour de la courbe
                            chart.data.labels = s.labels;
                            chart.data.datasets[0].data = s.valeurs;
                            chart.data.datasets[0].borderColor = s.en_alerte ? '#dc3545' : '#003366';
                            chart.data.datasets[0].backgroundColor = s.en_alerte ? 'rgba(220, 53, 69, 0.1)' : 'rgba(255, 183, 3, 0.1)';
                            chart.data.datasets[0].pointBackgroundColor = s.en_alerte ? '#dc3545' : '#FFB703';
                            chart.update();

                            // B. Mise à jour du texte HTML
                            const valTextHTML = document.getElementById(`val-${id}`);
                            const cardHTML = document.getElementById(`card-${id}`);
                            
                            valTextHTML.innerText = s.valeurs[s.valeurs.length - 1] + ' ' + s.unite;

                            // C. Gestion visuelle des alertes rouges
                            if (s.en_alerte) {
                                cardHTML.classList.add('alerte-danger');
                                valTextHTML.classList.add('alerte-text');
                            } else {
                                cardHTML.classList.remove('alerte-danger');
                                valTextHTML.classList.remove('alerte-text');
                            }
                        }
                    });

                    // Optionnel : Si une nouvelle alerte générale apparaît, on force un rafraîchissement
                    // pour afficher proprement le gros bandeau rouge en haut de page
                    const alertBoxExists = document.getElementById('zone-alerte');
                    if (data.alertes.length > 0 && !alertBoxExists) {
                        window.location.reload();
                    }
                })
                .catch(error => console.error('Erreur AJAX:', error));
        }, 10000); // Exécute l'actualisation toutes les 10 secondes
    </script>
</body>
</html>