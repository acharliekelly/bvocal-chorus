#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

while IFS= read -r -d '' php_file; do
	php -l "$php_file" >/dev/null
done < <(find "$repo_root/themes/bvocal" "$repo_root/plugins/bvocal-gig-rsvp" "$repo_root/plugins/bvocal-repertoire" "$repo_root/plugins/bvocal-volunteer-guide" -type f -name '*.php' -print0)

bash "$repo_root/tests/staging-package-test.sh"
bash "$repo_root/tests/homepage-hero-asset-test.sh"
bash "$repo_root/tests/theme-logo-asset-test.sh"

echo "PASS: custom BVOCAL code is ready to package for staging"
