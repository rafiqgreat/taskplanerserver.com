
DROP TABLE IF EXISTS `cor_notifications`;

CREATE TABLE `cor_notifications` (
  `NOTIFICATION_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NOTIFICATION` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `NOTIFICATION_TYPE` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SENDER_ID` int(11) DEFAULT NULL,
  `RECEIVER_ID` int(11) DEFAULT NULL,
  `OBJECT_ID` int(11) DEFAULT NULL,
  `OBJECT_TYPE` enum('task','project','chats','shipment') COLLATE utf8_unicode_ci DEFAULT NULL,
  `IS_READ` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 not read 1 read or opened by user',
  `CREATED_AT1` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `CREATED_AT` bigint(13) NOT NULL,
  PRIMARY KEY (`NOTIFICATION_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10405997 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

