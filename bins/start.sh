#!/bin/bash
#请确保php命令在环境变量中,即确认php可直接运行

#是否是整数函数
function isInt () {
    if [ $# -lt 1 ]; then
        return 0
    fi

    if [[ $1 =~ ^-?[1-9][0-9]*$ ]]; then
        return 1
    fi

    if [[ $1 =~ ^0$ ]]; then
        return 1
    fi

    return 0
}

#获取脚本目录路径
scriptPath=$(dirname $0)
scriptPath=${scriptPath/\./$(pwd)}

#获取运行参数
appPath=$1
processNum=$2

#路径是否正确
if [ "$appPath" == "" ]
then
    echo -e "\033[31m 应用路径不能为空 \033[0m"
    exit
fi

if [ ! -e "$appPath" ]
then
    echo -e "\033[31m 应用不存在 \033[0m"
    exit
fi

#进程个数参数是否正确
if [ "$processNum" == "" ]
then
    echo -e "\033[31m 必须指定进程个数 \033[0m"
    exit
fi
isInt $processNum
if [ $? == 1 ];
then
    if [ "$processNum" -lt 1 ]
    then
        echo -e "\033[31m 启动的进程个数必须大于等于 1 \033[0m"
        exit
    fi
else
    echo -e "\033[31m 开启的进程个数必须是数字 \033[0m"
    exit
fi

#删除如果有残留的关闭命令
AppPathFileName=${appPath////--}
statusFileName="$AppPathFileName.shutdown"
shutdownFilePath="$scriptPath/../data/$shutdownFilePath"
if [ ! -e "$shutdownFilePath" ]
then
    rm -f $shutdownFilePath
fi

#开始前正在运行的个数
runingProcessNum=`ps -o pid,cmd -C php | grep "$appPath" | wc -l`

#执行对应的命令
runLogFileName=${appPath##*/}
runLogFilePath="/var/log/$runLogFileName.log"
echo -e "\033[33m consumer运行日志文件:$runLogFilePath \033[0m"
for ((i=1;i<=processNum;i++))
do
    php "$appPath" >> "$runLogFilePath" &
    echo -e "\033[33m 正在启动第$i个进程... \033[0m"
    sleep 1 #睡眠一秒再启动下一个,避免瞬间启动过多,对服务器造成压力
done

#新成功启动的进程数量是否等于指定启动的数量
currentProcessNum=`ps -o pid,cmd -C php | grep "$appPath" | wc -l`
let startedProcessNum=currentProcessNum-runingProcessNum
if [ "$startedProcessNum" == "0" ]
then
    echo -e "\033[31m 启动失败,你可以查看日志: $runLogFilePath \033[0m"
    exit
fi
if [ "$startedProcessNum" -lt "$processNum" ]
then
    echo -e "\033[33m 部分consumer启动失败,此次成功启动了$startedProcessNum个consumer \033[0m"
    exit
fi

echo -e "\033[32m 启动完成 \033[0m"
exit
