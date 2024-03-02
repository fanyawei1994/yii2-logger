CREATE TABLE `yii_log_debug_logger`
(
    `id`       bigint(20) NOT NULL AUTO_INCREMENT,
    `app_id`   varchar(50)  NOT NULL DEFAULT '' COMMENT ' 应用标识id ',
    `level`    int(11) DEFAULT NULL,
    `category` varchar(255) DEFAULT NULL,
    `log_time` double       DEFAULT NULL,
    `prefix`   text,
    `message`  text,
    `user_id`  int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID ',
    `username` varchar(255) NOT NULL DEFAULT '' COMMENT ' 用户名 ',
    PRIMARY KEY (`id`),
    KEY        `idx_log_level` (`level`),
    KEY        `idx_log_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
