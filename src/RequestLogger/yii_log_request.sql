CREATE TABLE `yii_log_request`
(
    `id`           int(11) NOT NULL AUTO_INCREMENT,
    `url`          varchar(255) NOT NULL DEFAULT '' COMMENT '请求路由',
    `method`       varchar(255) NOT NULL DEFAULT '' COMMENT '请求方式GET,POST',
    `code`         smallint(6) NOT NULL DEFAULT '0' COMMENT 'Http响应状态码',
    `data_code`    smallint(6) NOT NULL DEFAULT '0' COMMENT '响应数据data中的code值，如果没有则为0',
    `get_params`   text         NOT NULL COMMENT '请求get参数',
    `post_params`  mediumtext   NOT NULL COMMENT '请求post参数',
    `extra_data`   text         NOT NULL COMMENT '请求额外拓展记录数据，默认空',
    `param_length` int(11) NOT NULL DEFAULT '0' COMMENT '为防止部分请求携带特别长的无效参数，本字段记录get,post,和extra_data三个字段内容的长度和供后续即时分析处理',
    `time`         int(11) NOT NULL DEFAULT '0' COMMENT '请求耗时（毫秒）',
    `memory`       int(11) NOT NULL DEFAULT '0' COMMENT '请求内存占用',
    `created_at`   int(11) NOT NULL DEFAULT '0' COMMENT '请求时间',
    `user_id`      int(11) NOT NULL DEFAULT '0' COMMENT '请求用户id',
    `user_name`    varchar(255) NOT NULL DEFAULT '' COMMENT '请求用户名',
    `request_ip`   varchar(20)  NOT NULL DEFAULT '' COMMENT '请求ip',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='请求日志记录';
