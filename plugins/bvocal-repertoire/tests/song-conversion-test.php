<?php
/** Run with wp eval-file; all temporary source and target records are removed. */
function conversion_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException($message); } }
conversion_expect( BVOCAL_Song_Conversion::shared_lyrics_allowed('hostile-government-take-over','first-amendment-2'), 'Approved pair may share lyrics.' );
conversion_expect( BVOCAL_Song_Conversion::shared_lyrics_allowed('first-amendment-2','hostile-government-take-over'), 'Exception works in either conversion order.' );
conversion_expect( !BVOCAL_Song_Conversion::shared_lyrics_allowed('another-song','first-amendment-2'), 'Other pairs still require review.' );
conversion_expect( !BVOCAL_Song_Conversion::shared_lyrics_allowed('hostile-government-take-over',''), 'Missing source identity is not exempt.' );
$original_user = get_current_user_id();
$ids = array();
try {
 $admins = get_users(array('role'=>'administrator','fields'=>'ID','number'=>1));
 wp_set_current_user((int)$admins[0]);
 $unique = wp_generate_uuid4();
 $body = '<!-- wp:paragraph --><p>By Test Author</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Temporary unique lyric '.$unique.'</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Links</h2><!-- /wp:heading --><!-- wp:paragraph --><p><a href="https://example.org/performance">Performance</a></p><!-- /wp:paragraph -->';
 $source = wp_insert_post(array('post_type'=>'song','post_status'=>'publish','post_title'=>'Conversion test '.$unique,'post_content'=>$body)); $ids[]=$source;
 $original = get_post($source)->to_array();
 $dry = BVOCAL_Song_Conversion::convert_song($source, false);
 conversion_expect('would_create' === $dry['status'] && !$dry['repertoire_ids'], 'Default is a read-only plan.');
 $result = BVOCAL_Song_Conversion::convert_song($source, true, 'test-run');
 conversion_expect('created' === $result['status'], 'Create a convertible source.');
 $target = $result['repertoire_ids'][0]; $ids[]=$target;
 conversion_expect('draft'===get_post_status($target), 'Never auto-publish.');
 conversion_expect('Test Author'===get_post_meta($target,'_bvocal_repertoire_author',true), 'Save structured credits.');
 conversion_expect(1===count(get_post_meta($target,'_bvocal_repertoire_links',true)), 'Save mapped Links.');
 conversion_expect((string)$source===(string)get_post_meta($target,'_bvocal_repertoire_source_song_id',true), 'Track source identity.');
 conversion_expect('test-run'===get_post_meta($target,'_bvocal_repertoire_conversion_run',true), 'Record batch for rollback.');
 conversion_expect($original===get_post($source)->to_array(), 'Leave original source unchanged.');
 wp_update_post(array('ID'=>$target,'post_title'=>'Manually edited draft'));
 update_post_meta($target,'_bvocal_repertoire_notes','Human edit');
 $again = BVOCAL_Song_Conversion::convert_song($source,true,'second-run');
 conversion_expect('skipped_existing'===$again['status'] && array($target)===$again['repertoire_ids'], 'Rerun finds the same draft.');
 conversion_expect('Human edit'===get_post_meta($target,'_bvocal_repertoire_notes',true), 'Rerun preserves manual edits.');
 wp_update_post(array('ID'=>$source,'post_title'=>'Changed source '.$unique));
 $changed = BVOCAL_Song_Conversion::convert_song($source,true,'third-run');
 conversion_expect('skipped_source_changed'===$changed['status'], 'Flag changed source without overwriting.');
 wp_trash_post($target);
 conversion_expect('skipped_trashed'===BVOCAL_Song_Conversion::convert_song($source,true)['status'], 'Do not recreate a trashed conversion.');
 $other = wp_insert_post(array('post_type'=>'song','post_status'=>'publish','post_title'=>'Unmapped test '.$unique,'post_content'=>'<!-- wp:html --><div>Unmappable '.$unique.'</div><!-- /wp:html -->')); $ids[]=$other;
 conversion_expect('blocked_unmapped'===BVOCAL_Song_Conversion::convert_song($other,true)['status'], 'Unmapped source stays unconverted.');
 wp_update_post(array('ID'=>$other,'post_status'=>'private'));
 conversion_expect('blocked_source'===BVOCAL_Song_Conversion::convert_song($other,true)['status'], 'Private records are not converted.');
 wp_update_post(array('ID'=>$other,'post_status'=>'publish','post_content'=>$body,'post_password'=>'test-only'));
 conversion_expect('blocked_protected'===BVOCAL_Song_Conversion::convert_song($other,true)['status'], 'Never expose protected source content.');
 wp_update_post(array('ID'=>$other,'post_password'=>''));
 $manual = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>'Different name '.$unique));$ids[]=$manual;
 $preview = BVOCAL_Song_Preview::preview(get_post($other));
 update_post_meta($manual,'_bvocal_repertoire_lyrics',$preview['proposed']['lyrics']);
 conversion_expect('blocked_conflict'===BVOCAL_Song_Conversion::convert_song($other,true)['status'], 'Detect untracked duplicate by lyrics even when titles differ.');
 wp_set_current_user(0);
 conversion_expect('blocked_permission'===BVOCAL_Song_Conversion::convert_song($other,true)['status'], 'Writes require administrator context.');
} finally {
 wp_set_current_user((int)$admins[0]);
 foreach(array_reverse($ids) as $id){wp_delete_post($id,true);}
 wp_set_current_user($original_user);
}
echo "PASS: draft conversion, provenance, reruns, conflicts, visibility and source preservation\n";
