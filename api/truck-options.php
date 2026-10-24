<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../config/database.php';

    $trucks = $pdo
        ->query('SELECT id, label FROM trucks ORDER BY label')
        ->fetchAll();

    $rates = $pdo
        ->query(
            'SELECT truck_id, uom, rate
             FROM truck_rates
             ORDER BY truck_id, uom'
        )
        ->fetchAll();

    echo json_encode([
        'trucks' => $trucks,
        'rates' => $rates,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);

    error_log($exception->getMessage());

    echo json_encode([
        'error' => 'Unable to load truck options.',
    ]);
}
