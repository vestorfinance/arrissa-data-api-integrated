<?php

$dbPath = __DIR__ . '/app.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create economic_events table
    $db->exec("
        CREATE TABLE IF NOT EXISTS economic_events (
            event_id TEXT PRIMARY KEY,
            event_name TEXT,
            event_date DATE,
            event_time TIME,
            currency TEXT,
            forecast_value TEXT,
            actual_value TEXT,
            previous_value TEXT,
            impact_level TEXT,
            consistent_event_id TEXT
        )
    ");
    
    // Create indexes for economic_events table
    $db->exec("CREATE INDEX IF NOT EXISTS idx_event_date ON economic_events(event_date)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_currency ON economic_events(currency)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_impact_level ON economic_events(impact_level)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_consistent_event_id ON economic_events(consistent_event_id)");
    
    // Insert default admin user (password: admin)
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    $db->exec("
        INSERT OR IGNORE INTO users (username, password) 
        VALUES ('admin', '$hashedPassword')
    ");
    
    // Insert default settings
    $defaults = [
        'app_name' => 'Arrissa Data API',
        'api_key' => 'arr_' . bin2hex(random_bytes(8))
    ];
    
    foreach ($defaults as $key => $value) {
        $db->exec("
            INSERT OR IGNORE INTO settings (key, value) 
            VALUES ('$key', '$value')
        ");
    }
    
    echo "Database initialized successfully!\n";
    echo "Location: $dbPath\n";
    
} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}
