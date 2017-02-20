#!/bin/bash
##########################
# select 1000万数据，到redis，开启10个php脚本，并发写入，区分区间，平均分配到10个脚本
# 1000万 / 10  依次累加
# 秒数 都是平均值
# 10 进程 每次查询50000 110秒
# 10 进程 每次查询10000 240秒
# 10 进程 每次查询20000 177秒
# 8  进程 每次查询10000 258秒
# 16 进程 每次查询10000 263秒
# author: scloud@alibaba-inc.com
##########################
. /etc/rc.d/init.d/functions
PHPD="nohup /usr/bin/php "
SELF_DIR=$(cd "$(dirname "$0")"; pwd)
SUB_DIR=$(cd "$(dirname "$SELF_DIR")"; pwd)
ROOT_DIR=$(cd "$(dirname "$SUB_DIR")"; pwd)
TOTAL=200000
QJ=0 # 从 TOTAL + QJ 后开始计算 ,从开始算 默认 0
#定义脚本目录 监控脚本文件名称
FILE_NAME="transfer.php"
SYNC_EXEC=$SELF_DIR"/"$FILE_NAME
SYNC_LOG_DIR=$SELF_DIR"/log"
LOG_DIR="${SYNC_LOG_DIR}/"
SRV_NAME="t_feed" #输出名称
LOG_NAME="insert_feed_" #日志输出名称
SHOW_NAME="feed_" #终端展示名称

if [ $# -lt 1 ]
then
    echo "Usage：缺少参数！"
    exit 1
fi

if [ ! -f $SYNC_EXEC ]  
then
    echo "Usage: $SYNC_EXEC 文件不存在! "
    exit 1
fi

if [ ! -d ${SYNC_LOG_DIR} ]; then
    mkdir -p ${SYNC_LOG_DIR}
fi

start()
{
    let AVERAGE=$TOTAL/10
    for id in {1..10}; 
    do 
        let STARTNUMBER=($id-1)*$AVERAGE+$QJ
        let ENDNUMBER=($id)*$AVERAGE+$QJ
        PID=`ps -ef |grep "$FILE_NAME $STARTNUMBER $ENDNUMBER" |grep -v "grep"|awk '{print $2}'`
        if checkpid $PID 2>&1; then
            echo "Service already start"
        else
            if [  $id == 10 ]; then
                let ENDTOTAL=$TOTAL+$QJ
                echo "$id" "$STARTNUMBER" "$ENDTOTAL"   >> ${LOG_DIR}"${LOG_NAME}${id}.log"
                $PHPD $SYNC_EXEC $STARTNUMBER $ENDTOTAL >> ${LOG_DIR}"${LOG_NAME}${id}.log" 2>&1 &
                echo ${LOG_DIR}"${SHOW_NAME}$id.log"
            else
                echo "$id" "$STARTNUMBER" "$ENDNUMBER"  >> ${LOG_DIR}"${LOG_NAME}${id}.log"
                $PHPD $SYNC_EXEC $STARTNUMBER $ENDNUMBER >> ${LOG_DIR}"${LOG_NAME}${id}.log" 2>&1 &
                echo ${LOG_DIR}"${SHOW_NAME}$id.log"
            fi
        fi
    done;

}

stop()
{
    let AVERAGE=$TOTAL/10
    for id in {1..10}; 
    do 
        let STARTNUMBER=($id-1)*$AVERAGE
        let ENDNUMBER=($id)*$AVERAGE
        PID=`ps -ef |grep "$FILE_NAME $STARTNUMBER $ENDNUMBER" |grep -v "grep"|awk '{print $2}'`
        if checkpid $PID 2>&1; then
            kill -TERM $PID >/dev/null 2>&1
            usleep 100000
            if checkpid $PID && sleep 1 && checkpid $PID ; then
                kill -KILL $PID >/dev/null 2>&1
            fi
            PID=`ps -ef |grep "$FILE_NAME $STARTNUMBER $ENDNUMBER" |grep -v "grep"|awk '{print $2}'`
            if [ X$PID == 'X' ] ; then
                echo "Stop $SRV_NAME $id succed!"
            else
                echo "Stop $SRV_NAME $id failed!"
            fi
        else
            echo "Service already stop"
        fi
    done
}

restart () {
    stop
    sleep 2
    start
}

RETVAL=0

case "$1" in
  start)
    start
    ;;
  stop)
    stop
    ;;
  restart|reload|force-reload)
    restart
    ;;
  *)
    echo "Usage: $0 {start|stop|restart|reload|force-reload}"
    RETVAL=1
esac

exit $RETVAL

