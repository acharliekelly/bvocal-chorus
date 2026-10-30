<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BVOCAL_Guide_Source {
	public static function discover() {
		$root = dirname( __DIR__, 3 );
		$paths = array_merge( array( $root . '/themes/bvocal/KB.md' ), glob( $root . '/plugins/bvocal-*/KB.md' ) ?: array() );
		$documents = array();
		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) { $documents[] = self::parse_document( ltrim( str_replace( $root, '', $path ), '/' ), file_get_contents( $path ) ); }
		}
		return $documents;
	}
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
