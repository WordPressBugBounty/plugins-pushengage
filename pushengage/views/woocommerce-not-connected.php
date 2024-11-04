<div class="notice notice-info is-dismissible">
	<p style="font-weight:700">
		<?php esc_html_e( 'PushEngage: You haven’t finished setting up your WooCommerce Integration.', 'pushengage' ); ?>
	</p>
	<p>
		<?php
		esc_html_e(
			'Integrate WooCommerce with PushEngage to recover abandoned carts, send price drop alerts, and more!',
			'pushengage'
		);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( 'admin.php?page=pushengage#/settings/integrations' ); ?>" class="button-primary">
			<?php esc_html_e( 'Connect your WooCommerce integration now!', 'pushengage' ); ?>
		</a>
	</p>
</div>
