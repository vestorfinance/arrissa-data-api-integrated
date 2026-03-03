<?php
// One-time migration: seed chat settings keys into existing database
$db = new PDO('sqlite:' . __DIR__ . '/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$keys = [
    'chat_webhook_url'      => '',
    'chat_title'            => 'Arrissa AI',
    'chat_subtitle'         => 'Your AI assistant',
    'chat_initial_messages' => json_encode(["Hello! I'm Arrissa AI. How can I help you today?", "Feel free to ask me anything."]),
    'chat_enable_streaming' => '0',
    'chat_available_models' => json_encode(['analysis-model-1' => 'Analysis Model 1', 'analysis-model-2' => 'Analysis Model 2', 'analysis-model-3' => 'Analysis Model 3']),
];

$stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))");
foreach ($keys as $k => $v) {
    $stmt->execute([$k, $v]);
    echo "Seeded: $k\n";
}
echo "Done.\n";
