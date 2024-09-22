<?php
/**
 * Render the root element for displaying the post editor metabox.
 */

// Generate custom nonce field for post editor
wp_nonce_field( 'pushengage_post_editor_nonce_action', 'pushengage_post_editor_nonce' );
?>
<div id='pe-root' class='pushengage-app' data-app='postEditorMetabox'></div>
