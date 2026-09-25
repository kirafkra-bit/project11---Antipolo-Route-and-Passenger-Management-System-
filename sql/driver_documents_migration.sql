-- Run once against an existing PoloNav database.
-- New installations should use sql/polonav.sql instead.

USE polonav;

ALTER TABLE drivers
    ADD COLUMN license_file VARCHAR(255) NULL AFTER status,
    ADD COLUMN valid_id_file VARCHAR(255) NULL AFTER license_file,
    ADD COLUMN or_cr_file VARCHAR(255) NULL AFTER valid_id_file,
    ADD COLUMN ctpl_file VARCHAR(255) NULL AFTER or_cr_file,
    ADD COLUMN mvir_file VARCHAR(255) NULL AFTER ctpl_file,
    ADD COLUMN emission_file VARCHAR(255) NULL AFTER mvir_file;

ALTER TABLE passengers
    ADD COLUMN valid_id_file VARCHAR(255) NULL AFTER address;
