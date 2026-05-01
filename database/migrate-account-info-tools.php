<?php
/**
 * Migration: Add account_info tools to the TMP tools database
 *
 * Adds 4 new tools to the 'orders' category:
 *   get_account_info, get_account_balance, get_account_equity, get_running_profit
 *
 * Safe to run multiple times — uses INSERT OR IGNORE.
 *
 * Run once:  php database/migrate-account-info-tools.php
 */

$dbPath = __DIR__ . '/app.db';

if (!file_exists($dbPath)) {
    die("Database not found at $dbPath — run database/init.php first.\n");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage() . "\n");
}

// Add missing columns if they don't exist (older schema compatibility)
$existingCols = array_column(
    $db->query("PRAGMA table_info(tools)")->fetchAll(),
    'name'
);
if (!in_array('auth_method', $existingCols)) {
    $db->exec("ALTER TABLE tools ADD COLUMN auth_method TEXT DEFAULT 'api_key_query'");
    echo "Added column: auth_method\n";
}
if (!in_array('response_type', $existingCols)) {
    $db->exec("ALTER TABLE tools ADD COLUMN response_type TEXT DEFAULT 'json'");
    echo "Added column: response_type\n";
}

// Resolve orders category id
$catRow = $db->query("SELECT id FROM tool_categories WHERE name = 'orders'")->fetch();
if (!$catRow) {
    die("'orders' category not found — run database/init-tmp-tools.php first.\n");
}
$catId = $catRow['id'];

$tools = [
    [
        'tool_name'         => 'get_account_info',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&account_info=1',
        'inputs_explanation'=> "No inputs required — returns a full live snapshot of the MT5 account.",
        'description'       => 'Get full MT5 account information: account number, name, broker, server, currency, balance, equity, running floating P/L, open positions, margin, free margin, margin level %, leverage, and trade mode (Real/Demo/Contest)',
        'search_phrase'     => 'get MT5 account information balance equity broker',
    ],
    [
        'tool_name'         => 'get_account_balance',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&account_info=1',
        'inputs_explanation'=> "No inputs required — returns full account info including balance field.",
        'description'       => 'Get the current MT5 account balance (closed-trades balance, not including floating P/L). Also returns currency and equity for context.',
        'search_phrase'     => 'get account balance MT5',
    ],
    [
        'tool_name'         => 'get_account_equity',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&account_info=1',
        'inputs_explanation'=> "No inputs required — returns full account info including equity field.",
        'description'       => 'Get the current MT5 account equity (balance plus all floating/unrealised P/L from open positions)',
        'search_phrase'     => 'get account equity MT5',
    ],
    [
        'tool_name'         => 'get_running_profit',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&account_info=1',
        'inputs_explanation'=> "No inputs required — returns full account info including running_profit field.",
        'description'       => 'Get the total floating (unrealised) profit/loss across all currently open MT5 positions, including swap charges',
        'search_phrase'     => 'get total running floating profit open positions MT5',
    ],
];

$stmt = $db->prepare("
    INSERT OR IGNORE INTO tools
        (category_id, tool_name, tool_format, inputs_explanation, description, search_phrase, auth_method, response_type, enabled)
    VALUES
        (:category_id, :tool_name, :tool_format, :inputs_explanation, :description, :search_phrase, 'api_key_query', 'json', 1)
");

$added   = 0;
$skipped = 0;

foreach ($tools as $t) {
    $stmt->execute([
        ':category_id'        => $catId,
        ':tool_name'          => $t['tool_name'],
        ':tool_format'        => $t['tool_format'],
        ':inputs_explanation' => $t['inputs_explanation'],
        ':description'        => $t['description'],
        ':search_phrase'      => $t['search_phrase'],
    ]);
    if ($db->lastInsertId()) {
        echo "  + added:   {$t['tool_name']}\n";
        $added++;
    } else {
        echo "  ~ skipped: {$t['tool_name']} (already exists)\n";
        $skipped++;
    }
}

echo "\nMigration complete: $added added, $skipped already existed.\n";

// Quick verify
$count = $db->query("SELECT COUNT(*) AS n FROM tools WHERE category_id = $catId")->fetch()['n'];
echo "Orders category now has $count tools.\n";

$total = $db->query("SELECT COUNT(*) AS n FROM tools")->fetch()['n'];
echo "Total tools in database: $total\n";
