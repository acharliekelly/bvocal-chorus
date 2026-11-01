<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BVOCAL_Guide_Markdown {
	public static function render( $markdown ) {
		$lines  = preg_split( '/\r?\n/', trim( (string) $markdown ) );
		$output = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^#\s+(.+)$/', $line, $heading ) ) {
				$output[] = '<h1>' . htmlspecialchars( $heading[1], ENT_QUOTES, 'UTF-8' ) . '</h1>';
			} elseif ( '' !== $line && '<' !== substr( $line, 0, 1 ) ) {
				$output[] = '<p>' . htmlspecialchars( $line, ENT_QUOTES, 'UTF-8' ) . '</p>';
			}
		}
		return implode( "\n", $output );
	}
}
