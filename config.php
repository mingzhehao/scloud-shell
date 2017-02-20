<?php
/***************************************************************************
 * 基本配置
 * Copyright (c) 2017 github.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
define('APP_STATUS','TEST');// 本地
#define('APP_STATUS','DEV'); // 测试服
#define('APP_STATUS','ONLINE');//正式服
define('TABLE_FOREACH_NUMBER', 200);//遍历多少次 10000次 （总数为 10000 * 100 = 100万）
define('TABLE_INSERT_NUMBER', 10000);//每次执行select insert数据条数 10000条
define('LIMIT',10000);//每次执行数量 遍历数量

if(APP_STATUS == 'TEST')
{
    define('FEED_DB_SERVER', '127.0.0.1');
    define('FEED_DB_USERNAME', 'root');
    define('FEED_DB_PASSWORD', 'password');
    define('FEED_DB_DATABASE', 'feed_test');
    define('COMMENT_DB_SERVER', '127.0.0.1');
    define('COMMENT_DB_USERNAME', 'root');
    define('COMMENT_DB_PASSWORD', 'password');
    define('COMMENT_DB_DATABASE', 'comment_test');
    
}
else if( APP_STATUS == 'DEV')
{
    define('FEED_DB_SERVER', '127.0.0.1');
    define('FEED_DB_USERNAME', 'root');
    define('FEED_DB_PASSWORD', 'password');
    define('FEED_DB_DATABASE', 'feed_test');
    define('COMMENT_DB_SERVER', '127.0.0.1');
    define('COMMENT_DB_USERNAME', 'root');
    define('COMMENT_DB_PASSWORD', 'password');
    define('COMMENT_DB_DATABASE', 'comment_test');
}
else 
{
    define('FEED_DB_SERVER', '127.0.0.1');
    define('FEED_DB_USERNAME', 'root');
    define('FEED_DB_PASSWORD', 'password');
    define('FEED_DB_DATABASE', 'feed_test');
    define('COMMENT_DB_SERVER', '127.0.0.1');
    define('COMMENT_DB_USERNAME', 'root');
    define('COMMENT_DB_PASSWORD', 'password');
    define('COMMENT_DB_DATABASE', 'comment_test');
}
}




?>
