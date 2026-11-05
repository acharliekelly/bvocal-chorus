<?php
/** Run with wp eval-file. Temporary draft is removed in finally. */
function repertoire_links_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } }
$links = array(
 array( 'label' => 'Hold On performance', 'url' => 'https://www.youtube.com/shorts/llZaPGzJ1J8' ),
 array( 'label' => 'Uploaded performance', 'url' => 'http://localhost:8091/wp-content/uploads/performance.mp4' ),
);
$html = BVOCAL_Repertoire::links_markup( $links );
repertoire_links_expect( 2 === substr_count($html, 'target="_blank"'), 'Render every link in a new tab.' );
repertoire_links_expect( 2 === substr_count($html, 'rel="noopener noreferrer"'), 'New tabs have appropriate rel attributes.' );
repertoire_links_expect( false === strpos($html, '<iframe') && false === strpos($html, '<video'), 'Links do not embed players.' );
repertoire_links_expect( '' === BVOCAL_Repertoire::links_markup(array()), 'No empty Links section.' );
repertoire_links_expect( is_wp_error(BVOCAL_Repertoire::normalize_link(array('label'=>'Unsafe','url'=>'javascript:alert(1)'))), 'Reject executable URL schemes.' );
repertoire_links_expect( is_wp_error(BVOCAL_Repertoire::normalize_link(array('url'=>'mailto:test@example.org'))), 'Only web URLs are accepted.' );
repertoire_links_expect( is_wp_error(BVOCAL_Repertoire::normalize_link(array('url'=>array('bad')))), 'Reject malformed URL values without warnings.' );
$html = BVOCAL_Repertoire::links_markup(array(array('label'=>'<script>Bad</script> & text','url'=>'https://example.org/')));
repertoire_links_expect( false === strpos($html, '<script>') && false !== strpos($html, '&amp;'), 'Escape link labels.' );
$original_user = get_current_user_id();
$original_post = $_POST;
$id = 0;
try {
 $admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
 repertoire_links_expect( !empty($admins), 'Administrator fixture available.' );
 wp_set_current_user((int)$admins[0]);
 $id = wp_insert_post(array('post_type'=>'repertoire_item','post_status'=>'draft','post_title'=>'Temporary Links test'), true);
 repertoire_links_expect( !is_wp_error($id), 'Create temporary test draft.' );
 $_POST = array('bvocal_repertoire_nonce'=>wp_create_nonce('bvocal_save_repertoire'),'bvocal_repertoire'=>array('author'=>'Test Author','link_label'=>array_column($links,'label'),'link_url'=>array_column($links,'url')));
 BVOCAL_Repertoire::save_meta_boxes($id);
 repertoire_links_expect( $links === get_post_meta($id, '_bvocal_repertoire_links', true), 'Save multiple label/URL entries in order.' );
 ob_start(); BVOCAL_Repertoire::render_materials_box(get_post($id)); $editor = ob_get_clean();
 repertoire_links_expect( false !== strpos($editor, 'bvocal-link-template') && false !== strpos($editor, 'Add link'), 'Editor provides an add-row template.' );
 repertoire_links_expect( false !== strpos($editor, 'Hold On performance') && false !== strpos($editor, 'bvocal_repertoire[link_url][]'), 'Editor restores saved link rows.' );
 $_POST['bvocal_repertoire']['link_url'][0] = 'javascript:alert(1)';
 BVOCAL_Repertoire::save_meta_boxes($id);
 repertoire_links_expect( $links === get_post_meta($id, '_bvocal_repertoire_links', true), 'Invalid links do not replace the saved list.' );
 $_POST['bvocal_repertoire']['link_label'] = array(); $_POST['bvocal_repertoire']['link_url'] = array();
 BVOCAL_Repertoire::save_meta_boxes($id);
 repertoire_links_expect( array() === get_post_meta($id, '_bvocal_repertoire_links', true), 'Removing all rows clears links.' );
} finally {
 $_POST = $original_post;
 if ( $id && !is_wp_error($id) ) { wp_delete_post($id, true); }
 wp_set_current_user($original_user);
}
echo "PASS: multiple Links save, restore, removal, validation and new-tab markup\n";
