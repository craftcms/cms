#!/usr/bin/env bash
#
# Builds the isolated workbench that the phpbench benchmarks in tests/Benchmark run against.
#
# It copies Testbench's Laravel skeleton to .phpbench/laravel (or CRAFT_BENCHMARK_PATH), so the benchmarks get their
# own database, project config, storage and asset files and never touch the dev workbench. It then seeds the
# workbench content plus ScaleSeeder's structure, without its entries, assets and users (SCALE=0): most control
# panel costs grow with sections, fields, entry types and permissions rather than with element counts.
#
# Usage: scripts/benchmark-setup.sh, then vendor/bin/phpbench run --report=aggregate
set -euo pipefail

cd "$(dirname "$0")/.."

BENCH_PATH="${CRAFT_BENCHMARK_PATH:-$PWD/.phpbench/laravel}"
SKELETON=vendor/orchestra/testbench-core/laravel

rm -rf "$BENCH_PATH" .phpbench/opcache
mkdir -p "$BENCH_PATH" .phpbench/opcache

# Everything but the dev workbench's state: its .env, databases, project config, assets, logs and caches
for path in app artisan bootstrap config lang migrations public resources routes server.php composer.json; do
  cp -R "$SKELETON/$path" "$BENCH_PATH/$path"
done
rm -rf "$BENCH_PATH"/config/craft/project "$BENCH_PATH"/public/assets "$BENCH_PATH"/public/vendor
rm -f "$BENCH_PATH"/bootstrap/cache/*.php

mkdir -p "$BENCH_PATH"/database "$BENCH_PATH"/storage/{app,logs,framework/{cache/data,sessions,views}}
for path in factories migrations seeders; do
  cp -R "$SKELETON/database/$path" "$BENCH_PATH/database/$path"
done
touch "$BENCH_PATH/database/database.sqlite"

cat > "$BENCH_PATH/.env" <<EOF
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$(php -r 'echo base64_encode(random_bytes(32));')
APP_URL=http://localhost
DB_CONNECTION=sqlite
CACHE_STORE=file
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
AUTH_MODEL=\CraftCms\Cms\User\Models\User
EOF

testbench() {
  APP_BASE_PATH="$BENCH_PATH" php -d variables_order=EGPCS vendor/bin/testbench "$@" --no-interaction --force
}

testbench migrate:fresh --seed --seeder='Workbench\Database\Seeders\DatabaseSeeder'
SCALE=0 testbench db:seed --class='Workbench\Database\Seeders\ScaleSeeder'
