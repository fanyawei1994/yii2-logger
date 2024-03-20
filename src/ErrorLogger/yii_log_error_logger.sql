CREATE TABLE `yii_log_error_logger`
(
    `id`       int(11) NOT NULL AUTO_INCREMENT,
    `app_id`   varchar(50) COLLATE utf8mb4_bin   NOT NULL DEFAULT '' COMMENT ' 应用标识id ',
    `category` varchar(20) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
    `message`  mediumtext COLLATE utf8mb4_bin    NOT NULL COMMENT 'debug内容',
    `log_time` int(11) NOT NULL DEFAULT '0' COMMENT '日志记录时间',
    `user_id`  int(11) NOT NULL DEFAULT '0' COMMENT ' 用户ID ',
    `username` varchar(255) COLLATE utf8mb4_bin  NOT NULL DEFAULT '' COMMENT ' 用户名 ',
    `ip`       varchar(255) COLLATE utf8mb4_bin  NOT NULL DEFAULT '' COMMENT '请求ip',
    PRIMARY KEY (`id`),
    UNIQUE KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='错误debug日志记录';
