<?php
###################################
# 测试各环境下数据库连接情况      #
# author: scloud@alibaba-inc.com #
###################################
include_once("config.php");
class TestConnectMysql {
    private $feedDb;
    private $commentDb;
    
    public function __construct(){
        $this->feedDb = $this->feedDbConnect();
        $this->commentDb = $this->commentDbConnect();
    }

    public function feedDbConnect()
    {
        $conn = mysqli_connect(FEED_DB_SERVER, FEED_DB_USERNAME, FEED_DB_PASSWORD,FEED_DB_DATABASE);
        if (mysqli_connect_errno()) {
            exit("feed数据库连接失败!");
        }
        mysqli_set_charset($conn, "utf8");
        return $conn;
    }

    public function commentDbConnect()
    {
        $conn = mysqli_connect(COMMENT_DB_SERVER, COMMENT_DB_USERNAME, COMMENT_DB_PASSWORD,COMMENT_DB_DATABASE);
        if (mysqli_connect_errno()) {
            exit("comment数据库连接失败!");
        }
        mysqli_set_charset($conn, "utf8");
        return $conn; 
    }

    public function __destruct() {
        mysqli_close($this->feedDb);
        mysqli_close($this->commentDb);
        unset($this);
    }

    public function showConnect(){
        if(is_object($this->feedDb))
            echo "feed连接成功!\n\n";
        else
            echo "feed连接失败!\n\n";
        if(is_object($this->commentDb))
            echo "comment连接成功!\n\n";
        else
            echo "comment连接失败!\n\n";
    }


}

$test = new TestConnectMysql();
$test->showConnect();

