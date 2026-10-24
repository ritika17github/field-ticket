<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../config/database.php';

    $staff = $pdo
        ->query('SELECT id, name FROM staff ORDER BY name')
        ->fetchAll();

    $positions = $pdo
        ->query('SELECT id, name FROM positions ORDER BY name')
        ->fetchAll();

    $assignments = $pdo
        ->query('SELECT staff_id, position_id FROM staff_positions')
        ->fetchAll();

    $rates = $pdo
        ->query(
            'SELECT position_id, uom, regular_rate, overtime_rate
             FROM position_rates
             ORDER BY position_id, uom'
        )
        ->fetchAll();

    echo json_encode([
        'staff' => $staff,
        'positions' => $positions,
        'assignments' => $assignments,
        'rates' => $rates,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);

    error_log($exception->getMessage());

    echo json_encode([
        'error' => 'Unable to load labour options.',
    ]);
}