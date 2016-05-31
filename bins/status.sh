#!/bin/bash
#请确保php命令在环境变量中,即确认php可直接运行

#获取脚本目录路径
scriptPath=$(dirname $0)
scriptPath=${scriptPath/\./$(pwd)}

#获取第一个应用路径参数
appPath=$1

#检查传入应用参数是否正确
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

#是否正在运行
processNum=`ps -o pid -C "php $appPath" | wc -l`
let processNum=processNum-1
if [ "$processNum" -lt 1 ]
then
    echo -e "\033[33m 没有运行! \033[0m"
    exit
fi

#获取控制显示状态文件的路径
statusFileName=${appPath////--}
statusFileName="$statusFileName.status"
statusFilePath="$scriptPath/../data/$statusFileName"

#获取记录状态数据的文件路径,如果文件存在,则删除重新创建
statusDataFileName="$statusFileName.data"
statusDataFilePath="$scriptPath/../data/$statusDataFileName"
if [ -e "$statusDataFilePath" ]
then
    rm -f $statusDataFilePath
fi
touch $statusDataFilePath

#创建控制显示状态命令文件,如果文件存在,则删除重新创建
if [ -e "$statusFilePath" ]
then
    rm -f $statusFilePath
fi
touch $statusFilePath

echo ""
echo -e " 当前共有 \033[33m$processNum个\033[0m consumer运行,如果下面展示的consumer行数在多次查看状态下一直少于此数量,则有可能存在consumer阻塞死了"
echo " 状态说明:第一列:开始时间,第二列:运行时间,第三列:请求队列数次,第四列:使用内存,第五列:内存峰值"
echo -e " \033[33m正在等待进程反馈状态,获取到结果后会自动依次展示在下文,请稍候...\033[0m"
echo ""
#获取状态文件数据
tail -f $statusDataFilePath
