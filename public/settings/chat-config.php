<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings#chat-settings');
    exit;
}

$db = Database::getInstance();

// Build models array from parallel POST arrays
$modelKeys   = $_POST['model_key']   ?? [];
$modelLabels = $_POST['model_label'] ?? [];
$models = [];
foreach ($modelKeys as $i => $k) {
    $k = trim($k);
    $v = trim($modelLabels[$i] ?? '');
    if ($k !== '' && $v !== '') {
        $models[$k] = $v;
    }
}
if (empty($models)) {
    $models = ['analysis-model-1' => 'Analysis Model 1'];
}

// Build initial messages from textarea (one per line)
$rawMessages = $_POST['initial_messages'] ?? '';
$msgs = array_values(array_filter(array_map('trim', explode("\n", $rawMessages))));
if (empty($msgs)) {
    $msgs = ["Hello! How can I help you today?"];
}

$settings = [
    'chat_webhook_url'      => trim($_POST['webhook_url'] ?? ''),
    'chat_title'            => trim($_POST['chat_title'] ?? 'Arrissa AI'),
    'chat_subtitle'         => trim($_POST['chat_subtitle'] ?? 'Your AI assistant'),
    'chat_initial_messages' => json_encode($msgs, JSON_UNESCAPED_UNICODE),
    'chat_enable_streaming' => empty($_POST['enable_streaming']) ? '0' : '1',
    'chat_available_models' => json_encode($models, JSON_UNESCAPED_UNICODE),
];

foreach ($settings as $key => $value) {
    $db->query(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))",
        [$key, $value]
    );
}

header('Location: /settings?success=chat_config#chat-settings');
exit;

