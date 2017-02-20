<?php
#################
# 构建模拟数据  #
#################
/**
 * 插入测试数据，进行数据量执行时间测试 100万
 */
include_once("config.php");
define('TABLE_FOREACH_NUMBER', 20);//遍历多少次 10000次 （总数为 10000 * 100 = 100万）
define('TABLE_INSERT_NUMBER', 1000 );//每次执行insert数据条数 100条

class DbClass {
    static private $instance__;
    private $_db = null;
    static public function &instance(){
        if (!isset(self::$instance__)) {
            $class = __CLASS__;
            self::$instance__ = new $class();
        }
        return self::$instance__;
    }
    
    public function __construct() {
        $this->_db = $this->remoteConnect();
    }

    public function remoteConnect()
    {
        $conn = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
        mysql_select_db(DB_DATABASE,$conn);
        mysql_query('SET NAMES UTF8',$conn);
        return $conn;
    }

    public function InsertData()
    {
        $table1 = "truncate table t_feed;";
        $table2 = "truncate table t_feed_sid_targetid;";
        $table3 = "truncate table comment_map;";
        $result = mysql_query($table1,$this->_db);
        $result = mysql_query($table2,$this->_db);
        $result = mysql_query($table3,$this->_db);
        for($i = 1;$i<= TABLE_FOREACH_NUMBER ;$i++)
        {
            $nStart = ($i-1)*TABLE_INSERT_NUMBER+1;
            $nEnd = $i * TABLE_INSERT_NUMBER;
            $insertSql = "";
            $targetInsertSql = "";
            $commentMapSql = "";
            for ($j=$nStart;$j<=$nEnd;$j++ ){
                $targetids = "'560199aab4dc45181$i$j'";
                $targetid = "560199aab4dc45181$i$j";
                $defaultSql =  "($j, $j, '2', $targetids, '322301811', '1442945452', '2015-09-23'),";
                $utid = $j."-".$targetid;
                $targetSidSql = "($j,'$utid',2),";
                $commentSql = "($j,$targetids,$j$i),";
                $targetInsertSql .= $targetSidSql;
                $insertSql .= $defaultSql;
                $commentMapSql .= $commentSql;
            }
            $insertSql = rtrim($insertSql,",");
            $targetInsertSql = rtrim($targetInsertSql,",");
            $commentMapSql = rtrim($commentMapSql,",");
            //echo $insertSql."\n\n";
            //echo $targetInsertSql."\n\n";
            //echo $commentSql."\n\n";
            $sql = "INSERT INTO `t_feed` VALUES $insertSql";
            $result = mysql_query($sql,$this->_db);
            if(!$result){
                echo mysql_errno($this->_db) . ": " . mysql_error($this->_db) . "\n";
                error_log(date("Y-m-d H:i:s",time())." "."t_feed_".$i." create fail "."\n\n",3,'log/insert_table.log');
            }
            $sql = "INSERT INTO `t_feed_sid_targetid` VALUES $targetInsertSql";
            $result = mysql_query($sql,$this->_db);
            if(!$result){
                echo mysql_errno($this->_db) . ": " . mysql_error($this->_db) . "\n";
                error_log(date("Y-m-d H:i:s",time())." "."t_feed_".$i." create fail "."\n\n",3,'log/insert_table.log');
            }
            $sql = "INSERT INTO `comment_map` VALUES $commentMapSql";
            $result = mysql_query($sql,$this->_db);
            if(!$result){
                echo mysql_errno($this->_db) . ": " . mysql_error($this->_db) . "\n";
                error_log(date("Y-m-d H:i:s",time())." "."t_feed_".$i." create fail "."\n\n",3,'log/insert_table.log');
            }
        }

        echo "全部生成执行完毕\n\n";
        return true;
    }

    public function __destruct() {
        mysql_close();
        unset($this);
    }
}

$DbClass = DbClass::instance();
$DbClass->InsertData();

?>
