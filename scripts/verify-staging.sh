#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

if command -v php >/dev/null 2>&1; then
	lint_php_file() {
		php -l "$1" >/dev/null
	}
else
	if ! docker compose exec -T wordpress php -v >/dev/null 2>&1; then
		echo "PHP is not installed on the host and the WordPress container is not ready." >&2
		echo "Start the local WordPress environment, then run this command again." >&2
		exit 1
	fi

	lint_php_file() {
		local container_file="/var/www/html/wp-content/${1#"$repo_root/"}"
		docker compose exec -T wordpress php -l "$container_file" >/dev/null
	}
fi

while IFS= read -r -d '' php_file; do
	lint_php_file "$php_file"
done < <(find "$repo_root/themes/bvocal" "$repo_root/plugins/bvocal-gig-rsvp" "$repo_root/plugins/bvocal-repertoire" "$repo_root/plugins/bvocal-volunteer-guide" -type f -name '*.php' -print0)

bash "$repo_root/tests/staging-package-test.sh"
bash "$repo_root/tests/homepage-hero-asset-test.sh"
bash "$repo_root/tests/theme-logo-asset-test.sh"

echo "PASS: custom BVOCAL code is ready to package for staging"
