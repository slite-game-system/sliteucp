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
$characters_stmt = $pdo->prepare("SELECT * FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
$characters_stmt->execute([$user_id]);
$characters = $characters_stmt->fetchAll();

// Járművek lekérése
$vehicles = [];
$vehicles_count = 0;
try {
    $character_ids = [];
    foreach($characters as $char) {
        $character_ids[] = $char[CHARACTERS_ID];
    }
    
    if(!empty($character_ids)) {
        $placeholders = str_repeat('?,', count($character_ids) - 1) . '?';
        $vehicles_stmt = $pdo->prepare("SELECT v.*, c.".CHARACTERS_NAME." as character_name 
                                      FROM vehicles v 
                                      LEFT JOIN characters c ON v.".VEHICLES_OWNER." = c.".CHARACTERS_ID." 
                                      WHERE v.".VEHICLES_OWNER." IN ($placeholders) 
                                      ORDER BY v.".VEHICLES_ID." DESC");
        $vehicles_stmt->execute($character_ids);
        $vehicles = $vehicles_stmt->fetchAll();
        $vehicles_count = count($vehicles);
    }
} catch(Exception $e) {
    error_log("Vehicles error: " . $e->getMessage());
}

// Jármű modellek (MTA jármű ID-k)
$vehicle_models = [
    400 => "Landstalker", 401 => "Bravura", 402 => "Buffalo", 403 => "Linerunner", 404 => "Perenniel",
    405 => "Sentinel", 406 => "Dumper", 407 => "Firetruck", 408 => "Trashmaster", 409 => "Stretch",
    410 => "Manana", 411 => "Infernus", 412 => "Voodoo", 413 => "Pony", 414 => "Mule",
    415 => "Cheetah", 416 => "Ambulance", 417 => "Leviathan", 418 => "Moonbeam", 419 => "Esperanto",
    420 => "Taxi", 421 => "Washington", 422 => "Bobcat", 423 => "Mr Whoopee", 424 => "BF Injection",
    425 => "Hunter", 426 => "Premier", 427 => "Enforcer", 428 => "Securicar", 429 => "Banshee",
    430 => "Predator", 431 => "Bus", 432 => "Rhino", 433 => "Barracks", 434 => "Hotknife",
    435 => "Trailer", 436 => "Previon", 437 => "Coach", 438 => "Cabbie", 439 => "Stallion",
    440 => "Rumpo", 441 => "RC Bandit", 442 => "Romero", 443 => "Packer", 444 => "Monster",
    445 => "Admiral", 446 => "Squalo", 447 => "Seasparrow", 448 => "Pizzaboy", 449 => "Tram",
    450 => "Trailer", 451 => "Turismo", 452 => "Speeder", 453 => "Reefer", 454 => "Tropic",
    455 => "Flatbed", 456 => "Yankee", 457 => "Caddy", 458 => "Solair", 459 => "Berkley's RC Van",
    460 => "Skimmer", 461 => "PCJ-600", 462 => "Faggio", 463 => "Freeway", 464 => "RC Baron",
    465 => "RC Raider", 466 => "Glendale", 467 => "Oceanic", 468 => "Sanchez", 469 => "Sparrow",
    470 => "Patriot", 471 => "Quad", 472 => "Coastguard", 473 => "Dinghy", 474 => "Hermes",
    475 => "Sabre", 476 => "Rustler", 477 => "ZR-350", 478 => "Walton", 479 => "Regina",
    480 => "Comet", 481 => "BMX", 482 => "Burrito", 483 => "Camper", 484 => "Marquis",
    485 => "Baggage", 486 => "Dozer", 487 => "Maverick", 488 => "News Chopper", 489 => "Rancher",
    490 => "FBI Rancher", 491 => "Virgo", 492 => "Greenwood", 493 => "Jetmax", 494 => "Hotring",
    495 => "Sandking", 496 => "Blista Compact", 497 => "Police Maverick", 498 => "Boxville", 499 => "Benson",
    500 => "Mesa", 501 => "RC Goblin", 502 => "Hotring Racer A", 503 => "Hotring Racer B", 504 => "Bloodring Banger",
    505 => "Rancher", 506 => "Super GT", 507 => "Elegant", 508 => "Journey", 509 => "Bike",
    510 => "Mountain Bike", 511 => "Beagle", 512 => "Cropduster", 513 => "Stunt", 514 => "Tanker",
    515 => "Roadtrain", 516 => "Nebula", 517 => "Majestic", 518 => "Buccaneer", 519 => "Shamal",
    520 => "Hydra", 521 => "FCR-900", 522 => "NRG-500", 523 => "HPV1000", 524 => "Cement Truck",
    525 => "Tow Truck", 526 => "Fortune", 527 => "Cadrona", 528 => "FBI Truck", 529 => "Willard",
    530 => "Forklift", 531 => "Tractor", 532 => "Combine", 533 => "Feltzer", 534 => "Remington",
    535 => "Slamvan", 536 => "Blade", 537 => "Freight", 538 => "Streak", 539 => "Vortex",
    540 => "Vincent", 541 => "Bullet", 542 => "Clover", 543 => "Sadler", 544 => "Firetruck LA",
    545 => "Hustler", 546 => "Intruder", 547 => "Primo", 548 => "Cargobob", 549 => "Tampa",
    550 => "Sunrise", 551 => "Merit", 552 => "Utility", 553 => "Nevada", 554 => "Yosemite",
    555 => "Windsor", 556 => "Monster A", 557 => "Monster B", 558 => "Uranus", 559 => "Jester",
    560 => "Sultan", 561 => "Stratum", 562 => "Elegy", 563 => "Raindance", 564 => "RC Tiger",
    565 => "Flash", 566 => "Tahoma", 567 => "Savanna", 568 => "Bandito", 569 => "Freight Flat",
    570 => "Streak Carriage", 571 => "Kart", 572 => "Mower", 573 => "Dune", 574 => "Sweeper",
    575 => "Broadway", 576 => "Tornado", 577 => "AT-400", 578 => "DFT-30", 579 => "Huntley",
    580 => "Stafford", 581 => "BF-400", 582 => "News Van", 583 => "Tug", 584 => "Trailer 3",
    585 => "Emperor", 586 => "Wayfarer", 587 => "Euros", 588 => "Hotdog", 589 => "Club",
    590 => "Trailer 3", 591 => "Trailer 3", 592 => "Andromada", 593 => "Dodo", 594 => "RC Cam",
    595 => "Launch", 596 => "Police Car (LSPD)", 597 => "Police Car (SFPD)", 598 => "Police Car (LVPD)",
    599 => "Police Ranger", 600 => "Picador", 601 => "S.W.A.T.", 602 => "Alpha", 603 => "Phoenix",
    604 => "Glendale", 605 => "Sadler", 606 => "Luggage Trailer A", 607 => "Luggage Trailer B",
    608 => "Stair Trailer", 609 => "Boxville", 610 => "Farm Plow", 611 => "Utility Trailer"
];
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>SliteMTA UCP - Járművek</title>
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
        .vehicle-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
        }
        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .vehicle-icon {
            font-size: 2rem;
            color: #667eea;
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
                            <i class="fas fa-car me-2 text-success"></i>Járműveim
                        </h2>
                        <span class="badge bg-success fs-6">Összesen: <?= $vehicles_count ?> jármű</span>
                    </div>

                    <?php if($vehicles_count > 0): ?>
                        <!-- Statisztika kártyák -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-car fa-2x mb-2"></i>
                                        <h4><?= $vehicles_count ?></h4>
                                        <p class="mb-0">Összes jármű</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-gas-pump fa-2x mb-2"></i>
                                        <h4>
                                            <?php
                                            $total_fuel = 0;
                                            foreach($vehicles as $v) {
                                                $total_fuel += $v[VEHICLES_FUEL] ?? 0;
                                            }
                                            echo round($total_fuel / $vehicles_count, 1);
                                            ?>%
                                        </h4>
                                        <p class="mb-0">Átlagos üzemanyag</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <i class="fas fa-heart fa-2x mb-2"></i>
                                        <h4>
                                            <?php
                                            $total_health = 0;
                                            foreach($vehicles as $v) {
                                                $total_health += $v[VEHICLES_HEALTH] ?? 0;
                                            }
                                            echo round($total_health / $vehicles_count, 0);
                                            ?>
                                        </h4>
                                        <p class="mb-0">Átlagos életerő</p>
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
                                            foreach($vehicles as $v) {
                                                if(($v[VEHICLES_LOCKED] ?? 0) == 1) $locked_count++;
                                            }
                                            echo $locked_count;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Zárt járművek</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Járművek listája -->
                        <div class="row">
                            <?php foreach($vehicles as $vehicle): ?>
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card vehicle-card h-100">
                                    <div class="card-header bg-success text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-car me-2"></i>
                                                <?= $vehicle_models[$vehicle[VEHICLES_MODEL]] ?? 'Ismeretlen' ?>
                                            </h5>
                                            <span class="badge bg-light text-dark">#<?= $vehicle[VEHICLES_ID] ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Jármű információk -->
                                        <div class="mb-3">
                                            <small class="text-muted">Tulajdonos</small>
                                            <div class="fw-bold"><?= safe_output($vehicle['character_name'] ?? 'Ismeretlen') ?></div>
                                        </div>

                                        <!-- Életerő -->
                                        <div class="mb-3">
                                            <small class="text-muted">Életerő</small>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-<?= ($vehicle[VEHICLES_HEALTH] ?? 0) > 500 ? 'success' : 'danger' ?>" 
                                                     style="width: <?= min(($vehicle[VEHICLES_HEALTH] ?? 0) / 10, 100) ?>%"></div>
                                            </div>
                                            <small><?= $vehicle[VEHICLES_HEALTH] ?? 0 ?> / 1000</small>
                                        </div>

                                        <!-- Üzemanyag -->
                                        <div class="mb-3">
                                            <small class="text-muted">Üzemanyag</small>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-info" 
                                                     style="width: <?= $vehicle[VEHICLES_FUEL] ?? 0 ?>%"></div>
                                            </div>
                                            <small><?= $vehicle[VEHICLES_FUEL] ?? 0 ?>%</small>
                                        </div>

                                        <!-- Státusz badge-ek -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <span class="badge bg-<?= ($vehicle[VEHICLES_LOCKED] ?? 0) == 1 ? 'warning' : 'success' ?> w-100">
                                                    <i class="fas fa-<?= ($vehicle[VEHICLES_LOCKED] ?? 0) == 1 ? 'lock' : 'lock-open' ?> me-1"></i>
                                                    <?= ($vehicle[VEHICLES_LOCKED] ?? 0) == 1 ? 'Zárt' : 'Nyitott' ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="badge bg-<?= ($vehicle['engine'] ?? 0) == 1 ? 'success' : 'secondary' ?> w-100">
                                                    <i class="fas fa-<?= ($vehicle['engine'] ?? 0) == 1 ? 'play' : 'stop' ?> me-1"></i>
                                                    Motor
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Rendszám -->
                                        <?php if(!empty($vehicle[VEHICLES_PLATE])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Rendszám</small>
                                            <div class="fw-bold text-center bg-light py-1 rounded">
                                                <?= safe_output($vehicle[VEHICLES_PLATE]) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Model ID -->
                                        <div class="text-center">
                                            <small class="text-muted">Model ID: <?= $vehicle[VEHICLES_MODEL] ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-success btn-sm">
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
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Összes jármű</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Modell</th>
                                                <th>Tulajdonos</th>
                                                <th>Életerő</th>
                                                <th>Üzemanyag</th>
                                                <th>Státusz</th>
                                                <th>Rendszám</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($vehicles as $vehicle): ?>
                                            <tr>
                                                <td><strong>#<?= $vehicle[VEHICLES_ID] ?></strong></td>
                                                <td><?= $vehicle_models[$vehicle[VEHICLES_MODEL]] ?? 'Ismeretlen' ?></td>
                                                <td><?= safe_output($vehicle['character_name'] ?? 'Ismeretlen') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= ($vehicle[VEHICLES_HEALTH] ?? 0) > 500 ? 'success' : 'danger' ?>">
                                                        <?= $vehicle[VEHICLES_HEALTH] ?? 0 ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= $vehicle[VEHICLES_FUEL] ?? 0 ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if(($vehicle[VEHICLES_LOCKED] ?? 0) == 1): ?>
                                                        <span class="badge bg-warning">Zárt</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Nyitott</span>
                                                    <?php endif; ?>
                                                    <?php if(($vehicle['engine'] ?? 0) == 1): ?>
                                                        <span class="badge bg-success">Motor</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if(!empty($vehicle[VEHICLES_PLATE])): ?>
                                                        <code><?= safe_output($vehicle[VEHICLES_PLATE]) ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
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
                            <i class="fas fa-car fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">Nincsenek járműveid</h3>
                            <p class="text-muted">Vásárolj járműveket a szerveren!</p>
                            <a href="#" class="btn btn-success btn-lg">
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