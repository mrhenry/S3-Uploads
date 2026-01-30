#!/usr/bin/env bash

curl \
	-X POST \
	"http://0.0.0.0:4443/_internal/delete_all"

curl \
	-X POST --data '{ "name": "test-bucket", "location": "US", "storageClass": "STANDARD", "iamConfiguration": { "uniformBucketLevelAccess": { "enabled": true } } }' \
	-H "Content-Type: application/json" \
	"http://0.0.0.0:4443/storage/v1/b"

GS_UPLOADS_BUCKET=test-bucket GS_UPLOADS_BUCKET_URL="http://0.0.0.0:4443" ./vendor/bin/phpunit;
