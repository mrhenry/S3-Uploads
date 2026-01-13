#!/usr/bin/env bash

S3_UPLOADS_BASE_URL="http://127.0.0.1:9090" S3_UPLOADS_BUCKET=test-bucket S3_UPLOADS_KEY=1234567890 S3_UPLOADS_SECRET=valid-secret S3_UPLOADS_BUCKET_URL="http://127.0.0.1:9090" S3_UPLOADS_REGION="" WP_DEVELOP_DIR=./wp-test-root/wordpress-tests-lib ./vendor/bin/phpunit;
