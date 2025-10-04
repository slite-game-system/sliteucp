<?php
require_once 'config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // SliteMTA accounts tábla szerint - MÁR A HELYES OSZLOPNEVEKKEL
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE ".ACCOUNTS_USERNAME." = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user) {
            // Jelszó ellenőrzés
            if(password_verify($password, $user[ACCOUNTS_PASSWORD])) {
                // Felfüggesztés ellenőrzés
                if($user[ACCOUNTS_SUSPENDED] == 1) {
                    $error = "A fiókod felfüggesztve van!";
                } else {
                    $_SESSION['user_id'] = $user[ACCOUNTS_ID];
                    $_SESSION['username'] = $user[ACCOUNTS_USERNAME];
                    $_SESSION['admin_level'] = $user[ACCOUNTS_ADMINLEVEL];
                    $_SESSION['email'] = $user[ACCOUNTS_EMAIL] ?? '';
                    $_SESSION['premium_points'] = $user[ACCOUNTS_PREMIUM_POINTS] ?? 0;
                    
                    // Frissítjük az utolsó belépést (timestamp-ként)
                    $update_stmt = $pdo->prepare("UPDATE accounts SET ".ACCOUNTS_LASTLOGIN." = ? WHERE ".ACCOUNTS_ID." = ?");
                    $update_stmt->execute([time(), $user[ACCOUNTS_ID]]);
                    
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Hibás felhasználónév vagy jelszó!";
            }
        } else {
            $error = "Hibás felhasználónév vagy jelszó!";
        }
    } catch(PDOException $e) {
        $error = "Adatbázis hiba: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Bejelentkezés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none;
        }
        .brand-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-header brand-header text-white text-center py-4">
                        <h4><i class="fas fa-gamepad me-2"></i> SliteMTA V4 UCP</h4>
                        <p class="mb-0">Felhasználói Irányítópult - SliteMTA V4</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= safe_output($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Felhasználónév</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                    <input type="text" name="username" class="form-control" required placeholder="Add meg a felhasználóneved">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jelszó</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                                    <input type="password" name="password" class="form-control" required placeholder="Add meg a jelszavad">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i> Bejelentkezés
                            </button>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>SliteMTA V4 kapcsolat aktív</strong><br>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>