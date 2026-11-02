#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="$(mktemp -d)"
trap 'rm -rf "$output_dir"' EXIT

bash "$repo_root/scripts/package-staging.sh" "$output_dir"

assert_archive_contains() {
	local archive="$1"
	local expected_path="$2"
	if ! unzip -Z1 "$archive" | grep -Fxq "$expected_path"; then
		echo "Expected $archive to contain $expected_path" >&2
		exit 1
	fi
}

assert_archive_excludes() {
	local archive="$1"
	local forbidden_pattern="$2"
	if unzip -Z1 "$archive" | grep -Eq "$forbidden_pattern"; then
		echo "Expected $archive not to contain $forbidden_pattern" >&2
		exit 1
	fi
}

for component in bvocal-theme bvocal-gig-rsvp bvocal-repertoire bvocal-volunteer-guide; do
	archive="$output_dir/$component.zip"
	if [[ ! -f "$archive" ]]; then
		echo "Missing staging package: $archive" >&2
		exit 1
	fi
done

assert_archive_contains "$output_dir/bvocal-theme.zip" "bvocal/style.css"
assert_archive_contains "$output_dir/bvocal-theme.zip" "bvocal/assets/images/bvocal-logo.png"
assert_archive_contains "$output_dir/bvocal-gig-rsvp.zip" "bvocal-gig-rsvp/bvocal-gig-rsvp.php"
assert_archive_contains "$output_dir/bvocal-repertoire.zip" "bvocal-repertoire/bvocal-repertoire.php"
assert_archive_contains "$output_dir/bvocal-volunteer-guide.zip" "bvocal-volunteer-guide/bvocal-volunteer-guide.php"

for archive in "$output_dir"/*.zip; do
	assert_archive_excludes "$archive" '(^|/)\.git(/|$)'
	assert_archive_excludes "$archive" '(^|/)(node_modules|vendor)(/|$)'
done

echo "PASS: staging packages contain only deployable custom-code roots"
