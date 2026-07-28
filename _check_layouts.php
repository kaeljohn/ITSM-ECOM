<?php
$dsn = 'pgsql:host=ep-silent-sun-ao4q1u8z-pooler.c-2.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require';
$user = 'neondb_owner';
$pass = 'npg_zj31fUDEpYtd';
$pdo = new PDO($dsn, $user, $pass);

echo "=== ALL layout records on silent-sun ===\n";
$stmt = $pdo->query("SELECT id, client_id, draft_layout->>'logo_path' as draft_logo, published_layout->>'logo_path' as pub_logo FROM storefront_layouts ORDER BY id");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}
