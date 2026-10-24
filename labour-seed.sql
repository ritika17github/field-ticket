USE field_ticket;

INSERT INTO
  staff (id, name)
VALUES
  (1, 'Alex Morgan')
  , (2, 'Sam Taylor');

INSERT INTO
  positions (id, name)
VALUES
  (1, 'Operator')
  , (2, 'Supervisor')
  , (3, 'Technician');

INSERT INTO
  staff_positions (staff_id, position_id)
VALUES
  (1, 1)
  , (1, 2)
  , (2, 1)
  , (2, 3);

INSERT INTO
  position_rates (position_id, uom, regular_rate, overtime_rate)
VALUES
  (1, 'Hourly', 40.00, 60.00)
  , (1, 'Fixed', 200.00, 300.00)
  , (2, 'Hourly', 60.00, 90.00)
  , (2, 'Fixed', 350.00, 500.00)
  , (3, 'Hourly', 50.00, 75.00)
  , (3, 'Fixed', 250.00, 375.00);