USE field_ticket;

CREATE TABLE trucks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , label VARCHAR(150) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE truck_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , truck_id INT UNSIGNED NOT NULL
  , uom ENUM('Hourly', 'Fixed') NOT NULL
  , rate DECIMAL(10, 2) NOT NULL
  , UNIQUE KEY uq_truck_uom (truck_id, uom)
  , CONSTRAINT fk_truck_rates_truck FOREIGN KEY (truck_id) REFERENCES trucks(id)
  , CONSTRAINT chk_truck_rate_nonnegative CHECK (rate >= 0)
) ENGINE = InnoDB;
