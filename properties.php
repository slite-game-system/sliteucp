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

// Ingatlanok lekérése
$interiors = [];
$interiors_count = 0;
try {
    $interiors_stmt = $pdo->prepare("SELECT * FROM interiors WHERE ".INTERIORS_OWNER." = ? ORDER BY ".INTERIORS_ID." DESC");
    $interiors_stmt->execute([$user_id]);
    $interiors = $interiors_stmt->fetchAll();
    $interiors_count = count($interiors);
} catch(Exception $e) {
    error_log("Interiors error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Ingatlanok</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c3e50, #34495e);
            min-height: 100vh;
            color: white;
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
        .property-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .property-icon {
            font-size: 2rem;
            color: #4facfe;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="fw-bold">
                            <i class="fas fa-home me-2 text-info"></i>Ingatlanjaim
                        </h2>
                        <span class="badge bg-info fs-6">Összesen: <?= $interiors_count ?> ingatlan</span>
                    </div>

                    <?php if($interiors_count > 0): ?>
                        <!-- Statisztika kártyák -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-home fa-2x mb-2"></i>
                                        <h4><?= $interiors_count ?></h4>
                                        <p class="mb-0">Összes ingatlan</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock-open fa-2x mb-2"></i>
                                        <h4>
                                            <?php
                                            $unlocked_count = 0;
                                            foreach($interiors as $i) {
                                                if(($i[INTERIORS_LOCKED] ?? 0) == 0) $unlocked_count++;
                                            }
                                            echo $unlocked_count;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Nyitott ingatlanok</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock fa-2x mb-2"></i>
                                        <h4>
                                            <?php
                                            $locked_count = 0;
                                            foreach($interiors as $i) {
                                                if(($i[INTERIORS_LOCKED] ?? 0) == 1) $locked_count++;
                                            }
                                            echo $locked_count;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Zárt ingatlanok</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                        <h4>
                                            <?php
                                            $total_value = 0;
                                            foreach($interiors as $i) {
                                                $total_value += $i[INTERIORS_PRICE] ?? 0;
                                            }
                                            echo number_format($total_value);
                                            ?>
                                        </h4>
                                        <p class="mb-0">Összes érték</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ingatlanok listája -->
                        <div class="row">
                            <?php foreach($interiors as $interior): ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card property-card h-100">
                                    <div class="card-header bg-info text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-home me-2"></i>
                                                <?= safe_output($interior[INTERIORS_NAME]) ?>
                                            </h5>
                                            <span class="badge bg-light text-dark">#<?= $interior[INTERIORS_ID] ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Ingatlan típusa -->
                                        <div class="mb-3">
                                            <small class="text-muted">Típus</small>
                                            <div class="fw-bold"><?= safe_output($interior[INTERIORS_TYPE]) ?></div>
                                        </div>

                                        <!-- Zóna -->
                                        <div class="mb-3">
                                            <small class="text-muted">Zóna</small>
                                            <div class="fw-bold text-primary"><?= safe_output($interior['zone'] ?? 'Ismeretlen') ?></div>
                                        </div>

                                        <!-- Ár -->
                                        <?php if(!empty($interior[INTERIORS_PRICE]) && $interior[INTERIORS_PRICE] > 0): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Vételár</small>
                                            <div class="fw-bold text-success">$<?= number_format($interior[INTERIORS_PRICE]) ?></div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Státusz -->
                                        <div class="mb-3">
                                            <small class="text-muted">Státusz</small>
                                            <div>
                                                <?php if(($interior[INTERIORS_LOCKED] ?? 0) == 1): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-lock me-1"></i> Zárt
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-lock-open me-1"></i> Nyitott
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if(($interior['editable'] ?? 'N') == 'Y'): ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-edit me-1"></i> Szerkeszthető
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Tulajdonos típus -->
                                        <div class="mb-3">
                                            <small class="text-muted">Tulajdonos típus</small>
                                            <div>
                                                <span class="badge bg-<?= ($interior['ownerType'] ?? 'player') == 'player' ? 'info' : 'secondary' ?>">
                                                    <?= ($interior['ownerType'] ?? 'player') == 'player' ? 'Játékos' : 'Csoport' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Ingatlan ID -->
                                        <div class="text-center mt-3">
                                            <small class="text-muted">Ingatlan ID: <?= $interior[INTERIORS_ID] ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-cog me-1"></i> Kezelés
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Részletes táblázat -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Összes ingatlan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Név</th>
                                                <th>Típus</th>
                                                <th>Zóna</th>
                                                <th>Ár</th>
                                                <th>Státusz</th>
                                                <th>Tulajdonos típus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($interiors as $interior): ?>
                                            <tr>
                                                <td><strong>#<?= $interior[INTERIORS_ID] ?></strong></td>
                                                <td><?= safe_output($interior[INTERIORS_NAME]) ?></td>
                                                <td><?= safe_output($interior[INTERIORS_TYPE]) ?></td>
                                                <td><?= safe_output($interior['zone'] ?? 'Ismeretlen') ?></td>
                                                <td>
                                                    <?php if(!empty($interior[INTERIORS_PRICE]) && $interior[INTERIORS_PRICE] > 0): ?>
                                                        <span class="badge bg-success">$<?= number_format($interior[INTERIORS_PRICE]) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if(($interior[INTERIORS_LOCKED] ?? 0) == 1): ?>
                                                        <span class="badge bg-warning">Zárt</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Nyitott</span>
                                                    <?php endif; ?>
                                                    <?php if(($interior['editable'] ?? 'N') == 'Y'): ?>
                                                        <span class="badge bg-primary">Szerkeszthető</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= ($interior['ownerType'] ?? 'player') == 'player' ? 'info' : 'secondary' ?>">
                                                        <?= ($interior['ownerType'] ?? 'player') == 'player' ? 'Játékos' : 'Csoport' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-home fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">Nincsenek ingatlanjaid</h3>
                            <p class="text-muted">Vásárolj ingatlanokat a szerveren!</p>
                            <a href="#" class="btn btn-info btn-lg">
                                <i class="fas fa-gamepad me-2"></i>Csatlakozás a szerverhez
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>