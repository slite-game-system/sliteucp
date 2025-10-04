<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Felhasználó adatainak lekérése
$user_id = $_SESSION['user_id'];
$user_stmt = $pdo->prepare("SELECT * FROM accounts WHERE ".ACCOUNTS_ID." = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Admin jogosultság ellenőrzése
if($user[ACCOUNTS_ADMINLEVEL] < 1) {
    header("Location: dashboard.php");
    exit();
}

// Változók inicializálása
$message = '';
$error = '';
$search_results = [];
$player_stats = [];

// Játékos keresés
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_player'])) {
    $search_term = $_POST['search_term'] ?? '';
    
    if(!empty($search_term)) {
        try {
            $search_stmt = $pdo->prepare("SELECT * FROM accounts WHERE ".ACCOUNTS_USERNAME." LIKE ? OR ".ACCOUNTS_EMAIL." LIKE ?");
            $search_stmt->execute(["%$search_term%", "%$search_term%"]);
            $search_results = $search_stmt->fetchAll();
            
            if(empty($search_results)) {
                $message = "Nincs találat a keresésre: " . safe_output($search_term);
            }
        } catch(Exception $e) {
            $error = "Keresési hiba: " . $e->getMessage();
        }
    } else {
        $error = "Kérjük adj meg egy keresési kifejezést!";
    }
}

// Játékos adatok lekérése
if(isset($_GET['view_player'])) {
    $player_id = $_GET['view_player'];
    
    try {
        $player_stmt = $pdo->prepare("SELECT * FROM accounts WHERE ".ACCOUNTS_ID." = ?");
        $player_stmt->execute([$player_id]);
        $player_stats = $player_stmt->fetch();
        
        if($player_stats) {
            // Karakterek lekérése
            $chars_stmt = $pdo->prepare("SELECT * FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
            $chars_stmt->execute([$player_id]);
            $player_stats['characters'] = $chars_stmt->fetchAll();
            
            // Járművek lekérése
            $char_ids = array_column($player_stats['characters'], CHARACTERS_ID);
            if(!empty($char_ids)) {
                $placeholders = str_repeat('?,', count($char_ids) - 1) . '?';
                $veh_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vehicles WHERE ".VEHICLES_OWNER." IN ($placeholders)");
                $veh_stmt->execute($char_ids);
                $player_stats['vehicle_count'] = $veh_stmt->fetch()['count'];
            } else {
                $player_stats['vehicle_count'] = 0;
            }
            
            // Ingatlanok lekérése
            $int_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interiors WHERE ".INTERIORS_OWNER." = ?");
            $int_stmt->execute([$player_id]);
            $player_stats['interior_count'] = $int_stmt->fetch()['count'];
        }
    } catch(Exception $e) {
        $error = "Adatlekérési hiba: " . $e->getMessage();
    }
}

// Pénz kezelés
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_money'])) {
    $target_player = $_POST['player_id'] ?? '';
    $amount = intval($_POST['amount'] ?? 0);
    $action = $_POST['action'] ?? '';
    $character_id = $_POST['character_id'] ?? '';
    
    if($target_player && $amount > 0 && $character_id) {
        try {
            // Ellenőrizzük, hogy a karakter a kiválasztott játékoshoz tartozik-e
            $check_stmt = $pdo->prepare("SELECT * FROM characters WHERE ".CHARACTERS_ID." = ? AND ".CHARACTERS_ACCOUNT." = ?");
            $check_stmt->execute([$character_id, $target_player]);
            $character = $check_stmt->fetch();
            
            if($character) {
                $current_money = $character[CHARACTERS_MONEY] ?? 0;
                
                if($action == 'add') {
                    $new_money = $current_money + $amount;
                    $message = "Sikeresen hozzáadva $amount$ a karakterhez!";
                } else {
                    $new_money = max(0, $current_money - $amount);
                    $message = "Sikeresen eltávolítva $amount$ a karakterből!";
                }
                
                $update_stmt = $pdo->prepare("UPDATE characters SET ".CHARACTERS_MONEY." = ? WHERE ".CHARACTERS_ID." = ?");
                $update_stmt->execute([$new_money, $character_id]);
                
            } else {
                $error = "A karakter nem található vagy nem ehhez a játékoshoz tartozik!";
            }
        } catch(Exception $e) {
            $error = "Pénzkezelési hiba: " . $e->getMessage();
        }
    } else {
        $error = "Hiányzó vagy érvénytelen adatok!";
    }
}

