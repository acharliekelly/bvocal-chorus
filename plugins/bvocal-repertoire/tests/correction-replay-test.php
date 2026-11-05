<?php
/** Run with wp eval-file; temporary source and target records are removed. */
function replay_expect($ok,$message){if(!$ok){throw new RuntimeException($message);}}
$admins=get_users(['role'=>'administrator','fields'=>'ID','number'=>1]);$old_user=get_current_user_id();$ids=[];
try{
 wp_set_current_user((int)$admins[0]);
 $uuid=wp_generate_uuid4();
 $source=wp_insert_post(['post_type'=>'song','post_status'=>'publish','post_title'=>'Replay fixture '.$uuid,'post_content'=>'<!-- wp:paragraph --><p>By Fixture Author</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Lyrics</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Fixture lyric '.$uuid.'</p><!-- /wp:paragraph -->']);$ids[]=$source;
 $created=BVOCAL_Song_Conversion::convert_song($source,true,'replay-test');$target=$created['repertoire_ids'][0];$ids[]=$target;
 $baseline=BVOCAL_Correction_Replay::preview_fields(get_post($source));$reviewed=$baseline;$reviewed['notes']='Reviewed note';$reviewed['title']='Reviewed fixture '.$uuid;$reviewed['slug']='reviewed-fixture-'.$uuid;
 $entry=['source_song_slug'=>get_post($source)->post_name,'pairing'=>'conversion tracking','baseline_fields'=>$baseline,'reviewed_fields'=>$reviewed];
 $reordered=$entry;$reordered['baseline_fields']=array_reverse($baseline,true);$reordered['reviewed_fields']=array_reverse($reviewed,true);
 replay_expect('would_update'===BVOCAL_Correction_Replay::plan($reordered)['status'],'JSON key order does not cause false drift.');
 $before=get_post_meta($target);
 $plan=BVOCAL_Correction_Replay::plan($entry);
 replay_expect('would_update'===$plan['status']&&3===count($plan['changes']),'Plan only the three approved changes.');
 replay_expect($before===get_post_meta($target),'Planning is read-only.');
 $original=get_post($source)->to_array();
 BVOCAL_Correction_Replay::apply_row($plan);
 replay_expect('draft'===get_post_status($target),'Correction never publishes.');
 replay_expect('Reviewed note'===get_post_meta($target,'_bvocal_repertoire_notes',true),'Save approved field.');
 replay_expect($reviewed['title']===get_post($target)->post_title && $reviewed['slug']===get_post($target)->post_name,'Save title and slug.');
 replay_expect($original===get_post($source)->to_array(),'Source is untouched.');
 replay_expect('already_correct'===BVOCAL_Correction_Replay::plan($entry)['status'],'Rerun is idempotent.');
 update_post_meta($target,'_bvocal_repertoire_notes','Unexpected newer edit');
 replay_expect('blocked_target_drift'===BVOCAL_Correction_Replay::plan($entry)['status'],'Preserve unexpected target edits.');
 update_post_meta($target,'_bvocal_repertoire_notes','Reviewed note');
 wp_update_post(['ID'=>$source,'post_content'=>get_post($source)->post_content.'<!-- wp:paragraph --><p>New source information</p><!-- /wp:paragraph -->']);
 replay_expect('blocked_source_drift'===BVOCAL_Correction_Replay::plan($entry)['status'],'Detect newer source content.');
 wp_update_post(['ID'=>$source,'post_content'=>$original['post_content']]);
 update_post_meta($target,'_bvocal_repertoire_notes','');wp_update_post(['ID'=>$target,'post_status'=>'publish']);
 replay_expect('blocked_published'===BVOCAL_Correction_Replay::plan($entry)['status'],'Never edit a published mismatching item.');
 $bad=$entry;$bad['reviewed_fields']['post_status']='publish';
 replay_expect(is_wp_error(BVOCAL_Correction_Replay::validate_entry($bad)),'Unknown fields cannot enter replay.');
 replay_expect(['drive_file_id'=>'example']===BVOCAL_Correction_Replay::url_reference('https://drive.google.com/file/d/example/preview?usp=sharing'),'Drive URL variants canonicalize.');
 replay_expect(['site_path'=>'/wp-content/uploads/example.mp4']===BVOCAL_Correction_Replay::url_reference(home_url('/wp-content/uploads/example.mp4')),'Local uploaded media uses destination-relative paths.');
 // Exercise the structured recording update with a real catalogued audio reference.
 $audio_body='<!-- wp:paragraph --><p>By Fixture Author</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Practice Recordings</h2><!-- /wp:heading --><!-- wp:audio --><figure class="wp-block-audio"><audio src="https://drive.google.com/file/d/1wIv3K9pQChKIby9t6eQNpTrezNfaqREA/view"></audio></figure><!-- /wp:audio -->';
 $audio_source=wp_insert_post(['post_type'=>'song','post_status'=>'publish','post_title'=>'Audio replay fixture '.$uuid,'post_content'=>$audio_body]);$ids[]=$audio_source;
 $audio_preview=BVOCAL_Song_Preview::preview(get_post($audio_source));$meta=[BVOCAL_Song_Conversion::SOURCE_META=>$audio_source];
 foreach(['author','arranger','lyricist','language','notes','lyrics','score_url','recordings','links']as$key){$meta[BVOCAL_Repertoire::META_PREFIX.$key]=$audio_preview['proposed'][$key];}
 $audio_target=wp_insert_post(wp_slash(['post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>get_post($audio_source)->post_title,'post_name'=>get_post($audio_source)->post_name,'meta_input'=>$meta]));$ids[]=$audio_target;
 $b=BVOCAL_Correction_Replay::preview_fields(get_post($audio_source));$v=$b;$v['recordings'][0]['label']='All parts';
 $audio_entry=['source_song_slug'=>get_post($audio_source)->post_name,'baseline_fields'=>$b,'reviewed_fields'=>$v];
 $audio_plan=BVOCAL_Correction_Replay::plan($audio_entry);
 replay_expect('would_update'===$audio_plan['status'],'Catalogued recording label can be corrected.');
 $unknown=$audio_entry;$unknown['reviewed_fields']['recordings'][0]['url']=['drive_file_id'=>'uncatalogued-fixture'];
 replay_expect('blocked_media'===BVOCAL_Correction_Replay::plan($unknown)['status'],'Unknown correction media is held before writes.');
 BVOCAL_Correction_Replay::apply_row($audio_plan);
 replay_expect('All parts'===get_post_meta($audio_target,'_bvocal_repertoire_recordings',true)[0]['label'],'Structured recording label saved.');
 wp_set_current_user(0);
 $denied=false;try{BVOCAL_Correction_Replay::apply_row($audio_plan);}catch(RuntimeException $e){$denied=true;}
 replay_expect($denied,'Apply cannot bypass administrator permission.');
}finally{wp_set_current_user((int)$admins[0]);foreach(array_reverse($ids)as$id){wp_delete_post($id,true);}wp_set_current_user($old_user);}
echo "PASS: correction planning, draft updates, reruns, drift, publication guard and schema\n";
