#!/bin/bash
#请确保php命令在环境变量中,即确认php可直接运行
#获取脚本目录路径
scriptPath=$(dirname $0)
scriptPath=${scriptPath/\./$(pwd)}

#获取运行参数
appPath=$1

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

#获取运行队列的pid
pidRows=`ps -o pid -C "php $appPath"`

#是否正在运行
processNum=`ps -o pid -C "php $appPath" | wc -l`
let processNum=processNum-1
if [ "$processNum" -lt 1 ]
then
    echo -e "\033[33m 没有运行! \033[0m"
    exit
fi

#创建控制关闭的文件
shutdownFileName=${appPath////--}
shutdownFileName="$shutdownFileName.shutdown"
shutdonwFilePath="$scriptPath/../data/$shutdownFileName"
if [ ! -e "$shutdonwFilePath" ]
then
    touch $shutdonwFilePath
fi

#循环检测是否关闭完成,直到完成为止
while [ 1 "=" 1 ];do
    processNum=`ps -o pid -C "php $appPath" | wc -l`
    let processNum=processNum-1
    if [ "$processNum" -gt 0 ]
    then
        echo -e "\033[33m 还有$processNum个进程等待关闭,请稍候... \033[0m"
    else
        echo -e "\033[32m 已完成关闭! \033[0m"
        break
    fi
    sleep 1
done

#删除关闭命令
rm -f $shutdonwFilePath

exit


