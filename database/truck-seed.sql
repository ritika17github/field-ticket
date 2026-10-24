USE field_ticket;

INSERT INTO
  trucks (id, label)
VALUES
  (1, '100 - Ford F150')
  , (2, '105 - Chevrolet Silverado');

INSERT INTO
  truck_rates (truck_id, uom, rate)
VALUES
  (1, 'Hourly', 27.50)
  , (1, 'Fixed', 150.00)
  , (2, 'Hourly', 35.00)
  , (2, 'Fixed', 200.00);
