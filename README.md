# WhmcsPortForward
可以对接Whmcs进行销售的端口转发系统，支持TCP/UDP及Ipv6/Ipv4端口转发

默认分支为Sqlite，可切换到Mysql分支使用Mysql数据库!

自定义字段列表:

ptype|转发协议

sport

rsip|源服务器IP

rport|源服务器端口

bandwidth

forwardstatus

除'ptype|转发协议'、'rsip|源服务器IP'、'rport|源服务器端口'以外请全部设置为仅管理员可见!

'ptype|转发协议'、'rsip|源服务器IP'、'rport|源服务器端口'请设置为必填、在订单上时显示、在账单上显示!

安装:

1.安装Redis

2.安装Mysql

3.apt update

4.apt install php php-posix php-pdo-sqlite php-curl

5.编辑config.php

6.导入dbsql.sql到Mysql

7.修改my.cnf并重启mysql

8.Debug : php start.php start Daemon: php start.php start -d

9.Whmcs后台启用流量监控插件

10.添加服务器

11.添加产品

12.开通测试

服务器可选Hash:

&lt;proxyip&gt;10.0.1.1,10.0.1.2,10.0.0.3,10.0.0.4,10.0.0.5,10.0.0.6,10.0.0.7&lt;/proxyip&gt;

my.cnf修改:

将如下内容加入到my.cnf合适位置后重启mysql即可

innodb_lock_wait_timeout=43200

max_allowed_packet=268435456
