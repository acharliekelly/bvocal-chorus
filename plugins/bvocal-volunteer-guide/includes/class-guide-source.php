<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BVOCAL_Guide_Source {
	public static function parse_document( $identity, $markdown ) {
		$markdown = trim( (string) $markdown );
		if ( ! preg_match( '/^#\s+(.+)$/m', $markdown, $matches ) || 0 !== strpos( $markdown, '# ' ) ) {
			throw new InvalidArgumentException( 'A guide must begin with a level-one heading.' );
		}
		$component = 'Theme';
		if ( preg_match( '#^plugins/bvocal-([^/]+)/KB\.md$#', $identity, $plugin ) ) {
			$component = ucwords( str_replace( '-', ' ', $plugin[1] ) );
		}
		return array(
			'identity'  => $identity,
			'title'     => trim( $matches[1] ),
			'component' => $component,
			'markdown'  => $markdown,
			'hash'      => hash( 'sha256', $markdown ),
		);
	}
}
