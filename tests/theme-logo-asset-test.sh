#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
functions_file="$repo_root/themes/bvocal/functions.php"
logo_asset="$repo_root/themes/bvocal/assets/images/bvocal-logo.png"
expected_url="assets/images/bvocal-logo.png"

if ! test -f "$logo_asset"; then
	echo "Expected theme-owned logo asset: $logo_asset" >&2
	exit 1
fi

if ! grep -Fq "$expected_url" "$functions_file"; then
	echo "Expected the logo block to use the theme-owned logo asset" >&2
	exit 1
fi

if grep -Fq 'get_custom_logo' "$functions_file"; then
	echo "The logo block must not be overridden by WordPress custom-logo uploads" >&2
	exit 1
fi

if grep -Fq "'custom-logo'" "$functions_file"; then
	echo "The theme must not offer the crop-prone WordPress custom-logo control" >&2
	exit 1
fi

echo "PASS: header logo is a theme-owned, aspect-ratio-preserving asset"
