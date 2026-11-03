#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
functions_file="$repo_root/themes/bvocal/functions.php"
theme_css="$repo_root/themes/bvocal/assets/theme.css"
logo_asset="$repo_root/themes/bvocal/assets/images/bvocal-logo-stacked.png"
expected_url="assets/images/bvocal-logo-stacked.png"

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

if ! grep -Fq 'grid-template-columns: 320px 1fr;' "$theme_css"; then
	echo "Expected the desktop header to reserve 320px for the wide logo" >&2
	exit 1
fi

if [[ "$(grep -Fc 'max-width: 320px;' "$theme_css")" -lt 2 ]]; then
	echo "Expected the wide logo to use a readable 320px width on desktop and mobile" >&2
	exit 1
fi

if ! grep -Fq 'font-size: 1rem;' "$theme_css"; then
	echo "Expected the desktop header menu to use readable 1rem text" >&2
	exit 1
fi

echo "PASS: header logo is a theme-owned, aspect-ratio-preserving asset"
