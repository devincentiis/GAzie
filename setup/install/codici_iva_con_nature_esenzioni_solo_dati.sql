/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dump dei dati della tabella test.gaz_001aliiva_copy: 66 rows
/*!40000 ALTER TABLE `gaz_001aliiva` DISABLE KEYS */;
SET @lastcodice := 0;
SELECT @lastcodice := `codice` FROM  `gaz_001aliiva` WHERE 1 ORDER BY codice DESC LIMIT 1;

INSERT INTO `gaz_001aliiva` (`codice`,`descri`, `fae_natura`, `tipiva`, `aliquo`) VALUES
	(@lastcodice+1,'Fuori campo Iva art. 2 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+2,'Fuori campo Iva art. 3 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+3,'Fuori campo Iva art. 4 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+4,'Fuori campo Iva art. 5 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+5,'Art.7 bis DPR 633/72 (cessione di beni extra-UE)', 'N2.1', 'S',0),
	(@lastcodice+6,'Art.7 ter  DPR 633/72 prest.serv.UE (vendite)', 'N2.1', 'S',0),
	(@lastcodice+7,'Art.7 ter  DPR 633/72 prest.serv.extra-UE', 'N2.1', 'S',0),
	(@lastcodice+8,'Art.7 quater DPR 633/72 prest.serv. UE (vendite)', 'N2.1', 'S',0),
	(@lastcodice+9,'Art.7 quater DPR 633/72 prest.serv. extra-UE', 'N2.1', 'S',0),
	(@lastcodice+10,'Art.7 quinquies DPR 633/72 (prest.serv.)', 'N2.1', 'S',0),
	(@lastcodice+11,'Art.7 sexies, septies DPR 633/72 (prest.serv.)', 'N2.1', 'S',0),
	(@lastcodice+12,'Art. 38 c.5 DL  331/1993', 'N2.2', 'S',0),
	(@lastcodice+13,'Art.17 c.3 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+14,'Art.19 c.3 lett. b DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+15,'Art.50 bis c.4 DL 331/1993', 'N2.2', 'S',0),
	(@lastcodice+16,'Art.74 cc.1e2 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+17,'Art.19 c.3 lett.e DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+18,'Art.13 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+19,'Art.27 c.1e2 DL 98/2011(contr.min.)', 'N2.2', 'S',0),
	(@lastcodice+20,'Art.1 c.54-89 L. 190/2014 succ.mod.(forf.)', 'N2.2', 'S',0),
	(@lastcodice+21,'Art.26 c.3 DPR 633/72', 'N2.2', 'S',0),
	(@lastcodice+22,'DM 9/4/1993', 'N2.2', 'S',0),
	(@lastcodice+23,'Art.26 bis L.196/1997', 'N2.2', 'S',0),
	(@lastcodice+24,'Art.8 c.35 L. 67/1988', 'N2.2', 'S',0),
	(@lastcodice+25,'Art.8 c.1 lett.a DPR 633/72', 'N3.1', 'N',0),
	(@lastcodice+26,'Art.8 c.1 lett.b DPR 633/72', 'N3.1', 'N',0),
	(@lastcodice+27,'Art.2 c.2, n.4 DPR 633/72', 'N3.6', 'N',0),
	(@lastcodice+28,'Art.8 bis DPR 633/72', 'N3.4', 'N',0),
	(@lastcodice+29,'Art.9 c.1 DPR 633/72', 'N3.6', 'N',0),
	(@lastcodice+30,'Non imp. art.72 DPR 633/72', 'N3.6', 'N',0),
	(@lastcodice+31,'Art. 71 DPR 633/72', 'N3.6', 'N',0),
	(@lastcodice+32,'Non imp. art.8 c.1 lett. b-bis  DPR 633/72', 'N3.1', 'N',0),
	(@lastcodice+33,'Non imp. art.8 c.1 lett. c DPR 633/72', 'N3.5', 'N',0),
	(@lastcodice+34,'Non imp. art.8 bis c.2 DPR 633/72', 'N3.4', 'N',0),
	(@lastcodice+35,'Non imp. art.9 c.2 DPR 633/72', 'N3.1', 'N',0),
	(@lastcodice+36,'Non imp.art.72 c.1 DPR 633/72', 'N3.1', 'N',0),
	(@lastcodice+37,'Non imp. art.50 bis c.4 lett.g DL 331/93', 'N3.1', 'N',0),
	(@lastcodice+38,'Non imp. art.50 bis c.4 lett.f DL 331/93', 'N3.2', 'N',0),
	(@lastcodice+39,'Non imp. art.41 DL 331/93', 'N3.2', 'N',0),
	(@lastcodice+40,'Non imp. art.58 c.1 DL 331/93', 'N3.2', 'N',0),
	(@lastcodice+41,'Non imp. art.38 quater c.1 DPR 633/72', 'N3.6', 'N',0),
	(@lastcodice+42,'Non imp. art.14 legge n. 49/1987', 'N3.1', 'N',0),
	(@lastcodice+43,'Esente art.10 DPR 633/72', 'N4', 'E',0),
	(@lastcodice+44,'Esente a.19 c.3 lett.a bis DPR 633/72', 'N4', 'E',0),
	(@lastcodice+45,'Esente art.10 n.27 quinquies DPR 633/72', 'N4', 'E',0),
	(@lastcodice+46,'Esente art.10 n.18 DPR 633/72', 'N4', 'E',0),
	(@lastcodice+47,'Esente art.10 n.19 DPR 633/72', 'N4', 'E',0),
	(@lastcodice+48,'Art.36 DL n.41/1995', 'N5', 'M',0),
	(@lastcodice+49,'Art.36 c.1 DL 41/1995', 'N5', 'M',0),
	(@lastcodice+50,'Art.36 c.5 DL 41/1995', 'N5', 'M',0),
	(@lastcodice+51,'Art.36 c.6 DL 41/1995', 'N5', 'M',0),
	(@lastcodice+52,'Art.74 ter DPR 633/72 (Reg.spec, ag.viaggio)', 'N5', 'M',0),
	(@lastcodice+53,'Acquisti art.17 c.5', 'N6.2', 'R', 22.0),
	(@lastcodice+54,'Vendite art.17 c.5', 'N6.2', 'R', 0),
	(@lastcodice+55,'Acquisti art.17 c.6 l.a bis', 'N6.4', 'R', 22.0),
	(@lastcodice+56,'Vendite art.17 c.6 l.a bis', 'N6.4', 'R',0),
	(@lastcodice+57,'Acquisti art.74 c. 7 e 8', 'N6.1', 'R', 22.0),
	(@lastcodice+58,'Vendite art.74 c. 7 e 8', 'N6.1', 'R', 0),
	(@lastcodice+59,'Acquisti art.17 c.6 l. a', 'N6.3', 'R', 22.0),
	(@lastcodice+60,'Vendite art.17 c.6 l. a', 'N6.3', 'R', 0),
	(@lastcodice+61,'Acquisti art.17 c.6 l. b', 'N6.5', 'R', 22.0),
	(@lastcodice+62,'Vendite Art.17 c.6 l. b', 'N6.5', 'R', 0),
	(@lastcodice+63,'Acquisti art.17 c.6 l. c', 'N6.6', 'R', 22.0),
	(@lastcodice+64,'Vendite Art.17 c.6 l. c', 'N6.6', 'R', 0),
	(@lastcodice+65,'Acquisti art.17 c.6 l. a ter', 'N6.7', 'R', 22.0),
	(@lastcodice+66,'Vendite Art.17 c.6 l. a ter', 'N6.7', 'R', 0),
	(@lastcodice+67,'Acquisti art.17 c.6 l. d bis,d ter,d quater', 'N6.8', 'R', 22.0),
	(@lastcodice+68,'Vendite Art.17 c.6 l. d bis,d ter,d quater', 'N6.8', 'R', 0),
	(@lastcodice+69,'Cessioni beni UE art.7 bis', 'N6.9', 'R', 0.0),
	(@lastcodice+70,'Acquisti art.7 ter acquisti UE', 'N6.9', 'R', 22.0),
	(@lastcodice+71,'Acquisti art.7 quater prest.serv.', 'N6.9', 'R', 22.0),
	(@lastcodice+72,'Acquisti art.7 quinquies prest.serv', 'N6.9', 'R', 22.0),
	(@lastcodice+73,'art.40 c. 3 e 4 e art.41 DL 331/93', 'N7', 'S',0);
/*!40000 ALTER TABLE `gaz_001aliiva` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
