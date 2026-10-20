Я изменил код, чтобы ysf дашборд мог работать на современных серверах под линукс.<br> Мои тесты проходили на<br>
**Debian 13 (trixie),<br>
PHP 8.4.26,<br>
lighttpd 1.4.79**

Больше спасибо за эту разработку [DG9VH](https://github.com/dg9vh/YSFReflector-Dashboard) и модификации от [KDØIOE](https://git.kd0ioe.com/alynch/ReflectorDashboard)!<br> Рефлектор был собран из репозитория [DVReflectors](https://github.com/nostar/DVReflectors).

## Описание

Файлы php (у меня) лежат в директории `/var/www/html/ysf2/`

Файл конфигурации находится в `/etc/YSFReflector2.ini`

Исполняемый файл в `/usr/local/bin/YSFReflector`

Логи пишутся в `/tmp/`

После того как вы скопируете файлы, нужно присвоить им правильные права, командами:<br>
```
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 775 /var/www/html
```
для настройки lighttpd проверьте и измените запись:

```
sudo mcedit /etc/systemd/system/lighttpd.service.d/override.conf

[Service]
PrivateTmp=no
```

## Особенности

Эта сборка, отличается тем что все библиотеки и шрифты лежат в `/var/www/html/ysf2/assets/` мне так удобно, потому что YSF рефлектор работает изолированно, без подключения к глобальной сети.

Редактировать настройки дашборда вы можете вручную `sudo mcedit /var/www/html/ysf2/config/config.php` или через браузер ...../ysf2/setup.php , а после редактирования этот файл нужно удалить!

Как работает этот дашборд, вы можете посмотреть в YSF рефлекторе [25619 RU VLGDNK](http://176.192.125.149:88/ysf2/index.php).

Игорь R7HJ
