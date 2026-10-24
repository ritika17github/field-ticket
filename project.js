// Project: customers, jobs, and locations.
$(function () {
    const $customer = $('#customer');
    const $job = $('#job');
    const $location = $('#location');
    const $selects = $customer.add($job).add($location);

    let customers = [];
    let jobs = [];
    let locations = [];

    const selection = {
        customer: '',
        job: '',
        location: ''
    };

    function findJob(id) {
        return jobs.find(function (job) {
            return String(job.id) === id;
        });
    }

    function fillOptions($select, records, placeholder, selectedValue) {
        $select.empty();
        $select.append(new Option(placeholder, ''));

        records.forEach(function (record) {
            $select.append(new Option(record.name, String(record.id)));
        });

        $select.val(selectedValue);
    }

    function renderOptions() {
        const selectedJob = findJob(selection.job);

        const selectedLocation = locations.find(function (location) {
            return String(location.id) === selection.location;
        });

        const availableCustomers = customers.filter(function (customer) {
            return !selectedJob
                || String(customer.id) === String(selectedJob.customer_id);
        });

        const availableJobs = jobs.filter(function (job) {
            const matchesCustomer = !selection.customer
                || String(job.customer_id) === selection.customer;

            const matchesLocation = !selectedLocation
                || String(job.id) === String(selectedLocation.job_id);

            return matchesCustomer && matchesLocation;
        });

        const availableLocations = locations.filter(function (location) {
            const parentJob = findJob(String(location.job_id));

            if (!parentJob) {
                return false;
            }

            const matchesCustomer = !selection.customer
                || String(parentJob.customer_id) === selection.customer;

            const matchesJob = !selection.job
                || String(location.job_id) === selection.job;

            return matchesCustomer && matchesJob;
        });

        fillOptions(
            $customer,
            availableCustomers,
            'Select Customer...',
            selection.customer
        );

        fillOptions(
            $job,
            availableJobs,
            'Select Job...',
            selection.job
        );

        fillOptions(
            $location,
            availableLocations,
            'Select LSD...',
            selection.location
        );
    }

    $customer.on('change', function () {
        selection.customer = $(this).val() || '';
        selection.job = '';
        selection.location = '';

        renderOptions();
    });

    $job.on('change', function () {
        selection.job = $(this).val() || '';
        selection.location = '';

        const selectedJob = findJob(selection.job);

        if (selectedJob) {
            selection.customer = String(selectedJob.customer_id);
        }

        renderOptions();
    });

    $location.on('change', function () {
        selection.location = $(this).val() || '';

        const selectedLocation = locations.find(function (location) {
            return String(location.id) === selection.location;
        });

        if (selectedLocation) {
            const parentJob = findJob(String(selectedLocation.job_id));

            if (parentJob) {
                selection.job = String(parentJob.id);
                selection.customer = String(parentJob.customer_id);
            }
        }

        renderOptions();
    });

    $selects.prop('disabled', true);

    $.getJSON('api/project-options.php')
        .done(function (data) {
            customers = data.customers;
            jobs = data.jobs;
            locations = data.locations;

            renderOptions();
            $selects.prop('disabled', false);
        })
        .fail(function () {
            $('#project-heading').after(
                $('<p>', {
                    role: 'alert',
                    text: 'Unable to load project options. Please refresh to try again.'
                })
            );
        });
});

