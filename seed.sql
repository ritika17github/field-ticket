USE field_ticket;

INSERT INTO
  customers (id, name)
VALUES
  (1, 'CVS Controls Ltd')
  , (2, 'Prairie Energy Ltd');

INSERT INTO
  jobs (id, customer_id, name)
VALUES
  (1, 1, 'Material Sales')
  , (2, 1, 'Equipment Maintenance')
  , (3, 2, 'Site Inspection');

INSERT INTO
  locations (id, job_id, name)
VALUES
  (1, 1, 'North Site')
  , (2, 1, 'South Site')
  , (3, 2, 'Workshop')
  , (4, 3, 'East Field');