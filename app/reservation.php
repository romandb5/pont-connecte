<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
// On vérifie si l'utilisateur est un Administrateur (ID 1)
$is_admin = ($_SESSION['type_user_id'] == 1);

// Libellé du rôle pour l'affichage du menu
if ($_SESSION['type_user_id'] == 1) {
    $role = 'Administrateur';
} elseif ($_SESSION['type_user_id'] == 2) {
    $role = 'Opérateur Pont';
} else {
    $role = 'Utilisateur';
}

$message = "";
$message_type = "";

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

try {
    $pdo = new PDO("mysql:host=db-web;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================================
    // TRAITEMENT DES ACTIONS (POST)
    // ==========================================
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // ACTION PLAISANCIER : AJOUTER
        if (isset($_POST['action']) && $_POST['action'] == 'reserver' && !$is_admin) {
            $date_only = $_POST['date_reservation'];
            $combo_heure = $_POST['heure_reservation']; 
            
            if (!empty($date_only) && !empty($combo_heure) && !empty($_POST['pont_id']) && !empty($_POST['bateau_id'])) {
                list($horaires_id, $heure_precise) = explode('|', $combo_heure);
                $date_complete = $date_only . ' ' . $heure_precise; 
                
                $pont_id = $_POST['pont_id'];
                $bateau_id = $_POST['bateau_id'];
                $status_id = 1; // 1 = En attente

                $check_sql = "SELECT RESERVATION_ID FROM RESERVATION WHERE BATEAU_ID = :bateau AND DATE_RESERVATION = :date_res";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute(['bateau' => $bateau_id, 'date_res' => $date_complete]);

                if ($check_stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = "Ce bateau a déjà une réservation à cette heure exacte.";
                    $_SESSION['flash_type'] = "error";
                } else {
                    $sql = "INSERT INTO RESERVATION (USER_ID, PONT_ID, BATEAU_ID, STATUS_ID, HORAIRES_ID, DATE_RESERVATION) 
                            VALUES (:user, :pont, :bateau, :status, :horaires, :date_res)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'user' => $user_id, 'pont' => $pont_id, 'bateau' => $bateau_id,
                        'status' => $status_id, 'horaires' => $horaires_id, 'date_res' => $date_complete
                    ]);
                    $_SESSION['flash_message'] = "Réservation envoyée pour validation.";
                    $_SESSION['flash_type'] = "success";
                }
                header("Location: reservation.php");
                exit();
            }
        }
        
        // ACTION PLAISANCIER : ANNULER
        elseif (isset($_POST['action']) && $_POST['action'] == 'annuler' && !$is_admin) {
            $res_id = $_POST['reservation_id'];
            $sql = "DELETE FROM RESERVATION WHERE RESERVATION_ID = :res_id AND USER_ID = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['res_id' => $res_id, 'user_id' => $user_id]);
            $_SESSION['flash_message'] = "Votre réservation a été annulée.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }

        // ACTION ADMIN : VALIDER
        elseif (isset($_POST['action']) && $_POST['action'] == 'valider' && $is_admin) {
            $res_id = $_POST['reservation_id'];
            $sql = "UPDATE RESERVATION SET STATUS_ID = 2 WHERE RESERVATION_ID = :res_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['res_id' => $res_id]);
            $_SESSION['flash_message'] = "La réservation a été validée avec succès.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }

        // ACTION ADMIN : REFUSER 
        elseif (isset($_POST['action']) && $_POST['action'] == 'refuser' && $is_admin) {
            $res_id = $_POST['reservation_id'];
            $sql = "DELETE FROM RESERVATION WHERE RESERVATION_ID = :res_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['res_id' => $res_id]);
            $_SESSION['flash_message'] = "La réservation a été refusée et supprimée du système.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }
        
        // ACTION ADMIN : OUVRIR LE PONT
        elseif (isset($_POST['action']) && $_POST['action'] == 'ouvrir_pont' && $is_admin) {
            // ICI : Vous pourrez ajouter plus tard la logique MQTT ou API pour envoyer l'ordre à l'ESP32
            $_SESSION['flash_message'] = "Commande envoyée : Le pont est en cours d'OUVERTURE.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }

        // ACTION ADMIN : FERMER LE PONT
        elseif (isset($_POST['action']) && $_POST['action'] == 'fermer_pont' && $is_admin) {
            // ICI : Vous pourrez ajouter plus tard la logique MQTT ou API pour envoyer l'ordre à l'ESP32
            $_SESSION['flash_message'] = "Commande envoyée : Le pont est en cours de FERMETURE.";
            $_SESSION['flash_type'] = "success";
            header("Location: reservation.php");
            exit(); 
        }
    }

    // ==========================================
    // RÉCUPÉRATION DES DONNÉES (POUR AFFICHAGE)
    // ==========================================
    
    // Requête principale commune
    $sql_mes_res = "SELECT r.RESERVATION_ID, r.DATE_RESERVATION, p.LIBELLE_PONT, b.LIBELLE_BATEAU, pc.LIBELLE_PERIODE, d.LIBELLE_DIRECTION_CRENEAU, s.LIBELLE_STATUS, u.USER_NAME
                    FROM RESERVATION r 
                    JOIN PONTS p ON r.PONT_ID = p.PONT_ID 
                    JOIN BATEAUX b ON r.BATEAU_ID = b.BATEAU_ID 
                    JOIN HORAIRES_CRENEAUX hc ON r.HORAIRES_ID = hc.HORAIRES_ID
                    JOIN PERIODE_CRENEAU pc ON hc.PERIODE_ID = pc.PERIODE_ID
                    JOIN DIRECTION_CRENEAU d ON hc.DIRECTION_CRENEAU_ID = d.DIRECTION_CRENEAU_ID
                    JOIN STATUS s ON r.STATUS_ID = s.STATUS_ID
                    JOIN USERS u ON r.USER_ID = u.USER_ID ";

    // Si ce n'est PAS un admin, on filtre pour ne voir QUE ses propres réservations
    if (!$is_admin) {
        $sql_mes_res .= " WHERE r.USER_ID = :user_id ";
    }
    
    $sql_mes_res .= " ORDER BY r.DATE_RESERVATION ASC";
    
    $stmt = $pdo->prepare($sql_mes_res);
    
    if (!$is_admin) {
        $stmt->execute(['user_id' => $user_id]);
    } else {
        $stmt->execute(); // L'admin voit tout le monde
    }
    $reservations_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si c'est un utilisateur classique, on charge les données du formulaire
    if (!$is_admin) {
        $ponts = $pdo->query("SELECT PONT_ID, LIBELLE_PONT FROM PONTS")->fetchAll(PDO::FETCH_ASSOC);
        $stmt_bateaux = $pdo->prepare("SELECT BATEAU_ID, LIBELLE_BATEAU FROM BATEAUX WHERE USER_ID = :user_id");
        $stmt_bateaux->execute(['user_id' => $user_id]);
        $bateaux = $stmt_bateaux->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_horaires = "SELECT hc.HORAIRES_ID, hc.HORAIRE_DEPART, hc.HORAIRE_PASSAGE1, hc.HORAIRE_PASSAGE2, hc.HORAIRE_PASSAGE3, 
                                pc.PERIODE_ID, pc.LIBELLE_PERIODE, d.DIRECTION_CRENEAU_ID, d.LIBELLE_DIRECTION_CRENEAU 
                         FROM HORAIRES_CRENEAUX hc
                         JOIN PERIODE_CRENEAU pc ON hc.PERIODE_ID = pc.PERIODE_ID
                         JOIN DIRECTION_CRENEAU d ON hc.DIRECTION_CRENEAU_ID = d.DIRECTION_CRENEAU_ID
                         ORDER BY pc.PERIODE_ID, d.DIRECTION_CRENEAU_ID, hc.HORAIRE_DEPART ASC";
        $raw_horaires = $pdo->query($sql_horaires)->fetchAll(PDO::FETCH_ASSOC);

        $creneaux_groupes = [];
        foreach ($raw_horaires as $row) {
            $key = $row['PERIODE_ID'] . '_' . $row['DIRECTION_CRENEAU_ID'];
            if (!isset($creneaux_groupes[$key])) {
                $creneaux_groupes[$key] = [
                    'label' => $row['LIBELLE_PERIODE'] . ' - ' . $row['LIBELLE_DIRECTION_CRENEAU'],
                    'heures' => []
                ];
            }
            if ($row['HORAIRE_DEPART'])   $creneaux_groupes[$key]['heures'][] = $row['HORAIRES_ID'] . '|' . $row['HORAIRE_DEPART'];
            if ($row['HORAIRE_PASSAGE1']) $creneaux_groupes[$key]['heures'][] = $row['HORAIRES_ID'] . '|' . $row['HORAIRE_PASSAGE1'];
            if ($row['HORAIRE_PASSAGE2']) $creneaux_groupes[$key]['heures'][] = $row['HORAIRES_ID'] . '|' . $row['HORAIRE_PASSAGE2'];
            if ($row['HORAIRE_PASSAGE3']) $creneaux_groupes[$key]['heures'][] = $row['HORAIRES_ID'] . '|' . $row['HORAIRE_PASSAGE3'];
        }
    }

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
    <style>
        .bridge-control-panel {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            margin-top: 40px;
            border-top: 5px solid #FFB703; 
        }
        
        .bridge-control-panel h2 {
            color: #003366;
            margin-bottom: 5px;
        }
        
        .bridge-control-buttons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 25px;
        }
        
        .btn-open, .btn-close {
            background-color: #003366; 
            color: white;
            border: 2px solid #003366;
            padding: 12px 40px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        
        .btn-open:hover, .btn-close:hover {
            background-color: #FFB703; 
            color: #003366;
            border-color: #FFB703;
        }

        /* =========================================
           MODAL DE CONFIRMATION PERSONNALISÉ
           ========================================= */
        .custom-modal-overlay {
            display: none; 
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 51, 102, 0.7); 
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px); 
        }
        
        .custom-modal {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            max-width: 450px;
            text-align: center;
            border-top: 5px solid #FFB703;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .custom-modal h3 { color: #003366; margin-top: 0; font-size: 1.4rem; }
        .custom-modal p { color: #555; margin-bottom: 25px; line-height: 1.5; }
        .modal-buttons { display: flex; justify-content: center; gap: 15px; }
        
        .btn-cancel-modal {
            background: #e0e0e0; color: #333; border: none; padding: 10px 25px; 
            border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s;
        }
        .btn-cancel-modal:hover { background: #c8c8c8; }
        
        .btn-confirm-modal {
            background: #003366; color: white; border: none; padding: 10px 25px; 
            border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s;
        }
        .btn-confirm-modal:hover { background: #FFB703; color: #003366; }
    </style>
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
            <?php if ($_SESSION['type_user_id'] == 1 || $_SESSION['type_user_id'] == 2): ?>
                <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
                <li><a href="historique.php">Historique passages</a></li>
            <?php endif; ?>
            <li><a href="#">Aide</a></li>
        </ul>

        <div class="sidebar-footer"><a href="logout.php" class="btn-logout">Déconnexion</a></div>
    </aside>

    <main class="main-content">
        
        <div class="logo-central-container">
            <img src="assets/logo%20pont.png" alt="Logo PontConnect">
        </div>

        <div class="welcome-banner">
            <?php if ($is_admin): ?>
                <h1>ADMINISTRATION DES RÉSERVATIONS 🛡️</h1>
                <p>Validez ou refusez les demandes de passage des utilisateurs.</p>
            <?php else: ?>
                <h1>GESTION DES RÉSERVATIONS 📅</h1>
                <p>Planifiez vos passages sous les ponts en quelques clics.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <section class="reservations-list" style="width: 100%;">
                <h2>Toutes les demandes en attente / validées</h2>
                <?php if (empty($reservations_liste)): ?>
                    <p style="color: gray; text-align: center;">Aucune réservation dans le système.</p>
                <?php else: ?>
                    <div class="cards-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                        <?php foreach ($reservations_liste as $res): ?>
                            <div class="res-card">
                                <div>
                                    <div class="res-date">
                                        📅 <?= date('d/m/Y H:i', strtotime($res['DATE_RESERVATION'])) ?>
                                    </div>
                                    <div>
                                        <strong>👤 Utilisateur :</strong> <span style="color: var(--dk-yellow); font-weight: bold;"><?= htmlspecialchars($res['USER_NAME']) ?></span><br>
                                        <strong>⚓ Bateau :</strong> <?= htmlspecialchars($res['LIBELLE_BATEAU']) ?> <br>
                                        <strong>🌉 Pont :</strong> <?= htmlspecialchars($res['LIBELLE_PONT']) ?> <br>
                                        <strong>⏱️ Période :</strong> <?= htmlspecialchars($res['LIBELLE_PERIODE']) ?> - <?= htmlspecialchars($res['LIBELLE_DIRECTION_CRENEAU']) ?> <br>
                                        <em>Statut : <?= htmlspecialchars($res['LIBELLE_STATUS']) ?></em>
                                    </div>
                                </div>
                                
                                <div class="admin-actions">
                                    <form action="reservation.php" method="POST" style="flex: 1;">
                                        <input type="hidden" name="action" value="valider">
                                        <input type="hidden" name="reservation_id" value="<?= $res['RESERVATION_ID'] ?>">
                                        <button type="submit" class="btn-validate">Valider</button>
                                    </form>
                                    <form action="reservation.php" method="POST" style="flex: 1;" onsubmit="return confirm('Êtes-vous sûr de vouloir refuser et supprimer cette demande ?');">
                                        <input type="hidden" name="action" value="refuser">
                                        <input type="hidden" name="reservation_id" value="<?= $res['RESERVATION_ID'] ?>">
                                        <button type="submit" class="btn-refuse">Refuser</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- PANNEAU DE CONTRÔLE DU PONT -->
            <section class="bridge-control-panel">
                <h2>Contrôle Manuel du Pont</h2>
                <p style="color: gray;">Actionnez les moteurs pour laisser passer les navires.</p>
                
                <div class="bridge-control-buttons">
                    <form id="form-ouvrir" action="reservation.php" method="POST">
                        <input type="hidden" name="action" value="ouvrir_pont">
                        <button type="button" class="btn-open" onclick="ouvrirModalCustom('ouvrir')">Ouvrir le pont</button>
                    </form>

                    <form id="form-fermer" action="reservation.php" method="POST">
                        <input type="hidden" name="action" value="fermer_pont">
                        <button type="button" class="btn-close" onclick="ouvrirModalCustom('fermer')">Fermer le pont</button>
                    </form>
                </div>
            </section>

        <?php else: ?>
            <div class="dashboard-grid">
                
                <section class="reservations-list">
                    <h2>Mes demandes actives</h2>
                    <?php if (empty($reservations_liste)): ?>
                        <p style="color: gray; text-align: center; margin-top: 20px;">Vous n'avez aucune réservation.</p>
                    <?php else: ?>
                        <div class="cards-container">
                            <?php foreach ($reservations_liste as $res): ?>
                                <div class="res-card">
                                    <div>
                                        <div class="res-date">
                                            📅 <?= date('d/m/Y à H:i', strtotime($res['DATE_RESERVATION'])) ?>
                                        </div>
                                        <div>
                                            <strong>Pont :</strong> <?= htmlspecialchars($res['LIBELLE_PONT']) ?> <br>
                                            <strong>Bateau :</strong> <?= htmlspecialchars($res['LIBELLE_BATEAU']) ?> <br>
                                            <strong>Période :</strong> <?= htmlspecialchars($res['LIBELLE_PERIODE']) ?> - <?= htmlspecialchars($res['LIBELLE_DIRECTION_CRENEAU']) ?> <br>
                                            <em>Statut : <?= htmlspecialchars($res['LIBELLE_STATUS']) ?></em>
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

                <section class="reservation-form-box">
                    <h2>Nouvelle réservation</h2>
                    <form action="reservation.php" method="POST">
                        <input type="hidden" name="action" value="reserver">
                        <div class="input-group">
                            <label for="date_reservation">Date prévue</label>
                            <input type="date" id="date_reservation" name="date_reservation" required>
                        </div>
                        <div class="input-group">
                            <label for="bloc_creneau">Période du passage</label>
                            <select id="bloc_creneau" name="bloc_creneau" required onchange="mettreAJourHeuresPrecises()">
                                <option value="">-- Choisir une période --</option>
                                <?php foreach ($creneaux_groupes as $key => $groupe): ?>
                                    <option value="<?= $key ?>" data-heures='<?= json_encode($groupe['heures']) ?>'>
                                        <?= htmlspecialchars($groupe['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="heure_reservation">Heure exacte</label>
                            <select id="heure_reservation" name="heure_reservation" required>
                                <option value="">-- Sélectionnez d'abord une période --</option>
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
                        <button type="submit" class="btn-submit">Confirmer la réservation</button>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <?php if (!$is_admin): ?>
        <script src="js/script.js?v=<?= time(); ?>"></script>
    <?php endif; ?>

    <div class="custom-modal-overlay" id="modal-securite">
        <div class="custom-modal">
            <h3 id="modal-titre">Confirmation Requise</h3>
            <p id="modal-texte">Êtes-vous sûr ?</p>
            <div class="modal-buttons">
                <button class="btn-cancel-modal" onclick="fermerModalCustom()">Annuler</button>
                <button class="btn-confirm-modal" id="btn-valider-modal">Confirmer l'action</button>
            </div>
        </div>
    </div>

    <script>
        let formulaireEnAttente = ''; // Va stocker l'ID du formulaire à envoyer

        // Fonction pour ouvrir la fenêtre et changer le texte selon le bouton cliqué
        function ouvrirModalCustom(action) {
            const modal = document.getElementById('modal-securite');
            const titre = document.getElementById('modal-titre');
            const texte = document.getElementById('modal-texte');

            if (action === 'ouvrir') {
                titre.innerText = '⚠️ Ouverture du Pont';
                texte.innerText = 'Êtes-vous sûr de vouloir OUVRIR le pont à la circulation maritime ? Cette action bloquera la route.';
                formulaireEnAttente = 'form-ouvrir';
            } else if (action === 'fermer') {
                titre.innerText = '⚠️ Fermeture du Pont';
                texte.innerText = 'Êtes-vous sûr de vouloir FERMER le pont ? Vérifiez qu\'aucun navire n\'est engagé avant de rétablir la route.';
                formulaireEnAttente = 'form-fermer';
            }

            modal.style.display = 'flex'; // Affiche la fenêtre
        }

        // Fonction pour cacher la fenêtre
        function fermerModalCustom() {
            document.getElementById('modal-securite').style.display = 'none';
            formulaireEnAttente = '';
        }

        // Si l'admin clique sur "Confirmer", on valide enfin le formulaire stocké
        document.getElementById('btn-valider-modal').addEventListener('click', function() {
            if (formulaireEnAttente !== '') {
                document.getElementById(formulaireEnAttente).submit();
            }
        });
    </script>
</body>
</html>