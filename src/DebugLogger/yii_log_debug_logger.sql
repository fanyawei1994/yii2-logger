CREATE TABLE `yii_log_debug_logger`
(
    `id`       bigint(20) NOT NULL AUTO_INCREMENT,
    `level`    int(11) DEFAULT NULL,
    `category` varchar(255) DEFAULT NULL,
    `log_time` double       DEFAULT NULL,
    `prefix`   text,
    `message`  text,
    PRIMARY KEY (`id`),
    KEY        `idx_log_level` (`level`),
    KEY        `idx_log_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
