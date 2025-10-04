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

// Karakterek lekérése
$characters_stmt = $pdo->prepare("SELECT * FROM characters WHERE ".CHARACTERS_ACCOUNT." = ? ORDER BY ".CHARACTERS_PLAYED_MINUTES." DESC");
$characters_stmt->execute([$user_id]);
$characters = $characters_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Karakterek</title>
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
        .character-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
        }
        .character-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .character-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
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
                            <i class="fas fa-users me-2 text-primary"></i>Karaktereim
                        </h2>
                        <span class="badge bg-primary fs-6">Összesen: <?= count($characters) ?> karakter</span>
                    </div>

                    <?php if(count($characters) > 0): ?>
                        <div class="row">
                            <?php foreach($characters as $char): ?>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card character-card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex align-items-center">
                                            <div class="character-avatar me-3">
                                                <?= strtoupper(substr($char[CHARACTERS_NAME], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h5 class="mb-0"><?= safe_output($char[CHARACTERS_NAME]) ?></h5>
                                                <small>Karakter ID: <?= $char[CHARACTERS_ID] ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Játszott idő</small>
                                                <div class="fw-bold text-primary">
                                                    <?= round(($char[CHARACTERS_PLAYED_MINUTES] ?? 0) / 60, 1) ?> óra
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Státusz</small>
                                                <div>
                                                    <?php if(($char[CHARACTERS_ONLINE] ?? 0) == 1): ?>
                                                        <span class="badge bg-success">Online</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Offline</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Készpénz</small>
                                                <div class="fw-bold text-success">
                                                    $<?= number_format($char[CHARACTERS_MONEY] ?? 0) ?>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Bank</small>
                                                <div class="fw-bold text-info">
                                                    $<?= number_format($char[CHARACTERS_BANK_MONEY] ?? 0) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Életerő</small>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?= ($char[CHARACTERS_HEALTH] ?? 0) > 50 ? 'success' : 'danger' ?>" 
                                                         style="width: <?= $char[CHARACTERS_HEALTH] ?? 0 ?>%"></div>
                                                </div>
                                                <small><?= $char[CHARACTERS_HEALTH] ?? 0 ?>%</small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Páncél</small>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-info" 
                                                         style="width: <?= $char[CHARACTERS_ARMOR] ?? 0 ?>%"></div>
                                                </div>
                                                <small><?= $char[CHARACTERS_ARMOR] ?? 0 ?>%</small>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <small class="text-muted d-block">Skin ID: <?= $char[CHARACTERS_SKIN] ?? 0 ?></small>
                                            <small class="text-muted">Karakter ID: <?= $char[CHARACTERS_ID] ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i> Részletek
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Összesítő táblázat -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Összesítő táblázat</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Név</th>
                                                <th>Játszott idő</th>
                                                <th>Készpénz</th>
                                                <th>Bank</th>
                                                <th>Összes pénz</th>
                                                <th>Státusz</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_playtime = 0;
                                            $total_cash = 0;
                                            $total_bank = 0;
                                            ?>
                                            <?php foreach($characters as $char): ?>
                                            <?php
                                                $total_playtime += $char[CHARACTERS_PLAYED_MINUTES] ?? 0;
                                                $total_cash += $char[CHARACTERS_MONEY] ?? 0;
                                                $total_bank += $char[CHARACTERS_BANK_MONEY] ?? 0;
                                            ?>
                                            <tr>
                                                <td><strong><?= safe_output($char[CHARACTERS_NAME]) ?></strong></td>
                                                <td><?= round(($char[CHARACTERS_PLAYED_MINUTES] ?? 0) / 60, 1) ?> óra</td>
                                                <td>$<?= number_format($char[CHARACTERS_MONEY] ?? 0) ?></td>
                                                <td>$<?= number_format($char[CHARACTERS_BANK_MONEY] ?? 0) ?></td>
                                                <td><strong>$<?= number_format(($char[CHARACTERS_MONEY] ?? 0) + ($char[CHARACTERS_BANK_MONEY] ?? 0)) ?></strong></td>
                                                <td>
                                                    <?php if(($char[CHARACTERS_ONLINE] ?? 0) == 1): ?>
                                                        <span class="badge bg-success">Online</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Offline</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-primary fw-bold">
                                                <td>Összesen:</td>
                                                <td><?= round($total_playtime / 60, 1) ?> óra</td>
                                                <td>$<?= number_format($total_cash) ?></td>
                                                <td>$<?= number_format($total_bank) ?></td>
                                                <td>$<?= number_format($total_cash + $total_bank) ?></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">Nincsenek karaktereid</h3>
                            <p class="text-muted">Jelentkezz be a szerverre és hozz létre egy karaktert!</p>
                            <a href="#" class="btn btn-primary btn-lg">
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