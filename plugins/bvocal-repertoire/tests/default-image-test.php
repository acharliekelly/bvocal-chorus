<?php
/** Run with wp eval-file; temporary posts and the previous setting are restored. */
function default_image_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException($message); } }
default_image_expect(post_type_supports('repertoire_item','thumbnail'), 'Repertoire exposes Featured Image.');
$images = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image','posts_per_page'=>20,'fields'=>'ids'));
$images = array_values(array_filter($images, function($id){return (bool)wp_get_attachment_image($id,'thumbnail');}));
default_image_expect(count($images)>=2, 'Two existing image fixtures are available.');
$old = get_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION, false);
$ids = array();
try {
 update_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION, $images[0]);
 default_image_expect($images[0]===BVOCAL_Repertoire::sanitize_default_image($images[0]), 'Valid Media Library image is accepted.');
 default_image_expect($images[0]===BVOCAL_Repertoire::sanitize_default_image(PHP_INT_MAX), 'Invalid selection preserves the prior default.');
 default_image_expect($images[0]===BVOCAL_Repertoire::sanitize_default_image(array('bad')), 'Malformed selection preserves the prior default.');
 default_image_expect(0===BVOCAL_Repertoire::sanitize_default_image('0'), 'Default can be disabled.');
 $post = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>'Temporary default image test')); $ids[]=$post;
 default_image_expect($images[0]===get_post_thumbnail_id($post), 'New draft receives the configured default automatically.');
 $custom = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>'Temporary custom image test','meta_input'=>array('_thumbnail_id'=>$images[1]))); $ids[]=$custom;
 default_image_expect($images[1]===get_post_thumbnail_id($custom), 'Custom image supplied at creation is preserved.');
 update_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION, $images[1]);
 wp_update_post(array('ID'=>$post,'post_title'=>'Temporary saved image test'));
 default_image_expect($images[0]===get_post_thumbnail_id($post), 'Changing the setting never replaces existing images.');
 delete_post_thumbnail($custom);
 wp_update_post(array('ID'=>$custom,'post_title'=>'Temporary removed image test'));
 default_image_expect($images[1]===get_post_thumbnail_id($custom), 'Saving after removing an image restores the default.');
 $page = wp_insert_post(array('post_type'=>'page','post_status'=>'draft','post_title'=>'Temporary non-Repertoire test')); $ids[]=$page;
 default_image_expect(0===get_post_thumbnail_id($page), 'Other post types are unaffected.');
 $auto = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'auto-draft','post_title'=>'Temporary auto-draft test'));$ids[]=$auto;
 default_image_expect(0===get_post_thumbnail_id($auto), 'Auto-drafts are not changed prematurely.');
 delete_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION);
 $empty = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>'Temporary no-default test'));$ids[]=$empty;
 default_image_expect(0===get_post_thumbnail_id($empty), 'No image is assigned when default is disabled.');
} finally {
 foreach($ids as $id){wp_delete_post($id,true);}
 if(false===$old){delete_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION);}else{update_option(BVOCAL_Repertoire::DEFAULT_IMAGE_OPTION,$old);}
}
echo "PASS: featured-image support, default assignment, custom preservation, removal and disabled settings\n";
