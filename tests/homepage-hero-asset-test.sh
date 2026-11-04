#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
template="$repo_root/themes/bvocal/templates/front-page.html"
hero_asset="$repo_root/themes/bvocal/assets/images/NoKings-GroupPhoto.jpg"
expected_url="/wp-content/themes/bvocal/assets/images/NoKings-GroupPhoto.jpg"

if ! test -f "$hero_asset"; then
	echo "Expected theme-owned hero asset: $hero_asset" >&2
	exit 1
fi

if ! grep -Fq "$expected_url" "$template"; then
	echo "Expected homepage template to use the theme-owned hero asset" >&2
	exit 1
fi

if grep -Fq '/wp-content/uploads/' "$template"; then
	echo "Homepage template must not depend on an uploads-path asset" >&2
	exit 1
fi

echo "PASS: homepage hero is packaged with the theme"
