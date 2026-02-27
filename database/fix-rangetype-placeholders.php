<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/app.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rangeValues = 'last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future';

$stmt = $pdo->query("SELECT id, tool_name, inputs_explanation FROM tools WHERE inputs_explanation LIKE '%rangeType={range}%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Found: ' . count($rows) . ' tools to update' . PHP_EOL;

$upd = $pdo->prepare('UPDATE tools SET inputs_explanation = ? WHERE id = ?');
foreach ($rows as $r) {
    $fixed = str_replace(
        'rangeType={range}',
        "rangeType=<range>\nrangeType    = " . $rangeValues,
        $r['inputs_explanation']
    );
    $upd->execute([$fixed, $r['id']]);
    echo 'Updated: ' . $r['tool_name'] . PHP_EOL;
}
echo 'Done.' . PHP_EOL;
