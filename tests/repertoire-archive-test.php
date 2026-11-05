<?php
/** Run inside WordPress with wp eval-file; creates no content. */
function repertoire_archive_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } }
global $wp_the_query;
$original_main = $wp_the_query;
try {
 $expected = get_posts(array('post_type'=>'repertoire_item','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>array('title'=>'ASC','ID'=>'ASC'),'fields'=>'ids'));
 $all = array();
 $pages = max(1, (int)ceil(count($expected)/10));
 for($page=1;$page<=$pages;$page++) {
  $query = new WP_Query(); $wp_the_query = $query;
  $query->query(array('post_type'=>'repertoire_item','posts_per_page'=>10,'paged'=>$page));
  $actual = wp_list_pluck($query->posts,'ID');
  repertoire_archive_expect( $actual === array_slice($expected,($page-1)*10,10), 'Archive pages must be alphabetical and stable.' );
  repertoire_archive_expect( (int)$query->max_num_pages === $pages, 'Archive reports all pages.' );
  $all = array_merge($all,$actual);
 }
 repertoire_archive_expect( $all === $expected && count($all) === count(array_unique($all)), 'Every published item is reachable exactly once.' );
 $secondary = new WP_Query(array('post_type'=>'repertoire_item','posts_per_page'=>1,'orderby'=>'date','order'=>'DESC'));
 repertoire_archive_expect( 'date' === $secondary->get('orderby') && 'DESC' === $secondary->get('order'), 'Secondary queries retain their ordering.' );
 $blog = new WP_Query(); $wp_the_query = $blog;
 $blog->query(array('post_type'=>'post','posts_per_page'=>1,'orderby'=>'date','order'=>'DESC'));
 repertoire_archive_expect( 'date' === $blog->get('orderby'), 'Blog queries retain their ordering.' );
} finally { $wp_the_query = $original_main; }
echo 'PASS: '.count($expected).' published Repertoire items reachable in alphabetical pages; other queries unchanged'."\n";
