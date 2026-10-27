#!/usr/bin/env bash
# Builds a folder ready to upload to hosting without Composer or SSH, such as
# the InfinityFree test copy (docs/infinityfree.md).
#
#   scripts/build-bundle.sh [output folder]     default: build/bundle
#
# Contains only committed files plus the production Composer packages. Never
# contains .env, uploads, logs or the test suite.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
out="${1:-$root/build/bundle}"

rm -rf -- "${out:?}"
mkdir -p "$out"
git -C "$root" archive HEAD | tar -x -C "$out"

cd "$out"
composer install --no-dev --prefer-dist --no-interaction --no-progress --quiet

# Composer falls back to git clones when it cannot download archives; their
# .git folders are large and useless on the server.
find vendor -name .git -type d -prune -exec rm -rf {} +

# Not needed on the server. composer.json stays: the go-live check asks the
# site for it to prove project files are not served.
rm -rf -- tests .github scripts docs phpunit.dist.xml build

big="$(find . -name '*.php' -size +1M)"
if [ -n "$big" ]; then
    echo "PHP files over 1 MB (InfinityFree would drop them):" >&2
    echo "$big" >&2
    exit 1
fi

too_big="$(find . -type f -size +10M)"
if [ -n "$too_big" ]; then
    echo "Files over 10 MB (InfinityFree refuses them):" >&2
    echo "$too_big" >&2
    exit 1
fi

echo "Bundle ready in $out: $(find . -type f | wc -l) files, $(du -sh . | cut -f1)."