// Üzenet küldés
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $target_player = $_POST['message_player_id'] ?? '';
    $message_text = $_POST['message_text'] ?? '';
    $message_type = $_POST['message_type'] ?? 'info';
    
    if($target_player && !empty($message_text)) {
        $message = "Üzenet elküldve a játékosnak! (Szimuláció)";
    } else {
        $error = "Hiányzó üzenet vagy céljátékos!";
    }
}

// Kitiltások kezelése
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_ban'])) {
    $target_player = $_POST['ban_player_id'] ?? '';
    $ban_reason = trim($_POST['ban_reason'] ?? '');
    $ban_duration = intval($_POST['ban_duration'] ?? 0);
    $ban_action = $_POST['ban_action'] ?? '';
    
    if($target_player) {
        try {
            // Ellenőrizzük, hogy a játékos létezik-e
            $player_check = $pdo->prepare("SELECT ".ACCOUNTS_USERNAME.", ".ACCOUNTS_ADMINLEVEL." FROM accounts WHERE ".ACCOUNTS_ID." = ?");
            $player_check->execute([$target_player]);
            $target_player_data = $player_check->fetch();
            
            if(!$target_player_data) {
                $error = "A játékos nem található!";
            } elseif($target_player_data[ACCOUNTS_ADMINLEVEL] >= $user[ACCOUNTS_ADMINLEVEL] && $user[ACCOUNTS_ADMINLEVEL] < 6) {
                $error = "Nem tilthatsz ki egy magasabb vagy azonos szintű admint!";
            } else {
                if($ban_action == 'ban') {
                    if(!empty($ban_reason) && $ban_duration > 0) {
                        // Aktív ban ellenőrzése
                        $active_ban_check = $pdo->prepare("SELECT * FROM bans WHERE ".BANS_ACCOUNT_ID." = ? AND ".BANS_ACTIVE." = 1");
                        $active_ban_check->execute([$target_player]);
                        $existing_ban = $active_ban_check->fetch();
                        
                        if($existing_ban) {
                            $error = "A játékosnak már van aktív kitiltása!";
                        } else {
                            // Új ban beszúrása
                            $current_time = time();
                            $expire_timestamp = $current_time + ($ban_duration * 24 * 60 * 60);
                            
                            $insert_stmt = $pdo->prepare("INSERT INTO bans (
                                ".BANS_SERIAL.", 
                                ".BANS_SERIAL2.", 
                                ".BANS_ACCOUNT_ID.", 
                                ".BANS_ADMIN_NAME.", 
                                ".BANS_REASON.", 
                                ".BANS_TIMESTAMP.", 
                                ".BANS_EXPIRE_TIMESTAMP.", 
                                ".BANS_ADMIN_ACCOUNT_ID.", 
                                ".BANS_PLAYER_NAME.", 
                                ".BANS_ACTIVE.", 
                                ".BANS_INACTIVE.", 
                                ".BANS_DEACTIVATED."
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0)");
                            
                            $insert_stmt->execute([
                                '', // serial - üresen hagyjuk, ha nincs adat
                                'UCP_Ban', // serial2
                                $target_player,
                                $user[ACCOUNTS_USERNAME],
                                $ban_reason,
                                $current_time,
                                $expire_timestamp,
                                $user_id,
                                $target_player_data[ACCOUNTS_USERNAME]
                            ]);
                            
                            $message = "Sikeresen kitiltottad a játékost: " . safe_output($target_player_data[ACCOUNTS_USERNAME]) . " (" . $ban_duration . " napra)";
                        }
                    } else {
                        $error = "Kitiltási ok és időtartam megadása kötelező!";
                    }
                } else {
                    // Unban - kitiltás feloldása
                    $active_ban_check = $pdo->prepare("SELECT * FROM bans WHERE ".BANS_ACCOUNT_ID." = ? AND ".BANS_ACTIVE." = 1");
                    $active_ban_check->execute([$target_player]);
                    $existing_ban = $active_ban_check->fetch();
                    
                    if(!$existing_ban) {
                        $error = "A játékosnak nincs aktív kitiltása!";
                    } else {
                        // Ban inaktiválása
                        $update_stmt = $pdo->prepare("UPDATE bans SET 
                            ".BANS_ACTIVE." = 0, 
                            ".BANS_INACTIVE." = 1, 
                            ".BANS_DEACTIVATED." = 1,
                            ".BANS_DEACTIVATED_BY." = ?,
                            ".BANS_DEACTIVATED_BY_NAME." = ?,
                            ".BANS_DEACTIVATE_REASON." = 'Feloldva az UCP-n keresztül',
                            ".BANS_DEACTIVATE_TIMESTAMP." = ?
                        WHERE ".BANS_ID." = ?");
                        
                        $update_stmt->execute([
                            $user_id,
                            $user[ACCOUNTS_USERNAME],
                            time(),
                            $existing_ban[BANS_ID]
                        ]);
                        
                        $message = "Sikeresen feloldottad a kitiltást: " . safe_output($target_player_data[ACCOUNTS_USERNAME]);
                    }
                }
            }
        } catch(Exception $e) {
            $error = "Kitiltási hiba: " . $e->getMessage();
            error_log("Ban error: " . $e->getMessage());
        }
    } else {
        $error = "Válassz ki egy játékost!";
    }
}

