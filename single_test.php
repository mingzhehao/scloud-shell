<?php
/**
 *dsec: 针对指定账号进行数据更新测试
 *author: scloud@alibaba-inc.com
 */

include_once("config.php");

class DbClass {
    static private $instance__;
    private $feedDb = null;
    private $commentDb = null;
    private $commentTableName = "comment_map";
    private $feedTableName = "t_feed";
    private $targetTableName = "t_feed_sid_targetid";
    static public function &instance(){
        if (!isset(self::$instance__)) {
            $class = __CLASS__;
            self::$instance__ = new $class();
        }
        return self::$instance__;
    }
    
    public function __construct() {
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

    public function selectCommentData($mongoid,$tableId=-1)
    {
        if(!is_object($this->commentDb))
        {
            $this->commentDb = $this->commentDbConnect();
        }
        if($tableId == -1){
            $tableName = $this->commentTableName;
        }
        else {
            $tableName = $this->commentTableName."_".$tableId;
        }
        $mongoid = rtrim($mongoid,",");
        /*进入分表查询，合并分表查询结果，返回*/
        $selectSql = "select mongo_comment_id,comment_id from $tableName  where mongo_comment_id in ( $mongoid );";
        $res = $this->getAll($selectSql,$this->commentDb);
        return $res;
    }

   /**
     * 查询并合并分表查询后的数据  
     */
    public function selectAndMergeCommentData($mongoidString)
    {
        $mongoidArray = explode(",",$mongoidString);
        $transferArray= array();
        foreach($mongoidArray as $key => $mongoid){
            $hashId = $this->getHash($mongoid);
        if(isset($transferArray[$hashId])){
                $transferArray[$hashId] .= $mongoid.",";
        }
            else{
                $transferArray[$hashId] = $mongoid.",";
        }
        }
        $transferData = array();
        foreach($transferArray as $tableId => $mongoIdString){
                
            $mongoCommentData = $this->selectCommentData($mongoIdString,$tableId);
            if(!empty($mongoCommentData)){
                $transferData =  array_merge($transferData, $mongoCommentData);
            }
            else{
                continue;
            }
        }
        return $transferData;
    }

    public function selectFeedData($uid)
    {
        if(!is_object($this->feedDb))
        {
            $this->feedDb = $this->feedDbConnect();
        }
        $selectSql = "select sid,userid,targetids from $this->feedTableName  where userid = $uid  and type=2;";
        $res = $this->getAll($selectSql,$this->feedDb);
        return $res;
    }

    public function main($argv)
    {
        $uid = 1766;
        $feedData = $this->selectFeedData($uid);
        if(empty($feedData) || !is_array($feedData)){
            echo "数据为空\n\n";exit;
        }
        $targetIdString = "";
        $feedSidString = "";
        $feedSidArray = array();//sid 数据
        $feedTransferArray = array();//feed映射关系，sid => targetids
        $feedSidTargetidTransferArray = array();//feed_sid_targetid 映射关系 , 数组调整为 targetid => userid_targetid 
        foreach($feedData as $k => $feed){
            $targetIdString .= trim($feed["targetids"]).",";
            $feedSidString  .= $feed["sid"].",";  
            $feedTransferArray[$feed["sid"]] = $feed["targetids"];
            if(strpos($feed["targetids"], ",")){
                $explodeTargetidsArray = explode(",",$feed["targetids"]);
                foreach ($explodeTargetidsArray as $eta => $singleTargetid){
                    $feedSidTargetidTransferArray[$singleTargetid] = $feed["userid"]."-".$singleTargetid;
                }
            }
            else {
                $feedSidTargetidTransferArray[$feed["targetids"]] = $feed["userid"]."-".$feed["targetids"];
            }
        }
        $feedSidString = rtrim($feedSidString,",");
        /*拼接符合mysql插入要求数据*/
        $targetIdString = rtrim($targetIdString,",");
        $targetIdString = str_replace(",","','",$targetIdString);
        $targetIdString = "'".$targetIdString."'";
        /*拼接符合mysql插入要求数据*/
        $mongoCommentData = $this->selectAndMergeCommentData(trim($targetIdString));
        $commentArray = array();
        foreach($mongoCommentData as $key => $comment ) {
            $commentArray[$comment['mongo_comment_id']] = $comment['comment_id'];
        }
        if(empty($commentArray) || !is_array($commentArray)){
            echo "评论数据为空\n\n";exit;
        }
    var_dump($commentArray);exit;

        /** 构造feed表更新数据 **/
        $newFeedTransferArray = array();// feed新映射关系  sid => comment_id (targetids <=> comment_id)
        foreach($feedTransferArray as $sid => $targetids) {
            if(strpos($targetids, ",")){
            $targetidsArray = explode(",",$targetids);
            $newTargetidsString = "";
            foreach ($targetidsArray as $tk => $single){
                if($commentArray["$single"]){
                    $newTargetidsString .= $commentArray["$single"].",";
                }
            }
            $newFeedTransferArray["$sid"] = rtrim($newTargetidsString,",");
            }
            else {
                if($commentArray["$targetids"]){
                    $newFeedTransferArray["$sid"] = $commentArray["$targetids"];
                }
            }
        } 
        $feedSql = "UPDATE $this->feedTableName SET targetids = CASE sid "; 
        $newFeedSidString = "";
        foreach ($newFeedTransferArray as $newSid => $newTargetid) { 
            $newFeedSidString  .= $newSid.",";  
            $feedSql .= sprintf("WHEN %d THEN '%s' ", $newSid, $newTargetid); // 拼接SQL语句 
        } 
        $newFeedSidString = rtrim($newFeedSidString,",");
        $feedSql .= "END WHERE sid IN ($newFeedSidString)"; 
        //echo $feedSql."\n\n"; 
        /** 构造feed表更新数据 **/

        /** 构造feed_sid_target 表更新数据 **/
        $newFeedSidTargetidTransferArray = array();// feed_sid_targetid新映射关系  userid-comment_id => userid-comment_id (targetids <=> comment_id)
        $feedSidTargetidString = "";
        foreach($feedSidTargetidTransferArray as $tid => $utid) {
            if($commentArray["$tid"]){
                $nUtid = str_replace($tid,$commentArray["$tid"],$utid);
                $newFeedSidTargetidTransferArray["$utid"] = $nUtid;
                $feedSidTargetString .= "'".$utid."',";
            }
        } 
        $fstSql = "UPDATE $this->targetTableName SET targetid = CASE targetid "; 
        foreach ($newFeedSidTargetidTransferArray as $oldUtid => $newUtid) { 
            $fstSql .= sprintf("WHEN '%s' THEN '%s' ", $oldUtid, $newUtid); // 拼接SQL语句 
        } 
        $feedSidTargetString = rtrim($feedSidTargetString,",");
        $fstSql .= "END WHERE targetid IN ($feedSidTargetString)"; 
        //echo $fstSql."\n\n"; 
        /** 构造feed_sid_target 表更新数据 **/

        $feedRes = mysqli_query($this->feedDb,$feedSql);
        $fstRes = mysqli_query($this->feedDb,$fstSql);
        if(!$feedRes){
            echo mysqli_connect_errno($this->feedDb) . ": " . mysqli_connect_error($this->feedDb) . "\n";
            error_log("t_feed_".$i." update feed fail $feedSql"."\n\n",3,'update_table_feed.log');
        }
        if(!$fstRes){
            echo mysqli_connect_errno($this->feedDb) . ": " . mysqli_connect_error($this->feedDb) . "\n";
            error_log("t_feed_".$i." update feed_target fail $fstSql"."\n\n",3,'update_table_target.log');
        }
        echo "生成执行完毕 $i \n\n";
        return true;
    }

    public function getHash($mongoId)
    {
        $mongoId = $this->hashCode32(trim($mongoId,"'"));
        return intval(abs($mongoId) % 100);
    }

    public function overflow32($v)
    {
        $v = $v % 4294967296;
        if ($v > 2147483647) return $v - 4294967296;
        elseif ($v < -2147483648) return $v + 4294967296;
        else return $v;
    }
    
    public function hashCode32( $s )
    {
        $h = 0;
        $len = strlen($s);
        for($i = 0; $i < $len; $i++)
        {
            $h = $this->overflow32(31 * $h + ord($s[$i]));
        }
        return $h;
    }


     /**
     * 方法      :  执行查询
     * @$sql     :  查询语句
     * @return   :  查询结果
     */
    public function query($sql,$conn)
    {
        if (!($query = mysqli_query($conn,$sql)))
        {
            $this->errReport();
            return false;
        }
        return $query;
    }

    /**
     * 方法      :  取得所有符合条件的记录，二维数组
     * @$sql     :  查询语句
     * @return   :  所有记录的关联数组数组
     */
    public function getAll($sql,$conn)
    {
        $result = $this->query($sql,$conn);
        if ($result !== false)
        {
            $arr = array();
            while ($row = mysqli_fetch_assoc($result))
            {
                $arr[] = $row;
            }
            return $arr;
        }
        else
        {
            return false;
        }
    }

    /**
     * 方法   :  报错
     * @$str  :  错误信息
     */
    protected function errReport($str = '')
    {
        if (!empty($str))
        {
            echo 'Error: ' . $str;
        }
        else
        {
            echo mysqli_connect_errno($this->feedDb) . ": " . mysqli_connect_error($this->feedDb) . "\n";
        }
    }

    public function __destruct() {
        mysqli_close($this->feedDb);
        mysqli_close($this->commentDb);
        unset($this);
    }
}

$DbClass = DbClass::instance();
$DbClass->main($argv);

?>

