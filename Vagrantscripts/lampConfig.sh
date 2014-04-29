apt-get update;
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password root';
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password root';
apt-get install vim apache2 php5 php5-intl php5-json mysql-server-5.5 mysql-client-5.5 php5-mysql php5-xdebug rubygems git -y;

#Configure PHP
sed -i 's/display_errors = Off/display_errors = On/g' /etc/php5/apache2/php.ini

#Xdebug
echo "xdebug.remote_enable = on
xdebug.remote_handler = dbgp
xdebug.remote_port=9000
xdebug.max_nesting_level=250
xdebug.remote_host=192.168.55.1
#xdebug.remote_connect_back = on
#xdebug.idekey=PHPSTORM
#xdebug.remote_autostart = 1
" >> /etc/php5/mods-available/xdebug.ini

#Configure ssl
a2enmod ssl
openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=US/ST=Denial/L=Springfield/O=Dis/CN=www.example.com" -keyout server.key  -out server.cert
cp server.cert /etc/ssl/certs
cp server.key /etc/ssl/private
a2ensite default-ssl

/etc/init.d/apache2 restart

#Configure compass
gem install compass

mysql --user=root --password=root -e 'drop database if exists MO; create database MO default character set utf8';
php /var/www/shop2/app/console doctrine:schema:update --force;
