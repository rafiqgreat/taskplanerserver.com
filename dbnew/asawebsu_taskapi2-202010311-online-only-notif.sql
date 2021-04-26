/*
SQLyog Community v13.1.5  (64 bit)
MySQL - 5.7.23-23 : Database - asawebsu_taskapi2
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
USE `asawebsu_taskapi2`;

/*Table structure for table `cor_notifications` */

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
) ENGINE=InnoDB AUTO_INCREMENT=12036975 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
