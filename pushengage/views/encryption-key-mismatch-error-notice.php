<div class="notice notice-error is-dismissible">
<p style="font-weight:700">
		<?php esc_html_e( 'Your WhatsApp Business Account Access Token has become invalid.', 'pushengage' ); ?>
	</p>
	<p>
		<?php
		esc_html_e( 'Please update the access token in PushEngage settings to continue sending WhatsApp notifications.', 'pushengage' );
		?>
	</p>
	<p>
		<a
			class="button-primary"
			href="<?php echo esc_url( 'admin.php?page=pushengage#/whatsapp/settings?tab=cloud-api' ); ?>"
		>
		<?php esc_html_e( 'Update Now', 'pushengage' ); ?>
		</a>
	</p>
</div>
