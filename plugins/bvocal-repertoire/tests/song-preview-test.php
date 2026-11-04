<?php
/** Run with wp eval-file; uses in-memory fixtures and existing local catalogue only. */
require_once dirname( __DIR__ ) . '/includes/class-song-preview.php';
global $wpdb;
function preview_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } }
function preview_fixture( $content ) { return (object) array( 'ID'=>0, 'post_name'=>'fixture', 'post_title'=>'Fixture', 'post_status'=>'publish', 'post_password'=>'', 'post_content'=>$content ); }
$body = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>By Heidi Wilson / arr. by L. Gallatin</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Hold on<br>my dear ones</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Video</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Watch the performance <a href="https://www.youtube.com/shorts/example">here</a></p><!-- /wp:paragraph --></div><!-- /wp:group -->';
$r = BVOCAL_Song_Preview::preview( preview_fixture( $body ) );
preview_expect( 'Heidi Wilson' === $r['proposed']['author'], 'Split explicit author from arranger.' );
preview_expect( 'L. Gallatin' === $r['proposed']['arranger'], 'Preserve arranger spelling.' );
preview_expect( "Hold on\nmy dear ones" === $r['proposed']['lyrics'], 'Preserve lyric line breaks through nested groups.' );
preview_expect( 1 === count($r['unmapped']) && 'link' === $r['unmapped'][0]['type'], 'YouTube remains a reported unmapped component.' );
preview_expect( false !== strpos($r['unmapped'][0]['text'], 'Watch the performance'), 'Preserve link context, not just here.' );
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:paragraph --><p>Arrangement by Lisa Gallatin</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Practice Recordings</h2><!-- /wp:heading --><!-- wp:audio --><figure class="wp-block-audio"><audio src="https://example.org/missing.mp3"></audio></figure><!-- /wp:audio -->') );
preview_expect( '' === $r['proposed']['author'] && 'Lisa Gallatin' === $r['proposed']['arranger'], 'Do not invent author from arrangement credit.' );
preview_expect( in_array('Author needs review.', $r['warnings'], true), 'Missing author is explicit.' );
preview_expect( count($r['unmapped']) === 1, 'Uncatalogued audio cannot silently disappear.' );
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:html --><div>Unusual custom content</div><!-- /wp:html --><!-- wp:video --><figure class="wp-block-video"><video src="https://example.org/performance.mp4"></video><figcaption>Performance caption</figcaption></figure><!-- /wp:video -->') );
preview_expect( count($r['unmapped']) === 2, 'Custom HTML and uploaded video are preserved as exceptions.' );
preview_expect( 'Performance caption' === $r['unmapped'][1]['text'], 'Keep video caption.' );
preview_expect( 'https://example.org/performance.mp4' === $r['unmapped'][1]['url'], 'Keep video URL.' );
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:paragraph --><p>Based on &quot;Example&quot; by Someone.</p><!-- /wp:paragraph -->') );
preview_expect( '' === $r['proposed']['author'] && false !== strpos($r['proposed']['notes'], 'Based on'), 'Based-on attribution is notes, not guessed authorship.' );
// Standard list blocks contain nested list-item blocks in current WordPress.
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:heading --><h2>Practice Recordings</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Harmony: Bass/Alto</li><!-- /wp:list-item --></ul><!-- /wp:list -->') );
preview_expect( false !== strpos($r['proposed']['notes'], 'Harmony: Bass/Alto'), 'Preserve text inside nested list items.' );
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:verse --><pre class="wp-block-verse">First line<br>Second line</pre><!-- /wp:verse -->') );
preview_expect( "First line\nSecond line" === $r['proposed']['lyrics'] && !$r['unmapped'], 'Map Verse blocks as lyrics.' );
$r = BVOCAL_Song_Preview::preview( preview_fixture('<!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:preformatted --><pre class="wp-block-preformatted">First line<br>Second line</pre><!-- /wp:preformatted -->') );
preview_expect( "First line\nSecond line" === $r['proposed']['lyrics'] && !$r['unmapped'], 'Map Preformatted blocks as lyrics.' );
$before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}") . ':' . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}");
$hold = get_page_by_path( 'hold-on', OBJECT, 'song' );
if ($hold) {
 $r = BVOCAL_Song_Preview::preview($hold);
 preview_expect( 1 === count($r['proposed']['recordings']), 'Resolve Hold On audio without a block attachment ID.' );
 preview_expect( 0 === strpos($r['proposed']['recordings'][0]['url'], 'https://drive.google.com/file/d/'), 'Use portable Drive link.' );
 preview_expect( count($r['unmapped']) === 1 && $r['unmapped'][0]['type'] === 'link', 'Hold On only leaves its performance link unmapped.' );
}
$after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}") . ':' . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}");
preview_expect( $before === $after, 'Preview does not insert posts or metadata.' );
echo "PASS: song preview mapping, exceptions, media resolution and read-only execution\n";
