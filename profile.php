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

$message = '';
$error = '';

// Profil frissítése
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    
    try {
        $update_stmt = $pdo->prepare("UPDATE accounts SET ".ACCOUNTS_EMAIL." = ? WHERE ".ACCOUNTS_ID." = ?");
        $update_stmt->execute([$email, $user_id]);
        $message = "Profil sikeresen frissítve!";
        $user[ACCOUNTS_EMAIL] = $email;
    } catch(Exception $e) {
        $error = "Hiba történt a frissítés során: " . $e->getMessage();
    }
}

// Jelszó változtatás
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(password_verify($current_password, $user[ACCOUNTS_PASSWORD])) {
        if($new_password === $confirm_password) {
            if(strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE accounts SET ".ACCOUNTS_PASSWORD." = ? WHERE ".ACCOUNTS_ID." = ?");
                $update_stmt->execute([$hashed_password, $user_id]);
                $message = "Jelszó sikeresen megváltoztatva!";
            } else {
                $error = "Az új jelszónak legalább 6 karakter hosszúnak kell lennie!";
            }
        } else {
            $error = "Az új jelszavak nem egyeznek!";
        }
    } else {
        $error = "A jelenlegi jelszó nem megfelelő!";
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Profil</title>
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
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
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
                            <i class="fas fa-user me-2 text-primary"></i>Profilom
                        </h2>
                    </div>

                    <?php if($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profil információk -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Profil információk</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="user-avatar mx-auto mb-3">
                                            <?= strtoupper(substr($user[ACCOUNTS_USERNAME], 0, 2)) ?>
                                        </div>
                                        <h4><?= safe_output($user[ACCOUNTS_USERNAME]) ?></h4>
                                        <span class="badge bg-warning"><?= getAdminTitle($user[ACCOUNTS_ADMINLEVEL]) ?></span>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="update_profile" value="1">
                                        <div class="mb-3">
                                            <label class="form-label">Felhasználónév</label>
                                            <input type="text" class="form-control" value="<?= safe_output($user[ACCOUNTS_USERNAME]) ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">E-mail cím</label>
                                            <input type="email" name="email" class="form-control" value="<?= safe_output($user[ACCOUNTS_EMAIL] ?? '') ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Admin szint</label>
                                            <input type="text" class="form-control" value="<?= $user[ACCOUNTS_ADMINLEVEL] ?> - <?= getAdminTitle($user[ACCOUNTS_ADMINLEVEL]) ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Prémium pontok</label>
                                            <input type="text" class="form-control" value="<?= number_format($user[ACCOUNTS_PREMIUM_POINTS] ?? 0) ?>" readonly>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Profil frissítése
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Jelszó változtatás -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Jelszó változtatás</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="change_password" value="1">
                                        <div class="mb-3">
                                            <label class="form-label">Jelenlegi jelszó</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Új jelszó</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Új jelszó megerősítése</label>
                                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                        </div>
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="fas fa-key me-2"></i>Jelszó megváltoztatása
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Statisztikák -->
                            <div class="card mt-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statisztikák</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Regisztráció dátuma:</span>
                                            <strong><?= date('Y.m.d', $user[ACCOUNTS_LASTLOGIN] ?? time()) ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Utolsó belépés:</span>
                                            <strong><?= date('Y.m.d H:i', $user[ACCOUNTS_LASTLOGIN] ?? time()) ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Felfüggesztve:</span>
                                            <strong><?= ($user[ACCOUNTS_SUSPENDED] ?? 0) == 1 ? 'Igen' : 'Nem' ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Online státusz:</span>
                                            <strong><?= ($user[ACCOUNTS_ONLINE] ?? 'N') == 'Y' ? 'Online' : 'Offline' ?></strong>
                                        </li>
                                    </ul>
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