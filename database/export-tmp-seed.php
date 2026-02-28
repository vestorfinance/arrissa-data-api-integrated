<?php
/**
 * Exports current TMP categories + tools as PHP seed arrays
 * for embedding into init.php
 */
$pdo = new PDO('sqlite:' . __DIR__ . '/app.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cats  = $pdo->query('SELECT * FROM tool_categories ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$tools = $pdo->query('SELECT * FROM tools ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$out = "<?php\n// AUTO-GENERATED — do not edit manually\n// Run: php database/export-tmp-seed.php > /dev/null  to regenerate\n\n";
$out .= "\$tmpCategories = [\n";
foreach ($cats as $c) {
    $out .= "    [\n";
    $out .= "        'name'          => " . var_export($c['name'], true) . ",\n";
    $out .= "        'description'   => " . var_export($c['description'], true) . ",\n";
    $out .= "        'endpoint_base' => " . var_export($c['endpoint_base'], true) . ",\n";
    $out .= "        'requires_ea'   => " . var_export((int)$c['requires_ea'], true) . ",\n";
    $out .= "        'ea_name'       => " . var_export($c['ea_name'], true) . ",\n";
    $out .= "    ],\n";
}
$out .= "];\n\n";

$out .= "\$tmpTools = [\n";
foreach ($tools as $t) {
    // get category name
    $catName = '';
    foreach ($cats as $c) { if ($c['id'] == $t['category_id']) { $catName = $c['name']; break; } }
    $out .= "    [\n";
    $out .= "        'category'          => " . var_export($catName, true) . ",\n";
    $out .= "        'tool_name'         => " . var_export($t['tool_name'], true) . ",\n";
    $out .= "        'tool_format'       => " . var_export($t['tool_format'], true) . ",\n";
    $out .= "        'inputs_explanation'=> " . var_export($t['inputs_explanation'], true) . ",\n";
    $out .= "        'description'       => " . var_export($t['description'], true) . ",\n";
    $out .= "        'search_phrase'     => " . var_export($t['search_phrase'], true) . ",\n";
    $out .= "        'response_type'     => " . var_export($t['response_type'], true) . ",\n";
    $out .= "        'enabled'           => " . var_export((int)$t['enabled'], true) . ",\n";
    $out .= "    ],\n";
}
$out .= "];\n";

file_put_contents(__DIR__ . '/tmp-seed-data.php', $out);
echo "Exported " . count($cats) . " categories and " . count($tools) . " tools to database/tmp-seed-data.php\n";