// Labour: staff, positions, UOMs, and rates.
$(function () {
    const $rows = $('#labour-rows');

    let staff = [];
    let positions = [];
    let assignments = [];
    let rates = [];

    function fillSelect($select, records, placeholder) {
        $select.empty();
        $select.append(new Option(placeholder, ''));

        records.forEach(function (record) {
            $select.append(new Option(record.name, String(record.id)));
        });
    }

    function clearRates($row) {
        $row.find('.labour-regular-rate').val('');
        $row.find('.labour-overtime-rate').val('');
    }

    function resetPosition($row) {
        fillSelect(
            $row.find('.labour-position'),
            [],
            'Select Position...'
        );

        $row.find('.labour-position').prop('disabled', true);
    }

    function resetUom($row) {
        const $uom = $row.find('.labour-uom');

        $uom.empty();
        $uom.append(new Option('Select UOM...', ''));
        $uom.prop('disabled', true);

        clearRates($row);
    }

    function initializeRow($row) {
        fillSelect(
            $row.find('.labour-staff'),
            staff,
            'Select Staff...'
        );

        $row.find('.labour-staff').prop('disabled', false);

        resetPosition($row);
        resetUom($row);
    }

    $rows.on('initialize-row', '.labour-row', function () {
        initializeRow($(this));
    });

    $rows.on('change', '.labour-staff', function () {
        const $row = $(this).closest('.labour-row');
        const staffId = $(this).val();

        resetPosition($row);
        resetUom($row);

        if (!staffId) {
            return;
        }

        const availablePositions = positions.filter(function (position) {
            return assignments.some(function (assignment) {
                return String(assignment.staff_id) === staffId
                    && String(assignment.position_id) === String(position.id);
            });
        });

        fillSelect(
            $row.find('.labour-position'),
            availablePositions,
            'Select Position...'
        );

        $row.find('.labour-position').prop('disabled', false);
    });

    $rows.on('change', '.labour-position', function () {
        const $row = $(this).closest('.labour-row');
        const positionId = $(this).val();

        resetUom($row);

        if (!positionId) {
            return;
        }

        const availableRates = rates.filter(function (rate) {
            return String(rate.position_id) === positionId;
        });

        const $uom = $row.find('.labour-uom');

        availableRates.forEach(function (rate) {
            $uom.append(new Option(rate.uom, rate.uom));
        });

        $uom.prop('disabled', availableRates.length === 0);
    });

    $rows.on('change', '.labour-uom', function () {
        const $row = $(this).closest('.labour-row');
        const positionId = $row.find('.labour-position').val();
        const uom = $(this).val();

        clearRates($row);

        const selectedRate = rates.find(function (rate) {
            return String(rate.position_id) === positionId
                && rate.uom === uom;
        });

        if (!selectedRate) {
            return;
        }

        $row.find('.labour-regular-rate')
            .val(Number(selectedRate.regular_rate).toFixed(2));

        $row.find('.labour-overtime-rate')
            .val(Number(selectedRate.overtime_rate).toFixed(2));
    });

    $rows.find('select').prop('disabled', true);

    $.getJSON('api/labour-options.php')
        .done(function (data) {
            staff = data.staff;
            positions = data.positions;
            assignments = data.assignments;
            rates = data.rates;

            $rows.find('.labour-row').each(function () {
                initializeRow($(this));
            });
        })
        .fail(function () {
            $('#labour-heading').after(
                $('<p>', {
                    role: 'alert',
                    text: 'Unable to load labour options. Please refresh to try again.'
                })
            );
        });
});

// Truck: labels, UOMs, and rates.
$(function () {
    const $rows = $('#truck-rows');

    let trucks = [];
    let rates = [];

    function resetUom($row) {
        const $uom = $row.find('.truck-uom');

        $uom.empty();
        $uom.append(new Option('Select UOM...', ''));
        $uom.prop('disabled', true);

        $row.find('.truck-rate').val('');
    }

    function initializeRow($row) {
        const $truck = $row.find('.truck-label');

        $truck.empty();
        $truck.append(new Option('Select Truck...', ''));

        trucks.forEach(function (truck) {
            $truck.append(
                new Option(truck.label, String(truck.id))
            );
        });

        $truck.prop('disabled', false);
        resetUom($row);
    }

    $rows.on('initialize-row', '.truck-row', function () {
        initializeRow($(this));
    });

    $rows.on('change', '.truck-label', function () {
        const $row = $(this).closest('.truck-row');
        const truckId = $(this).val();

        resetUom($row);

        if (!truckId) {
            return;
        }

        const availableRates = rates.filter(function (rate) {
            return String(rate.truck_id) === truckId;
        });

        const $uom = $row.find('.truck-uom');

        availableRates.forEach(function (rate) {
            $uom.append(new Option(rate.uom, rate.uom));
        });

        $uom.prop('disabled', availableRates.length === 0);
    });

    $rows.on('change', '.truck-uom', function () {
        const $row = $(this).closest('.truck-row');
        const truckId = $row.find('.truck-label').val();
        const uom = $(this).val();

        $row.find('.truck-rate').val('');

        const selectedRate = rates.find(function (rate) {
            return String(rate.truck_id) === truckId
                && rate.uom === uom;
        });

        if (!selectedRate) {
            return;
        }

        $row.find('.truck-rate')
            .val(Number(selectedRate.rate).toFixed(2));
    });

    $rows.find('select').prop('disabled', true);

    $.getJSON('api/truck-options.php')
        .done(function (data) {
            trucks = data.trucks;
            rates = data.rates;

            $rows.find('.truck-row').each(function () {
                initializeRow($(this));
            });
        })
        .fail(function () {
            $('#truck-heading').after(
                $('<p>', {
                    role: 'alert',
                    text: 'Unable to load truck options. Please refresh to try again.'
                })
            );
        });
});

