<?php
session_start();

date_default_timezone_set('America/Edmonton');

$currentDate = date('Y-m-d');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$ticketCreated = !empty($_SESSION['ticket_created']);
unset($_SESSION['ticket_created']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Ticket</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
    <header>
        <div class="header-content">
            <div>
                <h1>Field Ticket</h1>
                <p class="header-caption">Project details, resources and charges</p>
            </div>
            <span class="ticket-badge">New ticket</span>
        </div>
    </header>

    <main>
        <form
            id="ticket-form"
            method="post"
            action="save-ticket.php"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >

            <section aria-labelledby="project-heading">
                <h2 id="project-heading">Project</h2>

                <div>
                    <label for="customer">Customer Name:</label>
                    <select id="customer" name="customer_id" required>
                        <option value="">Select Customer...</option>
                    </select>
                </div>

                <div>
                    <label for="job">Job Name:</label>
                    <select id="job" name="job_id" required>
                        <option value="">Select Job...</option>
                    </select>
                </div>

                <div>
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div>
                    <label for="location">Location/LSD:</label>
                    <select id="location" name="location_id" required>
                        <option value="">Select LSD...</option>
                    </select>
                </div>

                <div>
                    <label for="ordered-by">Ordered By:</label>
                    <input
                        type="text"
                        id="ordered-by"
                        name="ordered_by"
                        maxlength="150"
                    >
                </div>

                <div>
                    <label for="ticket-date">Date:</label>
                    <input
                        type="date"
                        id="ticket-date"
                        name="ticket_date"
                        min="1000-01-01"
                        max="9999-12-31"
                        value="<?= $currentDate ?>"
                        required
                    >
                </div>

                <div>
                    <label for="area-field">Area/Field:</label>
                    <input
                        type="text"
                        id="area-field"
                        name="area_field"
                        maxlength="150"
                    >
                </div>
            </section>

            <section aria-labelledby="description-heading">
                <h2 id="description-heading">Description of Work</h2>

                <label for="description">Description:</label>
                <textarea
                    id="description"
                    name="description_of_work"
                    rows="8"
                ></textarea>
            </section>

            <section aria-labelledby="labour-heading">
                <h2 id="labour-heading">Labour</h2>

                <div id="labour-rows">
                    <div class="labour-row">
                        <label>
                            Staff
                            <select
                                name="labour[0][staff_id]"
                                class="labour-staff"
                            >
                                <option value="">Select Staff...</option>
                            </select>
                        </label>

                        <label>
                            Position
                            <select
                                name="labour[0][position_id]"
                                class="labour-position"
                            >
                                <option value="">Select Position...</option>
                            </select>
                        </label>

                        <label>
                            UOM
                            <select
                                name="labour[0][uom]"
                                class="labour-uom"
                            >
                                <option value="">Select UOM...</option>
                            </select>
                        </label>

                        <label>
                            Regular Rate
                            <input
                                type="text"
                                class="labour-regular-rate"
                                readonly
                            >
                        </label>

                        <label>
                            Reg Hours
                            <input
                                type="number"
                                name="labour[0][regular_hours]"
                                class="labour-regular-hours"
                                min="0"
                                max="999999"
                                step="0.0001"
                            >
                        </label>

                        <label>
                            Overtime Rate
                            <input
                                type="text"
                                class="labour-overtime-rate"
                                readonly
                            >
                        </label>

                        <label>
                            Overtime
                            <input
                                type="number"
                                name="labour[0][overtime_hours]"
                                class="labour-overtime-hours"
                                min="0"
                                max="999999"
                                step="0.0001"
                            >
                        </label>

                        <button
                            type="button"
                            class="add-labour"
                            aria-label="Add labour row"
                        >+</button>

                        <button
                            type="button"
                            class="remove-labour"
                            aria-label="Remove labour row"
                        >×</button>
                    </div>
                </div>

                <label for="labour-subtotal">Sub-Total</label>
                <input
                    type="text"
                    id="labour-subtotal"
                    value="0.00"
                    readonly
                >
            </section>

            <section aria-labelledby="truck-heading">
                <h2 id="truck-heading">Truck</h2>

                <div id="truck-rows">
                    <div class="truck-row">
                        <label>
                            Label
                            <select
                                name="trucks[0][truck_id]"
                                class="truck-label"
                            >
                                <option value="">Select Truck...</option>
                            </select>
                        </label>

                        <label>
                            Qty
                            <input
                                type="number"
                                name="trucks[0][quantity]"
                                class="truck-quantity"
                                min="0"
                                max="999999"
                                step="0.0001"
                            >
                        </label>

                        <label>
                            UOM
                            <select
                                name="trucks[0][uom]"
                                class="truck-uom"
                            >
                                <option value="">Select UOM...</option>
                            </select>
                        </label>

                        <label>
                            Rate ($)
                            <input
                                type="text"
                                class="truck-rate"
                                readonly
                            >
                        </label>

                        <label>
                            Total
                            <input
                                type="text"
                                class="truck-total"
                                value="0.00"
                                readonly
                            >
                        </label>

                        <button
                            type="button"
                            class="add-truck"
                            aria-label="Add truck row"
                        >+</button>

                        <button
                            type="button"
                            class="remove-truck"
                            aria-label="Remove truck row"
                        >×</button>
                    </div>
                </div>

                <label for="truck-subtotal">Sub-Total</label>
                <input
                    type="text"
                    id="truck-subtotal"
                    value="0.00"
                    readonly
                >
            </section>

            <section aria-labelledby="misc-heading">
                <h2 id="misc-heading">Miscellaneous</h2>

                <div id="misc-rows">
                    <div class="misc-row">
                        <label>
                            Description
                            <input
                                type="text"
                                name="miscellaneous[0][description]"
                                class="misc-description"
                                maxlength="255"
                            >
                        </label>

                        <label>
                            Cost
                            <input
                                type="number"
                                name="miscellaneous[0][cost]"
                                class="misc-cost"
                                min="0"
                                max="999999.99"
                                step="0.01"
                            >
                        </label>

                        <label>
                            Price
                            <input
                                type="number"
                                name="miscellaneous[0][price]"
                                class="misc-price"
                                min="0"
                                max="999999.99"
                                step="0.01"
                            >
                        </label>

                        <label>
                            Quantity
                            <input
                                type="number"
                                name="miscellaneous[0][quantity]"
                                class="misc-quantity"
                                min="0"
                                max="999999"
                                step="0.0001"
                            >
                        </label>

                        <label>
                            Total
                            <input
                                type="text"
                                class="misc-total"
                                value="0.00"
                                readonly
                            >
                        </label>

                        <button
                            type="button"
                            class="add-misc"
                            aria-label="Add miscellaneous row"
                        >+</button>

                        <button
                            type="button"
                            class="remove-misc"
                            aria-label="Remove miscellaneous row"
                        >×</button>
                    </div>
                </div>

                <label for="misc-subtotal">Sub-Total</label>
                <input
                    type="text"
                    id="misc-subtotal"
                    value="0.00"
                    readonly
                >
            </section>

            <div>
                <button type="submit" id="finish" disabled>
                    Finish
                </button>
            </div>
        </form>
    </main>

    <script src="js/jquery-4.0.0.min.js"></script>
    <script src="js/project.js"></script>

    <script>
        $(function () {
            const $form = $('#ticket-form');
            const $finish = $('#finish');

            function updateFinishState() {
                const projectReady = !$('#customer').prop('disabled');
                const labourReady = !$('.labour-staff').first().prop('disabled');
                const truckReady = !$('.truck-label').first().prop('disabled');

                $finish.prop(
                    'disabled',
                    !(projectReady && labourReady && truckReady)
                );
            }

            $(document).ajaxComplete(updateFinishState);
            // Back/forward cache restores the disabled submit button as well.
            $(window).on('pageshow', updateFinishState);
            updateFinishState();

            $form.on('submit', function (event) {
                const projectReady = !$('#customer').prop('disabled');
                const labourReady = !$('.labour-staff').first().prop('disabled');
                const truckReady = !$('.truck-label').first().prop('disabled');

                if (!(projectReady && labourReady && truckReady)) {
                    event.preventDefault();
                    return;
                }

                $finish.prop('disabled', true);
            });
        });
    </script>

    <?php if ($ticketCreated): ?>
        <script>
            alert('Ticket created.');
        </script>
    <?php endif; ?>
</body>
</html>