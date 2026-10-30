#!/bin/sh
set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"
if curl -sf -o /dev/null --max-time 1 http://127.0.0.1:8080/; then
  exit 0
fi
PATH="./node_modules/.bin:$PATH" node scripts/with-app-env.mjs vite dev --host 0.0.0.0 --port 8080 > /tmp/dev-server.log 2>&1 &
