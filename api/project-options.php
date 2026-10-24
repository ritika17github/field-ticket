<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../config/database.php';

    $customers = $pdo
        ->query('SELECT id, name FROM customers ORDER BY name')
        ->fetchAll();

    $jobs = $pdo
        ->query('SELECT id, customer_id, name FROM jobs ORDER BY name')
        ->fetchAll();

    $locations = $pdo
        ->query('SELECT id, job_id, name FROM locations ORDER BY name')
        ->fetchAll();

    echo json_encode([
        'customers' => $customers,
        'jobs' => $jobs,
        'locations' => $locations,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);

    error_log($exception->getMessage());

    echo json_encode([
        'error' => 'Unable to load project options.',
    ]);
}
