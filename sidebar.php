<?php
// Karakterek számának lekérése
$characters_count = 0;
try {
    $characters_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
    $characters_stmt->execute([$_SESSION['user_id']]);
    $characters_count = $characters_stmt->fetch()['count'];
} catch(Exception $e) {
    $characters_count = 0;
}

// Járművek számának lekérése
$vehicles_count = 0;
try {
    $character_ids = [];
    $chars_stmt = $pdo->prepare("SELECT ".CHARACTERS_ID." FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
    $chars_stmt->execute([$_SESSION['user_id']]);
    $chars = $chars_stmt->fetchAll();
    
    foreach($chars as $char) {
        $character_ids[] = $char[CHARACTERS_ID];
    }
    
    if(!empty($character_ids)) {
        $placeholders = str_repeat('?,', count($character_ids) - 1) . '?';
        $vehicles_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vehicles WHERE ".VEHICLES_OWNER." IN ($placeholders)");
        $vehicles_stmt->execute($character_ids);
        $vehicles_count = $vehicles_stmt->fetch()['count'];
    }
} catch(Exception $e) {
    $vehicles_count = 0;
}

// Ingatlanok számának lekérése
$interiors_count = 0;
try {
    $interiors_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interiors WHERE ".INTERIORS_OWNER." = ?");
    $interiors_stmt->execute([$_SESSION['user_id']]);
    $interiors_count = $interiors_stmt->fetch()['count'];
} catch(Exception $e) {
    $interiors_count = 0;
}
?>
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
                <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
            </div>
            <div class="text-start">
                <div class="fw-bold"><?= safe_output($_SESSION['username']) ?></div>
                <small class="text-warning"><?= getAdminTitle($_SESSION['admin_level']) ?></small>
            </div>
        </div>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="profile.php">
            <i class="fas fa-user me-2"></i>Profilom
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'characters.php' ? 'active' : '' ?>" href="characters.php">
            <i class="fas fa-users me-2"></i>Karakterek
            <span class="badge bg-primary ms-2"><?= $characters_count ?></span>
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : '' ?>" href="vehicles.php">
            <i class="fas fa-car me-2"></i>Járművek
            <span class="badge bg-success ms-2"><?= $vehicles_count ?></span>
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : '' ?>" href="properties.php">
            <i class="fas fa-home me-2"></i>Ingatlanok
            <span class="badge bg-info ms-2"><?= $interiors_count ?></span>
        </a>
        <?php if($_SESSION['admin_level'] > 0): ?>
        <a class="nav-link text-warning <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>" href="admin.php">
            <i class="fas fa-shield-alt me-2"></i>Admin Panel
            <span class="badge bg-warning ms-2"><?= $_SESSION['admin_level'] ?></span>
        </a>
        <?php endif; ?>
        <div class="mt-4 pt-3 border-top border-secondary">
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Kijelentkezés
            </a>
        </div>
    </nav>
</div>