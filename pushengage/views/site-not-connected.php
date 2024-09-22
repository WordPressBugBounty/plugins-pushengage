<div class="notice notice-info is-dismissible">
	<p style="font-weight:700">
		<?php esc_html_e( 'You haven’t finished setting up your site.', 'pushengage' ); ?>
	</p>
	<p>
		<?php
		esc_html_e(
			'You are losing subscribers, leads and sales! Click on the button below to get started with PushEngage.',
			'pushengage'
		);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( 'admin.php?page=pushengage#/onboarding' ); ?>" class="button-secondary">
			<?php esc_html_e( 'Connect your site now!', 'pushengage' ); ?>
		</a>
	</p>
</div>
