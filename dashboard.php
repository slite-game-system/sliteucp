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

if(!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Karakterek lekérése - EZ MŰKÖDIK
$characters_stmt = $pdo->prepare("SELECT * FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
$characters_stmt->execute([$user_id]);
$characters = $characters_stmt->fetchAll();

// Járművek lekérése - MÁR A HELYES OSZLOPNEVVEL
$vehicles = [];
$vehicles_count = 0;
try {
    // Először összegyűjtjük az összes karakter ID-t
    $character_ids = [];
    foreach($characters as $char) {
        $character_ids[] = $char[CHARACTERS_ID];
    }
    
    if(!empty($character_ids)) {
        $placeholders = str_repeat('?,', count($character_ids) - 1) . '?';
        $vehicles_stmt = $pdo->prepare("SELECT * FROM vehicles WHERE ".VEHICLES_OWNER." IN ($placeholders)");
        $vehicles_stmt->execute($character_ids);
        $vehicles = $vehicles_stmt->fetchAll();
        $vehicles_count = count($vehicles);
    }
} catch(Exception $e) {
    error_log("Vehicles error: " . $e->getMessage());
    $vehicles_count = 0;
}

// Ingatlanok lekérése - MÁR A HELYES OSZLOPNEVVEL (owner = accountId)
$interiors = [];
$interiors_count = 0;
try {
    $interiors_stmt = $pdo->prepare("SELECT * FROM interiors WHERE ".INTERIORS_OWNER." = ?");
    $interiors_stmt->execute([$user_id]);
    $interiors = $interiors_stmt->fetchAll();
    $interiors_count = count($interiors);
} catch(Exception $e) {
    error_log("Interiors error: " . $e->getMessage());
    $interiors_count = 0;
}

// Statisztikák
$total_playtime = 0;
$total_money = 0;
$total_bank_money = 0;

foreach($characters as $char) {
    $total_playtime += $char[CHARACTERS_PLAYED_MINUTES] ?? 0;
    $total_money += $char[CHARACTERS_MONEY] ?? 0;
    $total_bank_money += $char[CHARACTERS_BANK_MONEY] ?? 0;
}

// Online státusz
$online_status = ($user[ACCOUNTS_ONLINE] == 'Y') ? 'Online' : 'Offline';
$online_badge = ($user[ACCOUNTS_ONLINE] == 'Y') ? 'bg-success' : 'bg-secondary';

// Utolsó belépés formázása
$last_login = ($user[ACCOUNTS_LASTLOGIN] > 0) ? date('Y.m.d H:i', $user[ACCOUNTS_LASTLOGIN]) : 'Még soha';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title> UCP - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c3e50, #34495e);
            min-height: 100vh;
            color: white;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
            transform: translateX(5px);
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .brand-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .vehicle-model {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .property-type {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar">
                <div class="text-center p-4 border-bottom border-secondary">
                    <div class="brand-gradient text-white p-3 rounded mb-3">
                        <i class="fas fa-gamepad fa-2x mb-2"></i>
                        <h5 class="mb-0">SliteMTA UCP</h5>
                        <small class="opacity-75">User Control Panel</small>
                    </div>
                    
                    <!-- User info -->
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="user-avatar me-2">
                            <?= strtoupper(substr($user[ACCOUNTS_USERNAME], 0, 2)) ?>
                        </div>
                        <div class="text-start">
                            <div class="fw-bold"><?= safe_output($user[ACCOUNTS_USERNAME]) ?></div>
                            <small class="text-warning"><?= getAdminTitle($user[ACCOUNTS_ADMINLEVEL]) ?></small>
                        </div>
                    </div>
                </div>
                
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i>Profilom
                    </a>
                    <a class="nav-link" href="characters.php">
                        <i class="fas fa-users me-2"></i>Karakterek
                        <span class="badge bg-primary ms-2"><?= count($characters) ?></span>
                    </a>
                    <a class="nav-link" href="vehicles.php">
                        <i class="fas fa-car me-2"></i>Járművek
                        <span class="badge bg-success ms-2"><?= $vehicles_count ?></span>
                    </a>
                    <a class="nav-link" href="properties.php">
                        <i class="fas fa-home me-2"></i>Ingatlanok
                        <span class="badge bg-info ms-2"><?= $interiors_count ?></span>
                    </a>
                    <?php if($user[ACCOUNTS_ADMINLEVEL] > 0): ?>
                    <a class="nav-link text-warning" href="admin.php">
                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
                        <span class="badge bg-warning ms-2"><?= $user[ACCOUNTS_ADMINLEVEL] ?></span>
                    </a>
                    <?php endif; ?>
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Kijelentkezés
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Main content -->
            <div class="col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold">
                                <i class="fas fa-tachometer-alt me-2 text-primary"></i>Irányítópult
                            </h2>
                            <p class="text-muted mb-0">Üdvözöljük a SliteMTA felhasználói irányítópultján!</p>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= $online_badge ?> me-2"><?= $online_status ?></span>
                            <div class="badge bg-light text-dark p-2">
                                <i class="fas fa-clock me-1"></i>
                                <?= date('Y.m.d H:i') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Statisztika kártyák -->
                    <div class="row g-4 mb-5">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="fw-bold"><?= count($characters) ?></h2>
                                            <p class="mb-0">Karakter</p>
                                        </div>
                                        <div class="display-4 opacity-50">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="fas fa-clock me-1"></i> Összes játékidő: <?= round($total_playtime / 60, 1) ?> óra</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="fw-bold"><?= $vehicles_count ?></h2>
                                            <p class="mb-0">Jármű</p>
                                        </div>
                                        <div class="display-4 opacity-50">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="fas fa-tachometer-alt me-1"></i> Garázsban</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-white" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="fw-bold"><?= $interiors_count ?></h2>
                                            <p class="mb-0">Ingatlan</p>
                                        </div>
                                        <div class="display-4 opacity-50">
                                            <i class="fas fa-home"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="fas fa-map-marker-alt me-1"></i> Tulajdonban</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card text-dark" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="fw-bold"><?= number_format($total_money + $total_bank_money) ?></h2>
                                            <p class="mb-0">Összes pénz</p>
                                        </div>
                                        <div class="display-4 opacity-50">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="fas fa-gem me-1"></i> <?= $user[ACCOUNTS_PREMIUM_POINTS] ?> prémium pont</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Karakterek listája -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-users me-2 text-primary"></i>Karaktereid
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if(count($characters) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Név</th>
                                                        <th>Játszott idő</th>
                                                        <th>Készpénz</th>
                                                        <th>Bank</th>
                                                        <th>Életerő</th>
                                                        <th>Státusz</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($characters as $char): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= safe_output($char[CHARACTERS_NAME]) ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info">
                                                                <?= round(($char[CHARACTERS_PLAYED_MINUTES] ?? 0) / 60, 1) ?> óra
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                $<?= number_format($char[CHARACTERS_MONEY] ?? 0) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary">
                                                                $<?= number_format($char[CHARACTERS_BANK_MONEY] ?? 0) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= ($char[CHARACTERS_HEALTH] ?? 0) > 50 ? 'success' : 'danger' ?>">
                                                                <?= $char[CHARACTERS_HEALTH] ?? 0 ?>%
                                                            </span>
                                                        </td>
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
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Nincs létrehozott karaktered</h5>
                                            <p class="text-muted">Jelentkezz be a szerverre és hozz létre egy karaktert!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Járművek listája -->
                    <?php if($vehicles_count > 0): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-car me-2 text-success"></i>Járműveid
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach($vehicles as $vehicle): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-car me-2"></i>
                                                        Jármű #<?= $vehicle[VEHICLES_ID] ?>
                                                    </h6>
                                                    <div class="vehicle-model">
                                                        Model: <?= $vehicle[VEHICLES_MODEL] ?>
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="badge bg-<?= ($vehicle[VEHICLES_HEALTH] ?? 0) > 500 ? 'success' : 'danger' ?>">
                                                            Életerő: <?= $vehicle[VEHICLES_HEALTH] ?? 0 ?>
                                                        </span>
                                                        <span class="badge bg-info">
                                                            Üzemanyag: <?= $vehicle[VEHICLES_FUEL] ?? 0 ?>%
                                                        </span>
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="badge bg-<?= ($vehicle[VEHICLES_LOCKED] ?? 0) == 1 ? 'warning' : 'success' ?>">
                                                            <?= ($vehicle[VEHICLES_LOCKED] ?? 0) == 1 ? 'Zárt' : 'Nyitott' ?>
                                                        </span>
                                                        <?php if(!empty($vehicle[VEHICLES_PLATE])): ?>
                                                        <span class="badge bg-secondary">
                                                            <?= safe_output($vehicle[VEHICLES_PLATE]) ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Ingatlanok listája -->
                    <?php if($interiors_count > 0): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-home me-2 text-info"></i>Ingatlanjaid
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach($interiors as $interior): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="fas fa-home me-2"></i>
                                                        <?= safe_output($interior[INTERIORS_NAME]) ?>
                                                    </h6>
                                                    <div class="property-type">
                                                        Típus: <?= safe_output($interior[INTERIORS_TYPE]) ?>
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="badge bg-<?= ($interior[INTERIORS_LOCKED] ?? 0) == 1 ? 'warning' : 'success' ?>">
                                                            <?= ($interior[INTERIORS_LOCKED] ?? 0) == 1 ? 'Zárt' : 'Nyitott' ?>
                                                        </span>
                                                        <?php if(!empty($interior[INTERIORS_PRICE]) && $interior[INTERIORS_PRICE] > 0): ?>
                                                        <span class="badge bg-success">
                                                            $<?= number_format($interior[INTERIORS_PRICE]) ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            Ingatlan ID: <?= $interior[INTERIORS_ID] ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gyors információk -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Gyors információk</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Felhasználónév:</span>
                                            <strong><?= safe_output($user[ACCOUNTS_USERNAME]) ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>E-mail:</span>
                                            <strong><?= safe_output($user[ACCOUNTS_EMAIL] ?? 'Nincs megadva') ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Admin szint:</span>
                                            <strong><?= $user[ACCOUNTS_ADMINLEVEL] ?> - <?= getAdminTitle($user[ACCOUNTS_ADMINLEVEL]) ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Utolsó belépés:</span>
                                            <strong><?= $last_login ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statisztikák</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h4 class="text-primary mb-0"><?= $vehicles_count ?></h4>
                                                <small>Járművek</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <h4 class="text-success mb-0"><?= $interiors_count ?></h4>
                                                <small>Ingatlanok</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded">
                                                <h4 class="text-info mb-0"><?= round($total_playtime / 60, 1) ?></h4>
                                                <small>Óra játék</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-light rounded">
                                                <h4 class="text-warning mb-0"><?= $user[ACCOUNTS_PREMIUM_POINTS] ?></h4>
                                                <small>Prémium pont</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>