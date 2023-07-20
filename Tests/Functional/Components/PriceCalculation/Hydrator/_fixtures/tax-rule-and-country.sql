SET @austriaCountryId = (SELECT id FROM `s_core_countries` WHERE `countryen` = 'AUSTRIA');

UPDATE s_user_addresses
SET `country_id` = @austriaCountryId
WHERE id = 1;

INSERT INTO `s_core_tax_rules` (`id`, `areaID`, `countryID`, `stateID`, `groupID`, `customer_groupID`, `tax`, `name`, `active`)
VALUES (1, 3, @austriaCountryId, NULL, 1, 1, 20.00, "Austria", 1);
