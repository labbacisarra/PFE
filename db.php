<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=sql303.byetcluster.com;dbname=if0_42043747_ecolna;charset=utf8", "if0_42043747", "ywfpzzUywmUo7I");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "خطأ في الاتصال: " . $e->getMessage();
}
?>
