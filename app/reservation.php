<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$role = ($_SESSION['type_user_id'] == 1) ? 'Administrateur' : 'Utilisateur';

$message = "";
$message_type = "";

// --- GESTION DES MESSAGES FLASH ---
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

try {
    $pdo = new PDO("mysql:host=db;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- GESTION DES ACTIONS ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST['action']) && $_POST['action'] == 'reserver') {
            $date_only = $_POST['date_reservation'];
            $heure_only = $_POST['heure_reservation'];
            $date_complete = $date_only . ' ' . $heure_only; 
            
            $type_creneau_id = $_POST['type_creneau_id']; 
            $pont_id = $_POST['pont_id'];
            $bateau_id = $_POST['bateau_id'];
            $periode_id = $_POST['periode_id'];
            $status_id = 1; 

            if (!empty($date_only) && !empty($heure_only) && !empty($type_creneau_id) && !empty($pont_id) && !empty($bateau_id) && !empty($periode_id)) {
                
                // --- NOUVEAU : VÉRIFICATION DE DISPONIBILITÉ DU BATEAU ---
                $check_sql = "SELECT reservation_id FROM reservation WHERE bateau_id = :bateau AND date_reservation = :date_res";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    'bateau' => $bateau_id,
                    'date_res' => $date_complete
                ]);

                // Si le bateau a déjà une réservation à cette heure exacte
                if ($check_stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = "Erreur : Ce bateau a déjà une réservation prévue à cette même date et heure.";
                    $_SESSION['flash_type'] = "error"; // Affichera la boîte rouge
                    header("Location: reservation.php");
                    exit();
                } 
                else {
                    // Si tout est bon, on insère la réservation
                    $sql = "INSERT INTO reservation (date_reservation, user_id, bateau_id, pont_id, type_creneau_id, periode_id, status_id) 
                            VALUES (:date_res, :user, :bateau, :pont, :type_c, :periode, :status)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'date_res' => $date_complete,
                        'user' => $user_id,
                        'bateau' => $bateau_id,
                        'pont' => $pont_id,
                        'type_c' => $type_creneau_id,
                        'periode' => $periode_id,
                        'status' => $status_id
                    ]);
                    
                    $_SESSION['flash_message'] = "Réservation confirmée pour le " . date('d/m/Y à H:i', strtotime($date_complete));
                    $_SESSION['flash_type'] = "success";
                    header("Location: reservation.php");
                    exit();
                }
            }
        }
        
        elseif (isset($_POST['action']) && $_POST['action'] == 'annuler') {
            $res_id = $_POST['reservation_id'];
            $sql = "DELETE FROM reservation WHERE reservation_id = :res_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['res_id' => $res_id, 'user_id' => $user_id]);
            
            $_SESSION['flash_message'] = "La réservation a été annulée.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }
    }

    // --- RÉCUPÉRATION DES DONNÉES (Pour l'affichage) ---
    $sql_mes_res = "SELECT r.reservation_id, r.date_reservation, p.libelle_pont, b.libelle_bateau, pc.libelle_periode, tc.libelle_type_creneau 
                    FROM reservation r 
                    JOIN ponts p ON r.pont_id = p.pont_id 
                    JOIN bateaux b ON r.bateau_id = b.bateau_id 
                    JOIN periode_creneau pc ON r.periode_id = pc.periode_id
                    JOIN type_creneau tc ON r.type_creneau_id = tc.type_creneau_id
                    WHERE r.user_id = :user_id 
                    ORDER BY r.date_reservation ASC";
    $stmt = $pdo->prepare($sql_mes_res);
    $stmt->execute(['user_id' => $user_id]);
    $mes_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ponts = $pdo->query("SELECT pont_id, libelle_pont FROM ponts")->fetchAll(PDO::FETCH_ASSOC);
    $bateaux = $pdo->query("SELECT bateau_id, libelle_bateau FROM bateaux")->fetchAll(PDO::FETCH_ASSOC);
    $periodes = $pdo->query("SELECT periode_id, libelle_periode FROM periode_creneau")->fetchAll(PDO::FETCH_ASSOC);
    $types_creneau = $pdo->query("SELECT type_creneau_id, libelle_type_creneau FROM type_creneau")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Erreur BDD : " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Réservation</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="css/style4.css?v=<?= time(); ?>">
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><h2>PONTCONNECT</h2></div>
        <div class="user-profile">
            <div class="avatar">👤</div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($username) ?></span>
                <span class="user-role"><?= $role ?></span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="reservation.php" class="active">Réservation</a></li>
            <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
            <li><a href="#">Aide</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">Déconnexion</a></div>
    </aside>

    <main class="main-content dashboard-layout">
        <div class="header-section">
            <h1>Gestion des Réservations</h1>
            <p>Planifiez vos passages ou gérez vos demandes existantes.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="stacked-layout">
            
            <section class="reservations-list">
                <h2>Mes réservations actives</h2>
                <?php if (empty($mes_reservations)): ?>
                    <div class="empty-state"><p>Vous n'avez aucune réservation pour le moment.</p></div>
                <?php else: ?>
                    <div class="cards-container">
                        <?php foreach ($mes_reservations as $res): ?>
                            <div class="res-card">
                                <div class="res-info">
                                    <div class="res-date">
                                        📅 <?= date('d/m/Y à H:i', strtotime($res['date_reservation'])) ?> 
                                        <span class="badge-periode"><?= htmlspecialchars($res['libelle_type_creneau']) ?> - <?= htmlspecialchars($res['libelle_periode']) ?></span>
                                    </div>
                                    <div class="res-details">
                                        <strong>Pont :</strong> <?= htmlspecialchars($res['libelle_pont']) ?> &nbsp;|&nbsp; 
                                        <strong>Bateau :</strong> <?= htmlspecialchars($res['libelle_bateau']) ?>
                                    </div>
                                </div>
                                <form action="reservation.php" method="POST" onsubmit="return confirm('Annuler cette réservation ?');">
                                    <input type="hidden" name="action" value="annuler">
                                    <input type="hidden" name="reservation_id" value="<?= $res['reservation_id'] ?>">
                                    <button type="submit" class="btn-cancel">Annuler</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <hr class="divider">

            <section class="reservation-form-box">
                <h2>Nouvelle réservation</h2>
                
                <form action="reservation.php" method="POST" class="grid-form">
                    <input type="hidden" name="action" value="reserver">
                    
                    <div class="input-group">
                        <label for="date_reservation">Date prévue</label>
                        <input type="date" id="date_reservation" name="date_reservation" required>
                    </div>

                    <div class="input-group">
                        <label for="type_creneau_id">Type de créneau</label>
                        <select id="type_creneau_id" name="type_creneau_id" required>
                            <option value="">-- Choisir un type --</option>
                            <?php foreach ($types_creneau as $tc): ?>
                                <option value="<?= $tc['type_creneau_id'] ?>"><?= htmlspecialchars($tc['libelle_type_creneau']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="periode_id">Période du créneau</label>
                        <select id="periode_id" name="periode_id" required onchange="mettreAJourHeures()">
                            <option value="">-- Choisir la période --</option>
                            <?php foreach ($periodes as $p): ?>
                                <option value="<?= $p['periode_id'] ?>"><?= htmlspecialchars($p['libelle_periode']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="heure_reservation">Heure prévue</label>
                        <select id="heure_reservation" name="heure_reservation" required>
                            <option value="">-- Sélectionnez d'abord une période --</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="pont_id">Sélectionnez le pont</label>
                        <select id="pont_id" name="pont_id" required>
                            <option value="">-- Choisir un pont --</option>
                            <?php foreach ($ponts as $p): ?>
                                <option value="<?= $p['pont_id'] ?>"><?= htmlspecialchars($p['libelle_pont']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="bateau_id">Sélectionnez le bateau</label>
                        <select id="bateau_id" name="bateau_id" required>
                            <option value="">-- Choisir un bateau --</option>
                            <?php foreach ($bateaux as $b): ?>
                                <option value="<?= $b['bateau_id'] ?>"><?= htmlspecialchars($b['libelle_bateau']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="submit-group">
                        <button type="submit" class="btn-submit">Confirmer la réservation</button>
                    </div>
                </form>
            </section>

        </div>
    </main>

    <script src="js/script.js?v=<?= time(); ?>"></script>
</body>
</html>