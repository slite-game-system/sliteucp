<?php
session_start();

// Localhost adatbázis kapcsolat
$db_host = "maria.srkhost.eu";
$db_user = "u81156_3aE0rjJLny"; 
$db_pass = "Y=Pdq!nshzHnDing0NsUxvU.";
$db_name = "s81156_seel";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Adatbázis kapcsolat sikertelen: " . $e->getMessage());
}

// SeeMTA admin szintek
function getAdminTitle($level) {
    $titles = [
        0 => "Játékos",
        1 => "Admin 1",
        2 => "Admin 2", 
        3 => "Admin 3",
        4 => "Admin 4",
        5 => "Admin 5",
        6 => "FőAdmin",
        7 => "SuperAdmin",
        8 => "Manager",
        9 => "SysEngineer",
        10 => "Fejlesztő",
        11 => "Tulajdonos"
    ];
    return $titles[$level] ?? "Játékos";
}

// Biztonságos kimenet
function safe_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// SeeMTA specifikus oszlopnevek - A TE TABLÁID SZERINT
// Accounts tábla
define('ACCOUNTS_ID', 'accountId');
define('ACCOUNTS_USERNAME', 'username'); 
define('ACCOUNTS_PASSWORD', 'password');
define('ACCOUNTS_EMAIL', 'email');
define('ACCOUNTS_ADMINLEVEL', 'adminLevel');
define('ACCOUNTS_LASTLOGIN', 'lastOnline');
define('ACCOUNTS_PREMIUM_POINTS', 'premiumPoints');
define('ACCOUNTS_SUSPENDED', 'suspended');
define('ACCOUNTS_ONLINE', 'online');

// Characters tábla
define('CHARACTERS_ID', 'characterId');
define('CHARACTERS_ACCOUNT', 'accountId');
define('CHARACTERS_NAME', 'name');
define('CHARACTERS_MONEY', 'money');
define('CHARACTERS_BANK_MONEY', 'bankMoney');
define('CHARACTERS_PLAYED_MINUTES', 'playedMinutes');
define('CHARACTERS_SKIN', 'skin');
define('CHARACTERS_HEALTH', 'health');
define('CHARACTERS_ARMOR', 'armor');
define('CHARACTERS_ONLINE', 'online');

// Vehicles tábla
define('VEHICLES_ID', 'dbID');
define('VEHICLES_OWNER', 'characterId');
define('VEHICLES_MODEL', 'modelId');
define('VEHICLES_PLATE', 'plate');
define('VEHICLES_HEALTH', 'health');
define('VEHICLES_FUEL', 'fuel');
define('VEHICLES_LOCKED', 'locked');

// Interiors tábla
define('INTERIORS_ID', 'interiorId');
define('INTERIORS_OWNER', 'owner'); // accountId-kat tárol
define('INTERIORS_NAME', 'name');
define('INTERIORS_TYPE', 'type');
define('INTERIORS_PRICE', 'price');
define('INTERIORS_LOCKED', 'locked');

// Bans tábla oszlopai
define('BANS_ID', 'banId');
define('BANS_SERIAL', 'serial');
define('BANS_SERIAL2', 'serial2');
define('BANS_ACCOUNT_ID', 'playerAccountId');
define('BANS_ADMIN_NAME', 'adminName');
define('BANS_REASON', 'banReason');
define('BANS_TIMESTAMP', 'banTimestamp');
define('BANS_EXPIRE_TIMESTAMP', 'expireTimestamp');
define('BANS_DEACTIVATED_BY', 'deactivatedBy');
define('BANS_DEACTIVATED_BY_NAME', 'deactivatedByName');
define('BANS_DEACTIVATE_REASON', 'deactivateReason');
define('BANS_DEACTIVATE_TIMESTAMP', 'deactivateTimestamp');
define('BANS_INACTIVE', 'inactive');
define('BANS_ACTIVE', 'active');
define('BANS_ADMIN_ACCOUNT_ID', 'adminAccountId');
define('BANS_PLAYER_NAME', 'playerName');
define('BANS_DEACTIVATED', 'deactivated');
?>