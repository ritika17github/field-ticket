<?php

session_start();

function textValue($value, $label, $maximumLength)
{
    if (!is_string($value)) {
        throw new InvalidArgumentException($label . ' is invalid.');
    }

    $value = trim($value);

    if (mb_strlen($value, 'UTF-8') > $maximumLength) {
        throw new InvalidArgumentException($label . ' is too long.');
    }

    return $value;
}

function positiveId($value, $label)
{
    if (
        !is_string($value)
        || !preg_match('/^[1-9][0-9]*$/D', $value)
        || strlen($value) > 10
        || (float) $value > 4294967295
    ) {
        throw new InvalidArgumentException('Select a valid ' . $label . '.');
    }

    return (int) $value;
}

function decimalValue($value, $label, $places, $maximum)
{
    if (!is_string($value)) {
        throw new InvalidArgumentException($label . ' is invalid.');
    }

    $value = trim($value);

    $pattern = '/^[0-9]+(?:\.[0-9]{1,' . $places . '})?$/D';

    if (!preg_match($pattern, $value) || (float) $value > $maximum) {
        throw new InvalidArgumentException(
            $label . ' must be a nonnegative number with up to '
            . $places . ' decimal places and no more than ' . $maximum . '.'
        );
    }

    $parts = explode('.', $value);

    return ltrim($parts[0], '0') === ''
        ? '0.' . str_pad($parts[1] ?? '', $places, '0')
        : ltrim($parts[0], '0') . '.'
            . str_pad($parts[1] ?? '', $places, '0');
}

function scaledInteger($value, $places)
{
    $parts = explode('.', $value);

    return ((int) $parts[0] * (10 ** $places))
        + (int) str_pad($parts[1] ?? '', $places, '0');
}

function roundedCents($scaledAmount)
{
    return intdiv($scaledAmount + 5000, 10000);
}

function submittedRows($key)
{
    $rows = $_POST[$key] ?? [];

    if (!is_array($rows)) {
        throw new InvalidArgumentException('Invalid ' . $key . ' rows.');
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Invalid row data.');
        }

        foreach ($row as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Invalid field value.');
            }
        }
    }

    return $rows;
}

function emptyRow($row, $fields)
{
    foreach ($fields as $field) {
        if (trim($row[$field] ?? '') !== '') {
            return false;
        }
    }

    return true;
}

function selectedUom($value)
{
    if (!in_array($value, ['Hourly', 'Fixed'], true)) {
        throw new InvalidArgumentException('Select a valid UOM.');
    }

    return $value;
}

