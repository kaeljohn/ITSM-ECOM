<?php
$dsn = 'pgsql:host=ep-empty-term-aoumk75c-pooler.c-2.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require';
$user = 'neondb_owner';
$pass = 'npg_zj31fUDEpYtd';

$pdo = new PDO($dsn, $user, $pass);

// Check ALL employees and their client_ids
echo "=== All employees with client_id ===\n";
$stmt = $pdo->query("SELECT id, first_name, last_name, email, company_email, department, position, client_id, approval_status FROM employees ORDER BY client_id NULLS LAST, id");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $e) {
    echo json_encode($e) . "\n";
}

// Specifically look for client_id = 13
echo "\n=== Employees with client_id = 13 ===\n";
$stmt = $pdo->query("SELECT id, first_name, last_name, email, company_email, department, client_id FROM employees WHERE client_id = 13");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}
