#!/usr/bin/env bash

yum remove -y httpd httpd-devel php php-devel php-gd php-mbstring php-intl php-mysql php-xml php-xmlrpc php-mcrypt php-imap php-pear php-mysql php-cli php-common php5-dev myself mysql dovecot-mysql MariaDB MariaDB-common MariaDB-compat MariaDB-server phpMyAdmin postfix dovecot dovecot-mysql dovecot-pigeonhole  sendmail ProFTPd proftpd proftpd-mysql vsftpd named bind bind-utils bind-libs bind9 pdns pdns-backend-mysql pdns-server pdns-server-backend-mysql webalizer crontabs 
yum clean all
yum install -y curl