// Statisztikák frissítése
if(isset($_GET['refresh_stats'])) {
    $message = "Statisztikák frissítve!";
}

// Aktív kitiltások lekérése
try {
    $active_bans_stmt = $pdo->prepare("
        SELECT b.*, a.".ACCOUNTS_USERNAME." as player_username 
        FROM bans b 
        LEFT JOIN accounts a ON b.".BANS_ACCOUNT_ID." = a.".ACCOUNTS_ID." 
        WHERE b.".BANS_ACTIVE." = 1 
        ORDER BY b.".BANS_TIMESTAMP." DESC 
        LIMIT 20
    ");
    $active_bans_stmt->execute();
    $active_bans = $active_bans_stmt->fetchAll();
} catch(Exception $e) {
    error_log("Active bans error: " . $e->getMessage());
    $active_bans = [];
}

// Admin statisztikák
$total_players = 0;
$online_players = 0;
$total_vehicles = 0;
$total_interiors = 0;
$recent_players = [];

try {
    // Összes játékos
    $players_stmt = $pdo->query("SELECT COUNT(*) as total FROM accounts");
    $total_players = $players_stmt->fetch()['total'];
    
    // Online játékosok
    $online_stmt = $pdo->query("SELECT COUNT(*) as online FROM accounts WHERE ".ACCOUNTS_ONLINE." = 1");
    $online_players = $online_stmt->fetch()['online'];
    
    // Összes jármű
    $vehicles_stmt = $pdo->query("SELECT COUNT(*) as total FROM vehicles");
    $total_vehicles = $vehicles_stmt->fetch()['total'];
    
    // Összes ingatlan
    $interiors_stmt = $pdo->query("SELECT COUNT(*) as total FROM interiors");
    $total_interiors = $interiors_stmt->fetch()['total'];
    
    // Legutóbb belépett játékosok
    $recent_stmt = $pdo->query("SELECT ".ACCOUNTS_ID.", ".ACCOUNTS_USERNAME.", ".ACCOUNTS_LASTLOGIN.", ".ACCOUNTS_ADMINLEVEL.", ".ACCOUNTS_ONLINE." 
                               FROM accounts 
                               ORDER BY ".ACCOUNTS_LASTLOGIN." DESC 
                               LIMIT 10");
    $recent_players = $recent_stmt->fetchAll();
    
    // Összes játékos listája a select-ekhez
    $all_players = $pdo->query("SELECT ".ACCOUNTS_ID.", ".ACCOUNTS_USERNAME." FROM accounts ORDER BY ".ACCOUNTS_USERNAME." LIMIT 100")->fetchAll();
    
} catch(Exception $e) {
    error_log("Admin stats error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #3498db;
            background: #34495e;
        }
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .player-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="pt-3">
                    <!-- Header -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <i class="fas fa-shield-alt me-2 text-warning"></i>Admin Panel
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <span class="badge bg-warning me-2">Admin szint: <?= $user[ACCOUNTS_ADMINLEVEL] ?></span>
                            <div class="btn-group me-2">
                                <a href="?refresh_stats=1" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-sync-alt me-1"></i>Frissítés
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Üzenetek -->
                    <?php if($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Admin statisztikák -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $total_players ?></h4>
                                            <p class="mb-0">Összes játékos</p>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small><i class="fas fa-circle text-success me-1"></i> <?= $online_players ?> online</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $online_players ?></h4>
                                            <p class="mb-0">Online játékos</p>
                                        </div>
                                        <i class="fas fa-signal fa-2x opacity-50"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small><i class="fas fa-percentage me-1"></i> 
                                            <?= $total_players > 0 ? round(($online_players / $total_players) * 100, 1) : 0 ?>% online
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $total_vehicles ?></h4>
                                            <p class="mb-0">Összes jármű</p>
                                        </div>
                                        <i class="fas fa-car fa-2x opacity-50"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small><i class="fas fa-database me-1"></i> Adatbázis</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= $total_interiors ?></h4>
                                            <p class="mb-0">Összes ingatlan</p>
                                        </div>
                                        <i class="fas fa-home fa-2x opacity-50"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small><i class="fas fa-database me-1"></i> Adatbázis</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Gyors admin műveletek -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Gyors műveletek</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <?php if($user[ACCOUNTS_ADMINLEVEL] >= 3): ?>
                                        <button class="btn btn-outline-primary btn-lg text-start" data-bs-toggle="modal" data-bs-target="#playerManagementModal">
                                            <i class="fas fa-user-cog me-2"></i>Játékos kezelés
                                        </button>
                                        <button class="btn btn-outline-success btn-lg text-start" data-bs-toggle="modal" data-bs-target="#banManagementModal">
                                            <i class="fas fa-ban me-2"></i>Kitiltások kezelése
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if($user[ACCOUNTS_ADMINLEVEL] >= 2): ?>
                                        <button class="btn btn-outline-info btn-lg text-start" data-bs-toggle="modal" data-bs-target="#messageModal">
                                            <i class="fas fa-comments me-2"></i>Üzenet küldés
                                        </button>
                                        <button class="btn btn-outline-warning btn-lg text-start" data-bs-toggle="modal" data-bs-target="#moneyModal">
                                            <i class="fas fa-money-bill-wave me-2"></i>Pénz kezelés
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-outline-secondary btn-lg text-start" data-bs-toggle="modal" data-bs-target="#searchModal">
                                            <i class="fas fa-search me-2"></i>Játékos keresés
                                        </button>
                                        <a href="?refresh_stats=1" class="btn btn-outline-dark btn-lg text-start">
                                            <i class="fas fa-chart-line me-2"></i>Statisztikák
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Szerver információk -->
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-server me-2"></i>Szerver információk</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>SliteMTA Verzió:</span>
                                            <strong>V4</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>PHP Verzió:</span>
                                            <strong><?= phpversion() ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>MySQL Verzió:</span>
                                            <strong>
                                                <?php 
                                                $version = $pdo->query('SELECT VERSION() as version')->fetch();
                                                echo $version['version'];
                                                ?>
                                            </strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Utolsó frissítés:</span>
                                            <strong><?= date('Y.m.d H:i') ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Legutóbb belépett játékosok -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Legutóbb belépett játékosok</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(count($recent_players) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Játékos</th>
                                                        <th>Admin</th>
                                                        <th>Státusz</th>
                                                        <th>Utolsó belépés</th>
                                                        <th>Művelet</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($recent_players as $player): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="player-avatar me-2">
                                                                    <?= strtoupper(substr($player[ACCOUNTS_USERNAME], 0, 2)) ?>
                                                                </div>
                                                                <strong><?= safe_output($player[ACCOUNTS_USERNAME]) ?></strong>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $player[ACCOUNTS_ADMINLEVEL] > 0 ? 'warning' : 'secondary' ?>">
                                                                <?= $player[ACCOUNTS_ADMINLEVEL] ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $player[ACCOUNTS_ONLINE] == 1 ? 'success' : 'secondary' ?>">
                                                                <?= $player[ACCOUNTS_ONLINE] == 1 ? 'Online' : 'Offline' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?= date('m.d H:i', $player[ACCOUNTS_LASTLOGIN]) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <a href="?view_player=<?= $player[ACCOUNTS_ID] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if($user[ACCOUNTS_ADMINLEVEL] >= 3): ?>
                                                            <button class="btn btn-sm btn-outline-warning me-1" onclick="setPlayerForBan('<?= $player[ACCOUNTS_ID] ?>', '<?= safe_output($player[ACCOUNTS_USERNAME]) ?>')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">Nincsenek adatok</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Admin útmutató -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Admin útmutató</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Admin szintek:</h6>
                                        <ul class="mb-0">
                                            <li><strong>Admin 1</strong> Alap moderációs jogok</li>
                                            <li><strong>Admin 2</strong> Alap moderációs jogok</li>
                                            <li><strong>Admin 3:</strong> Alap moderációs jogok</li>
                                            <li><strong>Admin 4:</strong> Alap moderációs jogok</li>
                                            <li><strong>Admin 5:</strong>Alap moderációs jogok</li>
                                            <li><strong>FőAdmin:</strong> Teljes hozzáférés</li>
                                            <li><strong>SuperAdmin:</strong> Teljes hozzáférés</li>
                                            <li><strong>Manager:</strong> Teljes hozzáférés</li>
                                            <li><strong>SysEngineer:</strong> Teljes hozzáférés</li>
                                            <li><strong>Fejlesztő:</strong> Teljes hozzáférés</li>
                                            <li><strong>Tulajdonos:</strong> Teljes hozzáférés</li>
                                        </ul>
                                    </div>
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-info-circle me-2"></i>Kitiltási jogosultságok:</h6>
                                        <ul class="mb-0">
                                            <li><strong>Admin 1-2:</strong> Nem rendelkezik kitiltási jogosultsággal</li>
                                            <li><strong>Admin 3-5:</strong> Alacsonyabb szintű játékosok kitiltása</li>
                                            <li><strong>Admin 6+:</strong> Teljes kitiltási jogosultság</li>
                                        </ul>
                                    </div>
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Fontos:</h6>
                                        <p class="mb-0">Az admin jogosultságokat felelősségteljesen használd! Minden műveleted naplózásra kerül.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aktív kitiltások -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-ban me-2"></i>
                                        Aktív Kitiltások (<?= count($active_bans) ?>)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if(count($active_bans) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Játékos</th>
                                                        <th>Admin</th>
                                                        <th>Ok</th>
                                                        <th>Kitiltva</th>
                                                        <th>Lejárat</th>
                                                        <th>Műveletek</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($active_bans as $ban): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= safe_output($ban[BANS_PLAYER_NAME]) ?></strong>
                                                            <?php if($ban['player_username']): ?>
                                                                <br><small class="text-muted"><?= safe_output($ban['player_username']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= safe_output($ban[BANS_ADMIN_NAME]) ?></td>
                                                        <td><?= safe_output($ban[BANS_REASON]) ?></td>
                                                        <td><?= date('Y.m.d H:i', $ban[BANS_TIMESTAMP]) ?></td>
                                                        <td>
                                                            <?php if($ban[BANS_EXPIRE_TIMESTAMP] > 0): ?>
                                                                <?= date('Y.m.d H:i', $ban[BANS_EXPIRE_TIMESTAMP]) ?>
                                                                <br><small class="text-muted">(<?= ceil(($ban[BANS_EXPIRE_TIMESTAMP] - time()) / (24 * 60 * 60)) ?> nap van hátra)</small>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Örök</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($user[ACCOUNTS_ADMINLEVEL] >= 3): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="ban_player_id" value="<?= $ban[BANS_ACCOUNT_ID] ?>">
                                                                <input type="hidden" name="ban_action" value="unban">
                                                                <button type="submit" name="manage_ban" class="btn btn-sm btn-success" 
                                                                        onclick="return confirm('Biztosan fel szeretnéd oldani a kitiltást?')">
                                                                    <i class="fas fa-unlock"></i>
                                                                </button>
                                                            </form>
                                                            <?php endif; ?>
                                                            <a href="?view_player=<?= $ban[BANS_ACCOUNT_ID] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">Nincsenek aktív kitiltások</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Játékos részletes nézet -->
                    <?php if(!empty($player_stats)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        Játékos adatai: <?= safe_output($player_stats[ACCOUNTS_USERNAME]) ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Alap információk</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Felhasználónév:</span>
                                                    <strong><?= safe_output($player_stats[ACCOUNTS_USERNAME]) ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>E-mail:</span>
                                                    <strong><?= safe_output($player_stats[ACCOUNTS_EMAIL] ?? 'Nincs megadva') ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Admin szint:</span>
                                                    <strong><?= $player_stats[ACCOUNTS_ADMINLEVEL] ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Prémium pontok:</span>
                                                    <strong><?= number_format($player_stats[ACCOUNTS_PREMIUM_POINTS] ?? 0) ?></strong>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Statisztikák</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Karakterek:</span>
                                                    <strong><?= count($player_stats['characters'] ?? []) ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Járművek:</span>
                                                    <strong><?= $player_stats['vehicle_count'] ?? 0 ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Ingatlanok:</span>
                                                    <strong><?= $player_stats['interior_count'] ?? 0 ?></strong>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Státusz:</span>
                                                    <strong><?= ($player_stats[ACCOUNTS_ONLINE] ?? 0) == 1 ? 'Online' : 'Offline' ?></strong>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <?php if(!empty($player_stats['characters'])): ?>
                                    <div class="mt-4">
                                        <h6>Karakterek</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Név</th>
                                                        <th>Pénz</th>
                                                        <th>Bank</th>
                                                        <th>Játszott idő</th>
                                                        <th>Státusz</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($player_stats['characters'] as $char): ?>
                                                    <tr>
                                                        <td><?= safe_output($char[CHARACTERS_NAME]) ?></td>
                                                        <td>$<?= number_format($char[CHARACTERS_MONEY] ?? 0) ?></td>
                                                        <td>$<?= number_format($char[CHARACTERS_BANK_MONEY] ?? 0) ?></td>
                                                        <td><?= round(($char[CHARACTERS_PLAYED_MINUTES] ?? 0) / 60, 1) ?> óra</td>
                                                        <td>
                                                            <?php if(($char[CHARACTERS_ONLINE] ?? 0) == 1): ?>
                                                                <span class="badge bg-success">Online</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Offline</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Keresési eredmények -->
                    <?php if(!empty($search_results)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-search me-2"></i>
                                        Keresési eredmények (<?= count($search_results) ?> találat)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Felhasználónév</th>
                                                    <th>E-mail</th>
                                                    <th>Admin szint</th>
                                                    <th>Státusz</th>
                                                    <th>Utolsó belépés</th>
                                                    <th>Műveletek</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($search_results as $result): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="player-avatar me-2">
                                                                <?= strtoupper(substr($result[ACCOUNTS_USERNAME], 0, 2)) ?>
                                                            </div>
                                                            <strong><?= safe_output($result[ACCOUNTS_USERNAME]) ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><?= safe_output($result[ACCOUNTS_EMAIL] ?? 'Nincs') ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $result[ACCOUNTS_ADMINLEVEL] > 0 ? 'warning' : 'secondary' ?>">
                                                            <?= $result[ACCOUNTS_ADMINLEVEL] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $result[ACCOUNTS_ONLINE] == 1 ? 'success' : 'secondary' ?>">
                                                            <?= $result[ACCOUNTS_ONLINE] == 1 ? 'Online' : 'Offline' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('Y.m.d H:i', $result[ACCOUNTS_LASTLOGIN]) ?></td>
                                                    <td>
                                                        <a href="?view_player=<?= $result[ACCOUNTS_ID] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if($user[ACCOUNTS_ADMINLEVEL] >= 3): ?>
                                                        <button class="btn btn-sm btn-outline-warning me-1" onclick="setPlayerForBan('<?= $result[ACCOUNTS_ID] ?>', '<?= safe_output($result[ACCOUNTS_USERNAME]) ?>')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Játékos Keresés -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-search me-2"></i>Játékos Keresés</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Keresési kifejezés</label>
                            <input type="text" name="search_term" class="form-control" placeholder="Felhasználónév vagy e-mail cím..." required>
                            <div class="form-text">Add meg a játékos felhasználónevét vagy e-mail címét</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                        <button type="submit" name="search_player" class="btn btn-primary">Keresés</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Pénz Kezelés -->
    <div class="modal fade" id="moneyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Pénz Kezelés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Játékos kiválasztása</label>
                            <select name="player_id" class="form-control" required>
                                <option value="">-- Válassz játékost --</option>
                                <?php foreach($all_players as $p): ?>
                                <option value="<?= $p[ACCOUNTS_ID] ?>"><?= safe_output($p[ACCOUNTS_USERNAME]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Karakter</label>
                            <select name="character_id" class="form-control" required>
                                <option value="">-- Először válassz játékost --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Művelet</label>
                            <select name="action" class="form-control" required>
                                <option value="add">Pénz hozzáadása</option>
                                <option value="remove">Pénz elvétele</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Összeg ($)</label>
                            <input type="number" name="amount" class="form-control" min="1" max="10000000" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                        <button type="submit" name="manage_money" class="btn btn-warning">Végrehajtás</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Üzenet Küldés -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-comments me-2"></i>Üzenet Küldés</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Címzett</label>
                            <select name="message_player_id" class="form-control" required>
                                <option value="">-- Válassz játékost --</option>
                                <?php foreach($all_players as $p): ?>
                                <option value="<?= $p[ACCOUNTS_ID] ?>"><?= safe_output($p[ACCOUNTS_USERNAME]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Üzenet típusa</label>
                            <select name="message_type" class="form-control">
                                <option value="info">Információ</option>
                                <option value="warning">Figyelmeztetés</option>
                                <option value="important">Fontos</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Üzenet</label>
                            <textarea name="message_text" class="form-control" rows="4" placeholder="Írd ide az üzenetet..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                        <button type="submit" name="send_message" class="btn btn-info">Üzenet küldése</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Kitiltások Kezelése -->
    <div class="modal fade" id="banManagementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Kitiltások Kezelése</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Játékos</label>
                            <select name="ban_player_id" class="form-control" required>
                                <option value="">-- Válassz játékost --</option>
                                <?php foreach($all_players as $p): ?>
                                <option value="<?= $p[ACCOUNTS_ID] ?>"><?= safe_output($p[ACCOUNTS_USERNAME]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Művelet</label>
                            <select name="ban_action" class="form-control" required>
                                <option value="ban">Kitiltás</option>
                                <option value="unban">Kitiltás feloldása</option>
                            </select>
                        </div>
                        <div class="ban-reason-field">
                            <div class="mb-3">
                                <label class="form-label">Kitiltási ok</label>
                                <textarea name="ban_reason" class="form-control" rows="3" placeholder="Add meg a kitiltás okát..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kitiltás időtartama (nap)</label>
                                <input type="number" name="ban_duration" class="form-control" min="1" max="365" value="7">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
                        <button type="submit" name="manage_ban" class="btn btn-success">Végrehajtás</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Játékos Kezelés -->
    <div class="modal fade" id="playerManagementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-cog me-2"></i>Játékos Kezelés</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        A játékos kezelés funkciók itt lesznek elérhetőek. Jelenleg fejlesztés alatt.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-warning text-start">
                                    <i class="fas fa-user-slash me-2"></i>Játékos felfüggesztése
                                </button>
                                <button class="btn btn-outline-info text-start">
                                    <i class="fas fa-user-shield me-2"></i>Admin jogok kezelése
                                </button>
                                <button class="btn btn-outline-success text-start">
                                    <i class="fas fa-user-check me-2"></i>Játékos aktiválása
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-secondary text-start">
                                    <i class="fas fa-history me-2"></i>Előzmények
                                </button>
                                <button class="btn btn-outline-dark text-start">
                                    <i class="fas fa-chart-bar me-2"></i>Statisztikák
                                </button>
                                <button class="btn btn-outline-primary text-start">
                                    <i class="fas fa-cogs me-2"></i>Speciális beállítások
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bezárás</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dinamikus karakter betöltés
        document.querySelector('select[name="player_id"]').addEventListener('change', function() {
            const playerId = this.value;
            const characterSelect = document.querySelector('select[name="character_id"]');
            
            if(playerId) {
                // AJAX kérés a karakterek betöltéséhez
                fetch(`admin_ajax.php?action=get_characters&player_id=${playerId}`)
                    .then(response => response.json())
                    .then(data => {
                        characterSelect.innerHTML = '<option value="">-- Válassz karaktert --</option>';
                        data.characters.forEach(char => {
                            characterSelect.innerHTML += `<option value="${char.id}">${char.name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Hiba:', error);
                        characterSelect.innerHTML = '<option value="">Hiba a karakterek betöltésekor</option>';
                    });
            } else {
                characterSelect.innerHTML = '<option value="">-- Először válassz játékost --</option>';
            }
        });

        // Kitiltási modal dinamikus viselkedés
        document.querySelector('select[name="ban_action"]').addEventListener('change', function() {
            const reasonField = document.querySelector('.ban-reason-field');
            if(this.value === 'unban') {
                reasonField.style.display = 'none';
            } else {
                reasonField.style.display = 'block';
            }
        });

        // Játékos beállítása kitiltáshoz
        function setPlayerForBan(playerId, playerName) {
            const banModal = document.getElementById('banManagementModal');
            const playerSelect = banModal.querySelector('select[name="ban_player_id"]');
            playerSelect.value = playerId;
            
            const modal = new bootstrap.Modal(banModal);
            modal.show();
        }

        // Játékos beállítása pénzkezeléshez
        function setPlayerForAction(playerId, playerName) {
            const moneyModal = document.getElementById('moneyModal');
            const playerSelect = moneyModal.querySelector('select[name="player_id"]');
            playerSelect.value = playerId;
            
            const modal = new bootstrap.Modal(moneyModal);
            modal.show();
        }
    </script>
</body>
</html>