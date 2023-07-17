SET @germanCountryId = (SELECT id FROM `s_core_countries` WHERE `countryen` = 'GERMANY');

UPDATE s_user_addresses
SET `country_id` = @germanCountryId
WHERE id = 1;

DELETE FROM s_core_tax_rules WHERE id = 1;