// Calculations for all three sections.
$(function () {
    function numberValue($input) {
        const value = Number($input.val());

        return Number.isFinite(value) && value >= 0 ? value : 0;
    }

    function calculateLabour() {
        let subtotalCents = 0;

        $('#labour-rows .labour-row').each(function () {
            const $row = $(this);

            const regularRate = numberValue(
                $row.find('.labour-regular-rate')
            );

            const regularHours = numberValue(
                $row.find('.labour-regular-hours')
            );

            const overtimeRate = numberValue(
                $row.find('.labour-overtime-rate')
            );

            const overtimeHours = numberValue(
                $row.find('.labour-overtime-hours')
            );

            const amount = (regularRate * regularHours)
                + (overtimeRate * overtimeHours);

            subtotalCents += Math.round(amount * 100);
        });

        $('#labour-subtotal').val(
            (subtotalCents / 100).toFixed(2)
        );
    }

    function calculateTrucks() {
        let subtotalCents = 0;

        $('#truck-rows .truck-row').each(function () {
            const $row = $(this);

            const quantity = numberValue(
                $row.find('.truck-quantity')
            );

            const rate = numberValue(
                $row.find('.truck-rate')
            );

            const totalCents = Math.round(quantity * rate * 100);

            $row.find('.truck-total').val(
                (totalCents / 100).toFixed(2)
            );

            subtotalCents += totalCents;
        });

        $('#truck-subtotal').val(
            (subtotalCents / 100).toFixed(2)
        );
    }

    function calculateMiscellaneous() {
        let subtotalCents = 0;

        $('#misc-rows .misc-row').each(function () {
            const $row = $(this);

            const price = numberValue(
                $row.find('.misc-price')
            );

            const quantity = numberValue(
                $row.find('.misc-quantity')
            );

            const totalCents = Math.round(price * quantity * 100);

            $row.find('.misc-total').val(
                (totalCents / 100).toFixed(2)
            );

            subtotalCents += totalCents;
        });

        $('#misc-subtotal').val(
            (subtotalCents / 100).toFixed(2)
        );
    }

    $('#labour-rows').on(
        'input change',
        '.labour-staff, .labour-position, .labour-uom, ' +
        '.labour-regular-hours, .labour-overtime-hours',
        calculateLabour
    );

    $('#truck-rows').on(
        'input change',
        '.truck-quantity, .truck-label, .truck-uom',
        calculateTrucks
    );

    $('#misc-rows').on(
        'input change',
        '.misc-price, .misc-quantity',
        calculateMiscellaneous
    );

    calculateLabour();
    calculateTrucks();
    calculateMiscellaneous();
});

// Add and remove rows.
$(function () {
    const sections = [
        {
            container: '#labour-rows',
            row: '.labour-row',
            add: '.add-labour',
            remove: '.remove-labour',
            group: 'labour',
            recalculate: '.labour-regular-hours',
            initialize: true
        },
        {
            container: '#truck-rows',
            row: '.truck-row',
            add: '.add-truck',
            remove: '.remove-truck',
            group: 'trucks',
            recalculate: '.truck-quantity',
            initialize: true
        },
        {
            container: '#misc-rows',
            row: '.misc-row',
            add: '.add-misc',
            remove: '.remove-misc',
            group: 'miscellaneous',
            recalculate: '.misc-quantity',
            initialize: false
        }
    ];

    sections.forEach(function (section) {
        const $container = $(section.container);
        const $template = $container.find(section.row).first().clone();

        function renumberRows() {
            $container.find(section.row).each(function (index) {
                $(this).find('[name]').each(function () {
                    const currentName = $(this).attr('name');

                    const updatedName = currentName.replace(
                        /^[^\[]+\[\d+\]/,
                        section.group + '[' + index + ']'
                    );

                    $(this).attr('name', updatedName);
                });
            });
        }

        function createEmptyRow() {
            const $row = $template.clone();

            $row.find('input').val('');
            $row.find('select').val('');
            $row.find('.truck-total, .misc-total').val('0.00');

            return $row;
        }

        function recalculate() {
            $container.find(section.recalculate)
                .first()
                .trigger('input');
        }

        function initialize($row) {
            if (section.initialize) {
                $row.trigger('initialize-row');
            }
        }

        function referenceDataUnavailable($row) {
            return $row.find('.labour-staff, .truck-label')
                .is(':disabled');
        }

        $container.on('click', section.add, function () {
            const $currentRow = $(this).closest(section.row);

            if (referenceDataUnavailable($currentRow)) {
                return;
            }

            const $newRow = createEmptyRow();

            $currentRow.after($newRow);

            initialize($newRow);
            renumberRows();
            recalculate();
        });

        $container.on('click', section.remove, function () {
            const $currentRow = $(this).closest(section.row);

            if (referenceDataUnavailable($currentRow)) {
                return;
            }

            if ($container.find(section.row).length === 1) {
                const $newRow = createEmptyRow();

                $currentRow.replaceWith($newRow);
                initialize($newRow);
            } else {
                $currentRow.remove();
            }

            renumberRows();
            recalculate();
        });
    });
});