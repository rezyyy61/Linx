#!/bin/sh
set -e

# اتصال به سرویس minio داخل شبکه Docker (نه localhost)
mc alias set local http://minio:9000 minioadmin minioadmin123

# باکت‌ها
mc mb -p local/linx-media  || true
mc mb -p local/linx-temp   || true

# دسترسی عمومی برای download از باکت media
mc anonymous set download local/linx-media

# CORS
cat > /tmp/cors.json <<'JSON'
[
  {
    "AllowedMethod": ["GET","PUT","POST"],
    "AllowedOrigin": ["http://app.localhost:8080","http://admin.localhost:8080"],
    "AllowedHeader": ["*"],
    "ExposeHeader": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
JSON

#mc cors set local/linx-media /tmp/cors.json
#mc cors set local/linx-temp  /tmp/cors.json

echo "MinIO buckets ready"
