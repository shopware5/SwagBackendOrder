INSERT IGNORE INTO `s_order` (`id`, `ordernumber`, `userID`, `invoice_amount`, `invoice_amount_net`, `invoice_shipping`, `invoice_shipping_net`, `invoice_shipping_tax_rate`, `ordertime`, `status`, `cleared`, `paymentID`, `transactionID`, `comment`, `customercomment`, `internalcomment`, `net`, `taxfree`, `partnerID`, `temporaryID`, `referer`, `cleareddate`, `trackingcode`, `language`, `dispatchID`, `currency`, `currencyFactor`, `subshopID`, `remote_addr`, `deviceType`, `is_proportional_calculation`, `changed`)
VALUES (10000, '99999', 1, 63.89, 53.69, 3.9, 3.28, NULL, '2017-01-02 16:33:33', 0, 17, 3, '', '', '', '', 0, 0, NULL, '', '', NULL, '', '1', 9, 'EUR', 1, 1, '', 'Backend', 0,'2018-06-20 13:39:19');

INSERT IGNORE INTO `s_order_details` (id, orderID, ordernumber, articleID, articleordernumber, price, quantity, `name`, status, shipped, shippedgroup, releasedate, modus, esdarticle, taxID, tax_rate, config, ean, unit, pack_unit, articleDetailID)
VALUES (1000, 10000, '99999', 2, 'SW10002.1', 59.99, 1, 'Münsterländer Lagerkorn 32% 1,5 Liter', 0, 0, 0, NULL, 0, 0, 1, 19, '', NULL, 'Liter', 'Flasche(n)', 123);

INSERT IGNORE INTO `s_order_shippingaddress` (id, userID, orderID, company, department, salutation, firstname, lastname, street, zipcode, city, countryID, stateID, additional_address_line1, additional_address_line2, title)
VALUES (1000, 1, 10000, 'shopware AG', '', 'mr', 'Max', 'Mustermann', 'Mustermannstraße 92', '48624', 'Schöppingen', 2, 3, '', '', NULL);

INSERT IGNORE INTO `s_order_billingaddress` (id, userID, orderID, company, department, salutation, firstname, lastname, street, zipcode, city, phone, countryID, stateID)
VALUES (1000, 1, 10000, 'Muster GmbH', '', 'mr', 'Max', 'Mustermann', 'Musterstr. 55', '55555', 'Musterhausen', '05555 / 555555', 2, 3);
