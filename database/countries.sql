LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Andorra','AND'),(2,'United Arab Emirates','ARE'),(3,'Afghanistan','AFG'),(4,'Antigua and Barbuda','ATG'),(5,'Anguilla','AIA'),(6,'Albania','ALB'),(7,'Armenia','ARM'),(8,'Angola','AGO'),(9,'Antarctica','ATA'),(10,'Argentina','ARG'),(11,'American Samoa','ASM'),(12,'Austria','AUT'),(13,'Australia','AUS'),(14,'Aruba','ABW'),(15,'Åland','ALA'),(16,'Azerbaijan','AZE'),(17,'Bosnia and Herzegovina','BIH'),(18,'Barbados','BRB'),(19,'Bangladesh','BGD'),(20,'Belgium','BEL'),(21,'Burkina Faso','BFA'),(22,'Bulgaria','BGR'),(23,'Bahrain','BHR'),(24,'Burundi','BDI'),(25,'Benin','BEN'),(26,'Saint Barthélemy','BLM'),(27,'Bermuda','BMU'),(28,'Brunei','BRN'),(29,'Bolivia','BOL'),(30,'Bonaire','BES'),(31,'Brazil','BRA'),(32,'Bahamas','BHS'),(33,'Bhutan','BTN'),(34,'Bouvet Island','BVT'),(35,'Botswana','BWA'),(36,'Belarus','BLR'),(37,'Belize','BLZ'),(38,'Canada','CAN'),(39,'Cocos [Keeling] Islands','CCK'),(40,'Democratic Republic of the Congo','COD'),(41,'Central African Republic','CAF'),(42,'Republic of the Congo','COG'),(43,'Switzerland','CHE'),(44,'Ivory Coast','CIV'),(45,'Cook Islands','COK'),(46,'Chile','CHL'),(47,'Cameroon','CMR'),(48,'China','CHN'),(49,'Colombia','COL'),(50,'Costa Rica','CRI'),(51,'Cuba','CUB'),(52,'Cape Verde','CPV'),(53,'Curacao','CUW'),(54,'Christmas Island','CXR'),(55,'Cyprus','CYP'),(56,'Czech Republic','CZE'),(57,'Germany','DEU'),(58,'Djibouti','DJI'),(59,'Denmark','DNK'),(60,'Dominica','DMA'),(61,'Dominican Republic','DOM'),(62,'Algeria','DZA'),(63,'Ecuador','ECU'),(64,'Estonia','EST'),(65,'Egypt','EGY'),(66,'Western Sahara','ESH'),(67,'Eritrea','ERI'),(68,'Spain','ESP'),(69,'Ethiopia','ETH'),(70,'Finland','FIN'),(71,'Fiji','FJI'),(72,'Falkland Islands','FLK'),(73,'Micronesia','FSM'),(74,'Faroe Islands','FRO'),(75,'France','FRA'),(76,'Gabon','GAB'),(77,'United Kingdom','GBR'),(78,'Grenada','GRD'),(79,'Georgia','GEO'),(80,'French Guiana','GUF'),(81,'Guernsey','GGY'),(82,'Ghana','GHA'),(83,'Gibraltar','GIB'),(84,'Greenland','GRL'),(85,'Gambia','GMB'),(86,'Guinea','GIN'),(87,'Guadeloupe','GLP'),(88,'Equatorial Guinea','GNQ'),(89,'Greece','GRC'),(90,'South Georgia and the South Sandwich Islands','SGS'),(91,'Guatemala','GTM'),(92,'Guam','GUM'),(93,'Guinea-Bissau','GNB'),(94,'Guyana','GUY'),(95,'Hong Kong','HKG'),(96,'Heard Island and McDonald Islands','HMD'),(97,'Honduras','HND'),(98,'Croatia','HRV'),(99,'Haiti','HTI'),(100,'Hungary','HUN'),(101,'Indonesia','IDN'),(102,'Ireland','IRL'),(103,'Israel','ISR'),(104,'Isle of Man','IMN'),(105,'India','IND'),(106,'British Indian Ocean Territory','IOT'),(107,'Iraq','IRQ'),(108,'Iran','IRN'),(109,'Iceland','ISL'),(110,'Italy','ITA'),(111,'Jersey','JEY'),(112,'Jamaica','JAM'),(113,'Jordan','JOR'),(114,'Japan','JPN'),(115,'Kenya','KEN'),(116,'Kyrgyzstan','KGZ'),(117,'Cambodia','KHM'),(118,'Kiribati','KIR'),(119,'Comoros','COM'),(120,'Saint Kitts and Nevis','KNA'),(121,'North Korea','PRK'),(122,'South Korea','KOR'),(123,'Kuwait','KWT'),(124,'Cayman Islands','CYM'),(125,'Kazakhstan','KAZ'),(126,'Laos','LAO'),(127,'Lebanon','LBN'),(128,'Saint Lucia','LCA'),(129,'Liechtenstein','LIE'),(130,'Sri Lanka','LKA'),(131,'Liberia','LBR'),(132,'Lesotho','LSO'),(133,'Lithuania','LTU'),(134,'Luxembourg','LUX'),(135,'Latvia','LVA'),(136,'Libya','LBY'),(137,'Morocco','MAR'),(138,'Monaco','MCO'),(139,'Moldova','MDA'),(140,'Montenegro','MNE'),(141,'Saint Martin','MAF'),(142,'Madagascar','MDG'),(143,'Marshall Islands','MHL'),(144,'Macedonia','MKD'),(145,'Mali','MLI'),(146,'Myanmar [Burma]','MMR'),(147,'Mongolia','MNG'),(148,'Macao','MAC'),(149,'Northern Mariana Islands','MNP'),(150,'Martinique','MTQ'),(151,'Mauritania','MRT'),(152,'Montserrat','MSR'),(153,'Malta','MLT'),(154,'Mauritius','MUS'),(155,'Maldives','MDV'),(156,'Malawi','MWI'),(157,'Mexico','MEX'),(158,'Malaysia','MYS'),(159,'Mozambique','MOZ'),(160,'Namibia','NAM'),(161,'New Caledonia','NCL'),(162,'Niger','NER'),(163,'Norfolk Island','NFK'),(164,'Nigeria','NGA'),(165,'Nicaragua','NIC'),(166,'Netherlands','NLD'),(167,'Norway','NOR'),(168,'Nepal','NPL'),(169,'Nauru','NRU'),(170,'Niue','NIU'),(171,'New Zealand','NZL'),(172,'Oman','OMN'),(173,'Panama','PAN'),(174,'Peru','PER'),(175,'French Polynesia','PYF'),(176,'Papua New Guinea','PNG'),(177,'Philippines','PHL'),(178,'Pakistan','PAK'),(179,'Poland','POL'),(180,'Saint Pierre and Miquelon','SPM'),(181,'Pitcairn Islands','PCN'),(182,'Puerto Rico','PRI'),(183,'Palestine','PSE'),(184,'Portugal','PRT'),(185,'Palau','PLW'),(186,'Paraguay','PRY'),(187,'Qatar','QAT'),(188,'Réunion','REU'),(189,'Romania','ROU'),(190,'Serbia','SRB'),(191,'Russia','RUS'),(192,'Rwanda','RWA'),(193,'Saudi Arabia','SAU'),(194,'Solomon Islands','SLB'),(195,'Seychelles','SYC'),(196,'Sudan','SDN'),(197,'Sweden','SWE'),(198,'Singapore','SGP'),(199,'Saint Helena','SHN'),(200,'Slovenia','SVN'),(201,'Svalbard and Jan Mayen','SJM'),(202,'Slovakia','SVK'),(203,'Sierra Leone','SLE'),(204,'San Marino','SMR'),(205,'Senegal','SEN'),(206,'Somalia','SOM'),(207,'Suriname','SUR'),(208,'South Sudan','SSD'),(209,'São Tomé and Príncipe','STP'),(210,'El Salvador','SLV'),(211,'Sint Maarten','SXM'),(212,'Syria','SYR'),(213,'Swaziland','SWZ'),(214,'Turks and Caicos Islands','TCA'),(215,'Chad','TCD'),(216,'French Southern Territories','ATF'),(217,'Togo','TGO'),(218,'Thailand','THA'),(219,'Tajikistan','TJK'),(220,'Tokelau','TKL'),(221,'East Timor','TLS'),(222,'Turkmenistan','TKM'),(223,'Tunisia','TUN'),(224,'Tonga','TON'),(225,'Turkey','TUR'),(226,'Trinidad and Tobago','TTO'),(227,'Tuvalu','TUV'),(228,'Taiwan','TWN'),(229,'Tanzania','TZA'),(230,'Ukraine','UKR'),(231,'Uganda','UGA'),(232,'U.S. Minor Outlying Islands','UMI'),(233,'United States','USA'),(234,'Uruguay','URY'),(235,'Uzbekistan','UZB'),(236,'Vatican City','VAT'),(237,'Saint Vincent and the Grenadines','VCT'),(238,'Venezuela','VEN'),(239,'British Virgin Islands','VGB'),(240,'U.S. Virgin Islands','VIR'),(241,'Vietnam','VNM'),(242,'Vanuatu','VUT'),(243,'Wallis and Futuna','WLF'),(244,'Samoa','WSM'),(245,'Kosovo','XKX'),(246,'Yemen','YEM'),(247,'Mayotte','MYT'),(248,'South Africa','ZAF'),(249,'Zambia','ZMB'),(250,'Zimbabwe','ZWE');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;
