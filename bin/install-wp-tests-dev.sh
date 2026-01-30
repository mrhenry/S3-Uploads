#!/usr/bin/env bash

mkdir -p ./wp-test-root
TMPDIR=./wp-test-root bash bin/install-wp-tests.sh wp_tests_db wp_test password 127.0.0.1 6.6.4
