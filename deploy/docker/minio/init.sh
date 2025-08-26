#!/bin/sh
set -e

mc alias set local http://minio:9000 minioadmin minioadmin123

mc mb -p local/linx-media  || true
mc mb -p local/linx-temp   || true

mc anonymous set download local/linx-media || true

echo "MinIO buckets ready"
