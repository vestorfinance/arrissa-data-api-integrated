<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/app.db');
$stmt = $pdo->prepare("UPDATE tools SET inputs_explanation = REPLACE(inputs_explanation, 'Max 30 indicators per request', 'Max 3 indicators per request') WHERE inputs_explanation LIKE '%Max 30 indicators per request%'");
$stmt->execute();
echo 'Rows updated: ' . $stmt->rowCount() . PHP_EOL;