$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Use the ticket form to submit a ticket.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token)
        || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        throw new InvalidArgumentException(
            'Your form session expired. Reload the ticket page and try again.'
        );
    }

    require __DIR__ . '/config/database.php';

    $customerId = positiveId($_POST['customer_id'] ?? '', 'customer');
    $jobId = positiveId($_POST['job_id'] ?? '', 'job');
    $locationId = positiveId($_POST['location_id'] ?? '', 'location');

    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['Active', 'Pending', 'Closed'], true)) {
        throw new InvalidArgumentException('Select a valid status.');
    }

    $orderedBy = textValue(
        $_POST['ordered_by'] ?? '',
        'Ordered By',
        150
    );

    $areaField = textValue(
        $_POST['area_field'] ?? '',
        'Area/Field',
        150
    );

    $description = textValue(
        $_POST['description_of_work'] ?? '',
        'Description of Work',
        65535
    );

    if (strlen($description) > 65535) {
        throw new InvalidArgumentException('Description of Work is too long.');
    }

    $ticketDate = $_POST['ticket_date'] ?? '';

    if (
        !is_string($ticketDate)
        || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $ticketDate)
    ) {
        throw new InvalidArgumentException('Choose a valid date.');
    }

    [$year, $month, $day] = array_map('intval', explode('-', $ticketDate));

    if ($year < 1000 || !checkdate($month, $day, $year)) {
        throw new InvalidArgumentException('Choose a valid date.');
    }

    $labourRows = submittedRows('labour');
    $truckRows = submittedRows('trucks');
    $miscRows = submittedRows('miscellaneous');

    $pdo->beginTransaction();

    $projectQuery = $pdo->prepare(
        'SELECT locations.id
         FROM locations
         INNER JOIN jobs ON jobs.id = locations.job_id
         WHERE locations.id = ?
           AND jobs.id = ?
           AND jobs.customer_id = ?'
    );

    $projectQuery->execute([$locationId, $jobId, $customerId]);

    if (!$projectQuery->fetchColumn()) {
        throw new InvalidArgumentException(
            'Customer, Job, and Location must belong together.'
        );
    }

    $ticketInsert = $pdo->prepare(
        'INSERT INTO tickets
            (location_id, status, ordered_by, ticket_date,
             area_field, description_of_work)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $ticketInsert->execute([
        $locationId,
        $status,
        $orderedBy,
        $ticketDate,
        $areaField,
        $description
    ]);

    $ticketId = (int) $pdo->lastInsertId();

    $labourRateQuery = $pdo->prepare(
        'SELECT position_rates.regular_rate,
                position_rates.overtime_rate
         FROM position_rates
         INNER JOIN staff_positions
             ON staff_positions.position_id = position_rates.position_id
         WHERE staff_positions.staff_id = ?
           AND position_rates.position_id = ?
           AND position_rates.uom = ?'
    );

    $labourInsert = $pdo->prepare(
        'INSERT INTO ticket_labour
            (ticket_id, staff_id, position_id, uom,
             regular_rate, regular_hours, overtime_rate, overtime_hours)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $labourSubtotalCents = 0;

    foreach ($labourRows as $row) {
        if (emptyRow($row, [
            'staff_id', 'position_id', 'uom',
            'regular_hours', 'overtime_hours'
        ])) {
            continue;
        }

        $staffId = positiveId($row['staff_id'] ?? '', 'staff member');
        $positionId = positiveId($row['position_id'] ?? '', 'position');
        $uom = selectedUom($row['uom'] ?? '');

        $regularHours = decimalValue(
            ($row['regular_hours'] ?? '') === ''
                ? '0' : $row['regular_hours'],
            'Reg Hours',
            4,
            999999
        );

        $overtimeHours = decimalValue(
            ($row['overtime_hours'] ?? '') === ''
                ? '0' : $row['overtime_hours'],
            'Overtime',
            4,
            999999
        );

        $labourRateQuery->execute([$staffId, $positionId, $uom]);
        $rate = $labourRateQuery->fetch();

        if (!$rate) {
            throw new InvalidArgumentException(
                'A labour row has an invalid Staff, Position, or UOM.'
            );
        }

        $regularRate = decimalValue(
            $rate['regular_rate'], 'Regular Rate', 2, 999999.99
        );

        $overtimeRate = decimalValue(
            $rate['overtime_rate'], 'Overtime Rate', 2, 999999.99
        );

        $rowCents = roundedCents(
            scaledInteger($regularRate, 2) * scaledInteger($regularHours, 4)
            + scaledInteger($overtimeRate, 2) * scaledInteger($overtimeHours, 4)
        );

        $labourSubtotalCents += $rowCents;

        $labourInsert->execute([
            $ticketId,
            $staffId,
            $positionId,
            $uom,
            $regularRate,
            $regularHours,
            $overtimeRate,
            $overtimeHours
        ]);
    }

    $truckRateQuery = $pdo->prepare(
        'SELECT rate FROM truck_rates WHERE truck_id = ? AND uom = ?'
    );

    $truckInsert = $pdo->prepare(
        'INSERT INTO ticket_trucks
            (ticket_id, truck_id, uom, rate, quantity)
         VALUES (?, ?, ?, ?, ?)'
    );

    $truckSubtotalCents = 0;

    foreach ($truckRows as $row) {
        if (emptyRow($row, ['truck_id', 'uom', 'quantity'])) {
            continue;
        }

        $truckId = positiveId($row['truck_id'] ?? '', 'truck');
        $uom = selectedUom($row['uom'] ?? '');

        $quantity = decimalValue(
            $row['quantity'] ?? '', 'Truck Qty', 4, 999999
        );

        $truckRateQuery->execute([$truckId, $uom]);
        $rate = $truckRateQuery->fetchColumn();

        if ($rate === false) {
            throw new InvalidArgumentException(
                'A truck row has an invalid Truck or UOM.'
            );
        }

        $rate = decimalValue($rate, 'Truck Rate', 2, 999999.99);

        $truckSubtotalCents += roundedCents(
            scaledInteger($rate, 2) * scaledInteger($quantity, 4)
        );

        $truckInsert->execute([
            $ticketId, $truckId, $uom, $rate, $quantity
        ]);
    }

    $miscInsert = $pdo->prepare(
        'INSERT INTO ticket_miscellaneous
            (ticket_id, description, cost, price, quantity)
         VALUES (?, ?, ?, ?, ?)'
    );

    $miscSubtotalCents = 0;

    foreach ($miscRows as $row) {
        if (emptyRow($row, ['description', 'cost', 'price', 'quantity'])) {
            continue;
        }

        $itemDescription = textValue(
            $row['description'] ?? '',
            'Miscellaneous Description',
            255
        );

        if ($itemDescription === '') {
            throw new InvalidArgumentException(
                'Enter a description for each miscellaneous item.'
            );
        }

        $cost = decimalValue(
            $row['cost'] ?? '', 'Miscellaneous Cost', 2, 999999.99
        );

        $price = decimalValue(
            $row['price'] ?? '', 'Miscellaneous Price', 2, 999999.99
        );

        $quantity = decimalValue(
            $row['quantity'] ?? '', 'Miscellaneous Quantity', 4, 999999
        );

        $miscSubtotalCents += roundedCents(
            scaledInteger($price, 2) * scaledInteger($quantity, 4)
        );

        $miscInsert->execute([
            $ticketId, $itemDescription, $cost, $price, $quantity
        ]);
    }

    $pdo->commit();

    // Prevent a refresh from repeating the POST request.
    $_SESSION['ticket_created'] = true;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: index.php', true, 303);
    exit;
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($exception instanceof InvalidArgumentException) {
        http_response_code(422);
        $message = $exception->getMessage();
    } else {
        http_response_code(500);
        error_log($exception->getMessage());
        $message = 'Unable to save the ticket. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket could not be saved</title>
</head>
<body>
    <h1>Ticket could not be saved</h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <p>Use your browser’s Back button to return to the form.</p>
</body>
</html>