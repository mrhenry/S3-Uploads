#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

PHP_UNIT_CLONE_DIR=`mktemp -d`
PHP_UNIT_POLYFILLS_CLONE_DIR=/tmp/php-unit-polyfills/

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=/tmp/wordpress/

set -ex

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	wget -nv -O /tmp/wordpress.tar.gz https://wordpress.org/${ARCHIVE_NAME}.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	wget -nv -O $WP_CORE_DIR/wp-content/db.php https://raw.github.com/markoheijnen/wp-mysqli/master/db.php
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR

	cd $PHP_UNIT_CLONE_DIR
	git clone git://develop.git.wordpress.org/ ./
	cp -r $PHP_UNIT_CLONE_DIR/tests/phpunit/includes $WP_TESTS_DIR
	cp $PHP_UNIT_CLONE_DIR/wp-tests-config-sample.php $WP_TESTS_DIR/wp-tests-config.php

	mkdir -p $PHP_UNIT_POLYFILLS_CLONE_DIR
	cd $PHP_UNIT_POLYFILLS_CLONE_DIR
	git clone https://github.com/Yoast/PHPUnit-Polyfills.git ./

	cd $WP_TESTS_DIR
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$MYSQL_DATABASE/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$MYSQL_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$MYSQL_PASSWORD/" wp-tests-config.php
	sed $ioption "s|localhost|${MYSQL_HOST}|" wp-tests-config.php
}

install_wp
install_test_suite
