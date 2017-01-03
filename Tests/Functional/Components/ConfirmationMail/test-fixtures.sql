INSERT INTO `s_order`
VALUES
(10000,'99999',1,63.89,53.69,3.9,3.28,'2017-01-02 16:33:33',0,17,3,'','','','',0,0,'','','',NULL,'','1',9,'EUR',1,1,'','Backend');

INSERT INTO `s_order_details`
VALUES
(1000,10000,'99999',2,'SW10002.1',59.99,1,'Münsterländer Lagerkorn 32% 1,5 Liter',0,0,0,NULL,0,0,1,19,'',NULL,'Liter','Flasche(n)');

INSERT INTO `s_order_shippingaddress`
VALUES
  (1000,1,10000,'shopware AG','','mr','Max','Mustermann','Mustermannstraße 92','48624','Schöppingen',2,NULL,'','',NULL);

INSERT INTO `s_order_billingaddress`
VALUES
  (1000,1,10000,'Muster GmbH','','mr','','Max','Mustermann','Musterstr. 55','55555','Musterhausen','05555 / 555555',2,3,'','','',NULL);
