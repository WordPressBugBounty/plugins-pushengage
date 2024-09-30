<div class="notice notice-error is-dismissible">
	<p>
		<?php
		echo sprintf(
			/* translators: %s: Service Worker URL */
			esc_html__(
				'We could not access the service worker file at location %s.',
				'pushengage'
			),
			'<a target="_blank" href="' . esc_url( $data['sw_url'] ) . '">' . esc_url( $data['sw_url'] ) . '</a>'
		);
		?>
	</p>
	<p>
		<a
			style="color: inherit;text-decoration: none;"
			href="<?php echo esc_url( 'admin.php?page=pushengage#/' ); ?>"
		>
			<strong>PushEngage  - <?php esc_html_e( 'Fix Now', 'pushengage' ); ?></strong>
		</a>
	</p>

</div>
