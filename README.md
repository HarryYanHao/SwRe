# SwRe
index.php是单入口文件,config.php是配置文件
放到本地服务器，php需安装swoole和redis拓展
使用命令行启动异步更新服务：示例：php server.php
访问localhost/SwRe/index.php/test:实际访问的方法是demo.php里面的test方法


异步写入redis成功！
#更新内容
增加redis链接池功能，修改测试案例
