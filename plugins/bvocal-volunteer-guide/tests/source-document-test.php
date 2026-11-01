<?php
/**
 * Run: php plugins/bvocal-volunteer-guide/tests/source-document-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__ ) . '/includes/class-guide-source.php';
require_once dirname( __DIR__ ) . '/includes/class-guide-markdown.php';

function guide_expect( $actual, $expected, $message ) {
	if ( $actual !== $expected ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

$document = BVOCAL_Guide_Source::parse_document( 'plugins/bvocal-repertoire/KB.md', "# Add a repertoire item\n\nUse the Repertoire menu." );
guide_expect( $document['title'], 'Add a repertoire item', 'The first heading becomes the guide title.' );
guide_expect( $document['component'], 'Repertoire', 'A plugin guide identifies its component.' );
guide_expect( isset( $document['hash'] ) && 64 === strlen( $document['hash'] ), true, 'Each source has a SHA-256 hash.' );

guide_expect(
	BVOCAL_Guide_Markdown::render( "# Safe heading\n\n<script>alert('x')</script>" ),
	'<h1>Safe heading</h1>',
	'Unsafe source HTML is removed from rendered guide content.'
);

try {
	BVOCAL_Guide_Source::parse_document( 'themes/bvocal/KB.md', "No heading" );
	fwrite( STDERR, "FAIL: A title-less guide was accepted.\n" );
	exit( 1 );
} catch ( InvalidArgumentException $error ) {
	guide_expect( $error->getMessage(), 'A guide must begin with a level-one heading.', 'Title-less guides explain the problem.' );
}

echo "PASS: guide source documents\n";
