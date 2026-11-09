#!/usr/bin/env bash
#
# Runs the phpbench benchmarks in tests/Benchmark, passing any arguments on to `phpbench run`.
#
# Each iteration runs in its own PHP process, so compiled scripts are kept in an opcache file cache, as a PHP-FPM
# worker keeps them in shared memory. Build the benchmark workbench first with scripts/benchmark-setup.sh.
#
# The INI settings live here rather than in phpbench.json because `--php-config` replaces `runner.php_config`
# entirely, and opcache.file_cache must be an absolute path. JIT is off because PHP doesn't use the file cache with it,
# and setup-php turns it on in CI.
set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p .phpbench/opcache

php_config=$(cat <<JSON
{
    "opcache.enable_cli": 1,
    "opcache.file_cache": "$PWD/.phpbench/opcache",
    "opcache.jit": "disable",
    "opcache.validate_timestamps": 1,
    "memory_limit": "1G",
    "variables_order": "EGPCS"
}
JSON
)

# Compile everything once first, so the first iteration measured isn't the one that fills the cache
vendor/bin/phpbench run --php-config="$php_config" --iterations=1 --revs=1 --progress=none > /dev/null

if [ -z "$(ls -A .phpbench/opcache)" ]; then
    echo "The opcache file cache is empty, so every iteration will compile the application from scratch." >&2
    exit 1
fi

exec vendor/bin/phpbench run --php-config="$php_config" "$@"
