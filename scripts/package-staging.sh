#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${1:-$repo_root/build/staging}"

mkdir -p "$output_dir"

package_component() {
	local parent_directory="$1"
	local source_directory="$2"
	local package_name="$3"
	local archive="$output_dir/$package_name.zip"

	rm -f "$archive"
	(
		cd "$repo_root/$parent_directory"
		zip -qr "$archive" "$source_directory" \
			-x '*/.git/*' '*/node_modules/*' '*/vendor/*'
	)

	echo "Created $archive"
}

package_component themes bvocal bvocal-theme
package_component plugins bvocal-gig-rsvp bvocal-gig-rsvp
package_component plugins bvocal-repertoire bvocal-repertoire
package_component plugins bvocal-volunteer-guide bvocal-volunteer-guide
