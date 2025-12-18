<?php

$dbPath = __DIR__ . '/app.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Sample data from economic-events-table.sql
    $events = [
        ['000432722ba88c2f25180aa9d7f02bdc77566829', 'Retail Sales (YoY) (Dec)', '2025-01-17', '07:00:00', 'GBP', '4.2', '3.6', '0', 'Moderate', 'KIOBN'],
        ['0016005f8f4e629c763c7b032d7aebe8a5031e9d', 'Export Price Index (MoM) (Apr)', '2025-05-16', '12:30:00', 'USD', '-0.5', '0.1', '0.1', 'Moderate', 'JXDNC'],
        ['001b36854b5d2c008e18a49b60b41ad63eebc755', 'German ZEW Economic Sentiment (Aug)', '2025-08-12', '09:00:00', 'EUR', '39.5', '34.7', '52.7', 'Moderate', 'DDXDT'],
        ['001eed7aaa0c29c8398fbc24b767ce8d62f017a3', 'Fed Vice Chair for Supervision Barr Speaks', '2025-11-19', '02:30:00', 'USD', NULL, NULL, NULL, 'Moderate', 'JRUEB'],
        ['0027e4873d75dd08416318a6b4bbd01c644da953', 'Continuing Jobless Claims', '2025-05-08', '12:30:00', 'USD', '1890000', '1879000', '1908000', 'Moderate', 'PIIRP'],
    ];
    
    $stmt = $db->prepare("
        INSERT OR REPLACE INTO economic_events 
        (event_id, event_name, event_date, event_time, currency, forecast_value, actual_value, previous_value, impact_level, consistent_event_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $imported = 0;
    foreach ($events as $event) {
        $stmt->execute($event);
        $imported++;
    }
    
    echo "Successfully imported {$imported} economic events!\n";
    
} catch (PDOException $e) {
    die("Import failed: " . $e->getMessage() . "\n");
}
