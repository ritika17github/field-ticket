# Field Ticket Application

A single-page form for recording project details, labour, truck usage,
and miscellaneous charges.

Built with PHP 8, MySQL 8, jQuery, HTML, and CSS. No PHP framework is used.

## Setup

Tested on Windows with WampServer, PHP 8.3.28, and MySQL 8.4.7.
Linux has not been tested.

Requirements:

- Apache and PHP 8
- MySQL 8
- PHP extensions: pdo_mysql and mbstring
- PHP sessions enabled

1. Copy the field-ticket folder into your web server's document root.
   For a default WAMP installation, use C:\wamp64\www.

2. Create a database named field_ticket using the utf8mb4 character set.

3. Import these files into the empty database in this order:
   - database/schema.sql
   - database/seed.sql
   - database/labour-schema.sql
   - database/labour-seed.sql
   - database/truck-schema.sql
   - database/truck-seed.sql
   - database/ticket-schema.sql

4. Update config/database.php with your MySQL connection details.

5. Start Apache and MySQL, then open:
   http://localhost/field-ticket/

Import each SQL file once. The seed files provide sample data for
testing the dropdowns and rates.

jQuery is included locally, so an internet connection is not needed
to run the application.

## Using the form

Select the customer, job, and location, then enter the project details
and description of work.

Labour and truck rates come from the database. Enter hours or quantities
to calculate the charges. For miscellaneous items, enter the description,
cost, price, and quantity.

Use + to add an empty row and Ã— to remove a row. Removing the last row
leaves an empty replacement row.

Click Finish to save. A successful save displays "Ticket created."
and resets the form.

## Calculations

- Labour: (Regular Rate Ã— Reg Hours) + (Overtime Rate Ã— Overtime)
- Truck: Rate Ã— Qty
- Miscellaneous: Price Ã— Quantity

Row amounts are rounded to two decimal places and added to their
section subtotals. Miscellaneous cost is recorded separately and
does not affect the charge.

The requirements did not specify a separate calculation for Fixed UOM.
I used UOM to select the rate and applied the same formulas to both
Hourly and Fixed.

## Validation and saving

Customer, Job, Location/LSD, Status, and Date are required.
Ordered By, Area/Field, and Description of Work can be blank.

Empty detail rows are skipped. Partially completed rows are validated,
and blank labour hours are treated as zero.

PHP checks the submitted relationships and retrieves rates from MySQL
again when saving. The displayed rates and totals are not trusted as
submitted values.

The ticket and its detail rows are saved in one transaction. If a row
fails validation or an insert fails, the transaction is rolled back.

Applied rates are stored with each ticket row. Totals are derived from
the saved rates, hours, prices, and quantities.

Queries containing submitted values use prepared statements. The form
uses a session-based CSRF token and redirects after a successful save
to prevent normal page refreshes from repeating the submission.

## Other choices

- Description of Work uses a plain textarea, as allowed.
- The default date uses America/Edmonton (Canadian Mountain Time), including daylight saving time.
- Styling uses plain CSS.

## Testing

I manually tested dropdown filtering, rate changes, calculations,
adding and removing rows, and saving tickets with multiple detail rows.

I also checked:

- Refreshing after a save does not create another ticket.
- An incomplete truck row causes the save to roll back.
- Removing a middle row excludes it from the saved ticket.
- Completely empty sections do not create detail records.

I verified the setup by importing all SQL files into a fresh test
database and saving a complete ticket with one row in each section.
