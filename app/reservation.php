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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST['action']) && $_POST['action'] == 'reserver') {
            $date_only = $_POST['date_reservation'];
            // On récupère maintenant l'heure précise choisie dans la liste déroulante
            $heure_precise = $_POST['heure_reservation']; 
            $date_complete = $date_only . ' ' . $heure_precise; 
            
            $horaires_id = $_POST['horaires_id'];
            $pont_id = $_POST['pont_id'];
            $bateau_id = $_POST['bateau_id'];
            $status_id = 1; 

            if (!empty($date_only) && !empty($heure_precise) && !empty($horaires_id) && !empty($pont_id) && !empty($bateau_id)) {
                
                // VÉRIFICATION DE DISPONIBILITÉ DU BATEAU
                $check_sql = "SELECT RESERVATION_ID FROM RESERVATION WHERE BATEAU_ID = :bateau AND DATE_RESERVATION = :date_res";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    'bateau' => $bateau_id,
                    'date_res' => $date_complete
                ]);

                if ($check_stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = "Erreur : Ce bateau a déjà une réservation prévue à cette heure exacte.";
                    $_SESSION['flash_type'] = "error";
                    header("Location: reservation.php");
                    exit();
                } 
                else {
                    $sql = "INSERT INTO RESERVATION (USER_ID, PONT_ID, BATEAU_ID, STATUS_ID, HORAIRES_ID, DATE_RESERVATION) 
                            VALUES (:user, :pont, :bateau, :status, :horaires, :date_res)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'user' => $user_id,
                        'pont' => $pont_id,
                        'bateau' => $bateau_id,
                        'status' => $status_id,
                        'horaires' => $horaires_id,
                        'date_res' => $date_complete
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
            $sql = "DELETE FROM RESERVATION WHERE RESERVATION_ID = :res_id AND USER_ID = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['res_id' => $res_id, 'user_id' => $user_id]);
            
            $_SESSION['flash_message'] = "La réservation a été annulée.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }
    }

    // --- RÉCUPÉRATION DES DONNÉES ---
    $sql_mes_res = "SELECT r.RESERVATION_ID, r.DATE_RESERVATION, p.LIBELLE_PONT, b.LIBELLE_BATEAU, pc.LIBELLE_PERIODE, d.LIBELLE_DIRECTION_CRENEAU, s.LIBELLE_STATUS
                    FROM RESERVATION r 
                    JOIN PONTS p ON r.PONT_ID = p.PONT_ID 
                    JOIN BATEAUX b ON r.BATEAU_ID = b.BATEAU_ID 
                    JOIN HORAIRES_CRENEAUX hc ON r.HORAIRES_ID = hc.HORAIRES_ID
                    JOIN PERIODE_CRENEAU pc ON hc.PERIODE_ID = pc.PERIODE_ID
                    JOIN DIRECTION_CRENEAU d ON hc.DIRECTION_CRENEAU_ID = d.DIRECTION_CRENEAU_ID
                    JOIN STATUS s ON r.STATUS_ID = s.STATUS_ID
                    WHERE r.USER_ID = :user_id 
                    ORDER BY r.DATE_RESERVATION ASC";
    $stmt = $pdo->prepare($sql_mes_res);
    $stmt->execute(['user_id' => $user_id]);
    $mes_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ponts = $pdo->query("SELECT PONT_ID, LIBELLE_PONT FROM PONTS")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_bateaux = $pdo->prepare("SELECT BATEAU_ID, LIBELLE_BATEAU FROM BATEAUX WHERE USER_ID = :user_id");
    $stmt_bateaux->execute(['user_id' => $user_id]);
    $bateaux = $stmt_bateaux->fetchAll(PDO::FETCH_ASSOC);
    
    // MISE À JOUR : On récupère tous les passages (Départ, 1, 2, 3) depuis la base
    $sql_horaires = "SELECT hc.HORAIRES_ID, hc.HORAIRE_DEPART, hc.HORAIRE_PASSAGE1, hc.HORAIRE_PASSAGE2, hc.HORAIRE_PASSAGE3, pc.LIBELLE_PERIODE, d.LIBELLE_DIRECTION_CRENEAU 
                     FROM HORAIRES_CRENEAUX hc
                     JOIN PERIODE_CRENEAU pc ON hc.PERIODE_ID = pc.PERIODE_ID
                     JOIN DIRECTION_CRENEAU d ON hc.DIRECTION_CRENEAU_ID = d.DIRECTION_CRENEAU_ID
                     ORDER BY hc.HORAIRE_DEPART ASC";
    $horaires = $pdo->query($sql_horaires)->fetchAll(PDO::FETCH_ASSOC);

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
                                        📅 <?= date('d/m/Y à H:i', strtotime($res['DATE_RESERVATION'])) ?> 
                                        <span class="badge-periode"><?= htmlspecialchars($res['LIBELLE_PERIODE']) ?> - <?= htmlspecialchars($res['LIBELLE_DIRECTION_CRENEAU']) ?></span>
                                    </div>
                                    <div class="res-details">
                                        <strong>Pont :</strong> <?= htmlspecialchars($res['LIBELLE_PONT']) ?> &nbsp;|&nbsp; 
                                        <strong>Bateau :</strong> <?= htmlspecialchars($res['LIBELLE_BATEAU']) ?> <br>
                                        <small><em>Statut : <?= htmlspecialchars($res['LIBELLE_STATUS']) ?></em></small>
                                    </div>
                                </div>
                                <form action="reservation.php" method="POST" onsubmit="return confirm('Annuler cette réservation ?');">
                                    <input type="hidden" name="action" value="annuler">
                                    <input type="hidden" name="reservation_id" value="<?= $res['RESERVATION_ID'] ?>">
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
                        <label for="horaires_id">Bloc Créneau</label>
                        <select id="horaires_id" name="horaires_id" required onchange="mettreAJourHeuresPrecises()">
                            <option value="">-- Choisir un créneau --</option>
                            <?php foreach ($horaires as $h): ?>
                                <option value="<?= $h['HORAIRES_ID'] ?>"
                                        data-h0="<?= $h['HORAIRE_DEPART'] ?>"
                                        data-h1="<?= $h['HORAIRE_PASSAGE1'] ?>"
                                        data-h2="<?= $h['HORAIRE_PASSAGE2'] ?>"
                                        data-h3="<?= $h['HORAIRE_PASSAGE3'] ?>">
                                    <?= htmlspecialchars($h['LIBELLE_PERIODE']) ?> - <?= htmlspecialchars($h['LIBELLE_DIRECTION_CRENEAU']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="heure_reservation">Heure précise de passage</label>
                        <select id="heure_reservation" name="heure_reservation" required>
                            <option value="">-- Sélectionnez d'abord un créneau --</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="pont_id">Sélectionnez le pont</label>
                        <select id="pont_id" name="pont_id" required>
                            <option value="">-- Choisir un pont --</option>
                            <?php foreach ($ponts as $p): ?>
                                <option value="<?= $p['PONT_ID'] ?>"><?= htmlspecialchars($p['LIBELLE_PONT']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="bateau_id">Sélectionnez le bateau</label>
                        <select id="bateau_id" name="bateau_id" required>
                            <?php if(empty($bateaux)): ?>
                                <option value="">Aucun bateau enregistré</option>
                            <?php else: ?>
                                <option value="">-- Choisir un bateau --</option>
                                <?php foreach ($bateaux as $b): ?>
                                    <option value="<?= $b['BATEAU_ID'] ?>"><?= htmlspecialchars($b['LIBELLE_BATEAU']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
