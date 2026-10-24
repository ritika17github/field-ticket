USE field_ticket;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , location_id INT UNSIGNED NOT NULL
  , status ENUM('Active', 'Pending', 'Closed') NOT NULL
  , ordered_by VARCHAR(150) NOT NULL
  , ticket_date DATE NOT NULL
  , area_field VARCHAR(150) NOT NULL
  , description_of_work TEXT NOT NULL
  , created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  , CONSTRAINT fk_tickets_location FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE = InnoDB;

CREATE TABLE ticket_labour (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , ticket_id INT UNSIGNED NOT NULL
  , staff_id INT UNSIGNED NOT NULL
  , position_id INT UNSIGNED NOT NULL
  , uom ENUM('Hourly', 'Fixed') NOT NULL
  , regular_rate DECIMAL(10, 2) NOT NULL
  , regular_hours DECIMAL(10, 4) NOT NULL
  , overtime_rate DECIMAL(10, 2) NOT NULL
  , overtime_hours DECIMAL(10, 4) NOT NULL
  , CONSTRAINT fk_ticket_labour_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id)
  , CONSTRAINT fk_ticket_labour_assignment FOREIGN KEY (staff_id, position_id) REFERENCES staff_positions(staff_id, position_id)
  , CONSTRAINT chk_ticket_labour_values CHECK (
    regular_rate >= 0
    AND regular_hours >= 0
    AND overtime_rate >= 0
    AND overtime_hours >= 0
  )
) ENGINE = InnoDB;

CREATE TABLE ticket_trucks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , ticket_id INT UNSIGNED NOT NULL
  , truck_id INT UNSIGNED NOT NULL
  , uom ENUM('Hourly', 'Fixed') NOT NULL
  , rate DECIMAL(10, 2) NOT NULL
  , quantity DECIMAL(10, 4) NOT NULL
  , CONSTRAINT fk_ticket_trucks_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id)
  , CONSTRAINT fk_ticket_trucks_truck FOREIGN KEY (truck_id) REFERENCES trucks(id)
  , CONSTRAINT chk_ticket_truck_values CHECK (
    rate >= 0
    AND quantity >= 0
  )
) ENGINE = InnoDB;

CREATE TABLE ticket_miscellaneous (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  , ticket_id INT UNSIGNED NOT NULL
  , description VARCHAR(255) NOT NULL
  , cost DECIMAL(10, 2) NOT NULL
  , price DECIMAL(10, 2) NOT NULL
  , quantity DECIMAL(10, 4) NOT NULL
  , CONSTRAINT fk_ticket_miscellaneous_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id)
  , CONSTRAINT chk_ticket_miscellaneous_values CHECK (
    cost >= 0
    AND price >= 0
    AND quantity >= 0
  )
) ENGINE = InnoDB;