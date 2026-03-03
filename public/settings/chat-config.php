<?php
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings#chat-settings');
    exit;
}

$configFile = __DIR__ . '/../../config/chat.json';

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

$cfg = [
    'webhook_url'      => trim($_POST['webhook_url'] ?? ''),
    'chat_title'       => trim($_POST['chat_title'] ?? 'Arrissa AI'),
    'chat_subtitle'    => trim($_POST['chat_subtitle'] ?? 'Your AI assistant'),
    'initial_messages' => $msgs,
    'enable_streaming' => !empty($_POST['enable_streaming']),
    'available_models' => $models,
];

file_put_contents($configFile, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: /settings?success=chat_config#chat-settings');
exit;
