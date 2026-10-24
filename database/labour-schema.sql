USE field_ticket;

CREATE TABLE staff (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , name VARCHAR(150) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE positions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , name VARCHAR(150) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE staff_positions (
  staff_id INT UNSIGNED NOT NULL
  , position_id INT UNSIGNED NOT NULL
  , PRIMARY KEY (staff_id, position_id)
  , CONSTRAINT fk_staff_positions_staff FOREIGN KEY (staff_id) REFERENCES staff(id)
  , CONSTRAINT fk_staff_positions_position FOREIGN KEY (position_id) REFERENCES positions(id)
) ENGINE = InnoDB;

CREATE TABLE position_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , position_id INT UNSIGNED NOT NULL
  , uom ENUM('Hourly', 'Fixed') NOT NULL
  , regular_rate DECIMAL(10, 2) NOT NULL
  , overtime_rate DECIMAL(10, 2) NOT NULL
  , UNIQUE KEY uq_position_uom (position_id, uom)
  , CONSTRAINT fk_position_rates_position FOREIGN KEY (position_id) REFERENCES positions(id)
  , CONSTRAINT chk_labour_rates_nonnegative CHECK (
    regular_rate >= 0
    AND overtime_rate >= 0
  )
) ENGINE = InnoDB;
