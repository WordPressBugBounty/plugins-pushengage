<?php
/**
 * Render the content for displaying the site not connected metabox.
 */

$current_post_type_name = get_post_type_object( get_post_type() )->labels->singular_name;
?>
<!-- // Add Message and CTA section -->
<div style="padding: 0 24px" class="pe-site-not-connected-metabox">
	<p style="font-weight: 700; font-size: 18px;">
		<?php
			echo sprintf(
				/* translators: %s: Post type singular name */
				__( 'Share this %s with your subscribers', 'pushengage' ),
				$current_post_type_name
			);
			?>
	</p>
	<p>
		<?php
			echo sprintf(
				/* translators: %s: Post type singular name */
				__(
					'Enable auto push settings to send notifications to your subscribers when you publish a new %s.',
					'pushengage'
				),
				$current_post_type_name
			);
			?>
	</p>
	<p>
		<a href="<?php echo esc_url( 'admin.php?page=pushengage#/onboarding' ); ?>" class="button-primary">
			<?php esc_html_e( 'Connect your site now!', 'pushengage' ); ?>
		</a>
	</p>
</div>
<!-- // End Message and CTA section -->
