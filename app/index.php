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

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

try {
    $pdo = new PDO("mysql:host=db;dbname=pontconnecte;charset=utf8", "Etudiant", "P@ssword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- AJOUTER UN BATEAU ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_bateau') {
        $libelle = trim($_POST['libelle_bateau']);
        $immatriculation = trim($_POST['immatriculation']);
        $hauteur = trim($_POST['hauteur_max']);
        $direction_id = $_POST['direction_id'];

        if (!empty($libelle) && !empty($immatriculation) && !empty($hauteur) && !empty($direction_id)) {
            $sql = "INSERT INTO BATEAUX (DIRECTION_CRENEAU_ID, USER_ID, LIBELLE_BATEAU, IMMATRICULATION, HAUTEUR_MAX, CREATED_AT) 
                    VALUES (:dir, :user, :libelle, :immat, :hauteur, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'dir' => $direction_id, 'user' => $user_id, 'libelle' => $libelle,
                'immat' => $immatriculation, 'hauteur' => str_replace(',', '.', $hauteur) 
            ]);

            $_SESSION['flash_message'] = "Le bateau a été ajouté avec succès !";
            $_SESSION['flash_type'] = "success";
            header("Location: index.php");
            exit();
        }
    }

    // --- SUPPRIMER UN BATEAU ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_bateau') {
        $bateau_id_to_delete = $_POST['bateau_id'];
        
        $check_res = $pdo->prepare("SELECT RESERVATION_ID FROM RESERVATION WHERE BATEAU_ID = :bateau_id");
        $check_res->execute(['bateau_id' => $bateau_id_to_delete]);
        
        if ($check_res->rowCount() > 0) {
            $_SESSION['flash_message'] = "Impossible de supprimer ce bateau car il possède des réservations actives.";
            $_SESSION['flash_type'] = "error";
        } else {
            $stmt_del = $pdo->prepare("DELETE FROM BATEAUX WHERE BATEAU_ID = :bateau_id AND USER_ID = :user_id");
            $stmt_del->execute(['bateau_id' => $bateau_id_to_delete, 'user_id' => $user_id]);
            $_SESSION['flash_message'] = "Le bateau a été supprimé.";
            $_SESSION['flash_type'] = "success";
        }
        header("Location: index.php");
        exit();
    }

    // --- RÉCUPÉRATION DES DONNÉES ---
    $sql_mes_bateaux = "SELECT b.BATEAU_ID, b.LIBELLE_BATEAU, b.IMMATRICULATION, b.HAUTEUR_MAX, d.LIBELLE_DIRECTION_CRENEAU 
                        FROM BATEAUX b
                        JOIN DIRECTION_CRENEAU d ON b.DIRECTION_CRENEAU_ID = d.DIRECTION_CRENEAU_ID
                        WHERE b.USER_ID = :user_id 
                        ORDER BY b.CREATED_AT DESC";
    $stmt_bateaux = $pdo->prepare($sql_mes_bateaux);
    $stmt_bateaux->execute(['user_id' => $user_id]);
    $mes_bateaux = $stmt_bateaux->fetchAll(PDO::FETCH_ASSOC);

    $directions = $pdo->query("SELECT DIRECTION_CRENEAU_ID, LIBELLE_DIRECTION_CRENEAU FROM DIRECTION_CRENEAU")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Erreur de base de données : " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PontConnect - Accueil</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">
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
                <span class="user-role"><?= $role ?></span>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="index.php" class="active">Accueil</a></li>
            <li><a href="reservation.php">Réservation</a></li> 
            <li><a href="gestion-capteur.php">Gestion Capteur</a></li>
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
            <h1>TABLEAU DE BORD - <?= strtoupper(htmlspecialchars($username)) ?> ⚓</h1>
            <p>Gérez votre flotte de navires en un clin d'œil.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            
            <section class="reservations-list">
                <h2>Ma Flotte (<?= count($mes_bateaux) ?>)</h2>
                <?php if (empty($mes_bateaux)): ?>
                    <p style="color: gray; text-align: center; margin-top: 20px;">Aucun navire enregistré.</p>
                <?php else: ?>
                    <div class="cards-container">
                        <?php foreach ($mes_bateaux as $bateau): ?>
                            <div class="res-card">
                                <div>
                                    <div class="res-date">🛥️ <?= htmlspecialchars($bateau['LIBELLE_BATEAU']) ?></div>
                                    <div>
                                        <strong>Immat :</strong> <?= htmlspecialchars($bateau['IMMATRICULATION']) ?> <br>
                                        <strong>Hauteur :</strong> <?= htmlspecialchars($bateau['HAUTEUR_MAX']) ?> m <br>
                                        <strong>Direction :</strong> <?= htmlspecialchars($bateau['LIBELLE_DIRECTION_CRENEAU']) ?>
                                    </div>
                                </div>
                                <form action="index.php" method="POST" onsubmit="return confirm('Retirer ce bateau ?');">
                                    <input type="hidden" name="action" value="delete_bateau">
                                    <input type="hidden" name="bateau_id" value="<?= $bateau['BATEAU_ID'] ?>">
                                    <button type="submit" class="btn-cancel">Retirer</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="reservation-form-box">
                <h2>Ajouter un navire</h2>
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="add_bateau">
                    
                    <div class="input-group">
                        <label>Nom du navire</label>
                        <input type="text" name="libelle_bateau" required placeholder="Ex: L'Étoile des Mers">
                    </div>
                    
                    <div class="input-group">
                        <label>Immatriculation</label>
                        <input type="text" name="immatriculation" required placeholder="Ex: DK-123456">
                    </div>
                    
                    <div class="input-group">
                        <label>Hauteur (en m)</label>
                        <input type="number" step="0.01" name="hauteur_max" required placeholder="12.50">
                    </div>
                    
                    <div class="input-group">
                        <label>Direction habituelle</label>
                        <select name="direction_id" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($directions as $dir): ?>
                                <option value="<?= $dir['DIRECTION_CRENEAU_ID'] ?>"><?= htmlspecialchars($dir['LIBELLE_DIRECTION_CRENEAU']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">Enregistrer le navire</button>
                </form>
            </section>

        </div>
    </main>
</body>
</html>