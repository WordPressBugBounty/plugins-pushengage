<div class="notice pe-notice notice-info is-dismissible pe-review-notice">
	<p>
		<?php
		echo sprintf(
			// Translators: 1 - The plugin name ("PushEngage").
			esc_html__( 'Hey, I noticed you have been using %1$s for some time - that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'pushengage' ),
			'<strong>PushEngage</strong>'
		);
		?>
	</p>
	<p>
		<a href="https://wordpress.org/support/plugin/pushengage/reviews/?filter=5#new-post" class="button-primary pe-notice-dismiss pe-review-out" id="pe-review-in-org-link" target="_blank" rel="noopener noreferrer" style="margin-right:8px">
			<?php esc_html_e( 'Ok, you deserve it', 'pushengage' ); ?>
		</a>
		<a href="#" class="pe-notice-dismiss" id="pe-display-notice-later-link" target="_blank" rel="noopener noreferrer" style="margin-right:8px">
			<?php esc_html_e( 'Nope, maybe later', 'pushengage' ); ?>
		</a>
		<a href="#" class="pe-notice-dismiss" id="pe-already-reviewed-link" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'I already did', 'pushengage' ); ?>
		</a>
	</p>
	<button type="button" class="notice-dismiss" id="pe-review-dismiss-btn">
		<span class="screen-reader-text">
			<?php esc_html_e( 'Dismiss this notice.', 'pushengage' ); ?>
		</span>
	</button>
</div>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		function sendAjaxRequest(actionType, callback) {
			const data = {
				action: 'pe_dismiss_review_notice',
				clicked_review_action: actionType,
				_wpnonce: '<?php echo esc_html( wp_create_nonce( 'pushengage-nonce' ) ); ?>'
			};
			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data,
				success: function(data) {
					callback();
				}
			});
		}

		$('#pe-review-in-org-link').on('click', function(event) {
			event.preventDefault();
			sendAjaxRequest(
				'dismissed',
				function() {
					const href = $(event.target).attr('href');
					window.open(href, '_blank');
					$('.pe-review-notice').remove();
				}
			);
		});

		$('#pe-display-notice-later-link, #pe-review-dismiss-btn').on('click', function(event) {
			event.preventDefault();
			sendAjaxRequest(
				'later',
				function() {
					$('.pe-review-notice').remove();
				}
			);
		});

		$('#pe-already-reviewed-link').on('click', function(event) {
			event.preventDefault();
			sendAjaxRequest(
				'dismissed',
				function() {
					$('.pe-review-notice').remove();
				}
			);
		});
	});
</script>
