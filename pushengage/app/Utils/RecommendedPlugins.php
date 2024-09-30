<?php
namespace Pushengage\Utils;

use Pushengage\Utils\PluginUpgraderSkin;
use Pushengage\Utils\PluginUpgraderSilent;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RecommendedPlugins {

	/**
	 * Optin Monster plugin base URL
	 *
	 * @since 4.0.8
	 */
	const OPTIN_MONSTER_URL = 'optinmonster/optin-monster-wp-api.php';

	/**
	 * EDD plugin base URL
	 *
	 * @since 4.0.0
	 */
	const EDD_URL = 'easy-digital-downloads/easy-digital-downloads.php';
	const EDD_PRO_URL = 'easy-digital-downloads-pro/easy-digital-downloads.php';

	/**
	 * SEO Pack Plugin Base URL
	 *
	 * @since 4.0.0
	 */
	const SEO_PACK_URL = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
	const SEO_PACK_PRO_URL = 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php';

	/**
	 * Seed Prod Plugin Base URL
	 *
	 * @since 4.0.8
	 */
	const SEED_PROD_URL = 'coming-soon/coming-soon.php';
	const SEED_PROD_PRO_URL = 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php';

	/**
	 * WP Mail SMTP Plugin Base URL
	 *
	 * @since 4.0.8
	 */
	const WP_MAIL_SMTP_URL = 'wp-mail-smtp/wp_mail_smtp.php';
	const WP_MAIL_SMTP_PRO_URL = 'wp-mail-smtp-pro/wp_mail_smtp.php';

	/**
	 * RafflePress Plugin Base URL
	 *
	 * @since 4.0.8
	 */
	const RAFFLE_PRESS_URL = 'rafflepress/rafflepress.php';
	const RAFFLE_PRESS_PRO_URL = 'rafflepress-pro/rafflepress-pro.php';

	/**
	 * Monster Insight Plugin Base URL
	 *
	 * @since 4.0.8
	 */
	const MONSTER_INSIGHTS_URL = 'google-analytics-for-wordpress/googleanalytics.php';
	const MONSTER_INSIGHTS_PRO_URL = 'google-analytics-premium/googleanalytics-premium.php';

	/**
	 * WPForms Plugin Base URL
	 *
	 * @since 4.0.8
	 */
	const WP_FORMS_URL = 'wpforms-lite/wpforms.php';
	const WP_FORMS_PRO_URL = 'wpforms/wpforms.php';

	/**
	 * WP Code plugin base URL
	 *
	 * @since 4.0.8
	 */
	const WP_CODE_URL = 'insert-headers-and-footers/ihaf.php';
	const WP_CODE_PRO_URL = 'wpcode-premium/wpcode.php';

	/**
	 * WP simple pay plugin base URL
	 *
	 * @since 4.0.8
	 */
	const WP_SIMPLE_PAY_URL = 'stripe/stripe-checkout.php';
	const WP_SIMPLE_PAY_PRO_URL = 'wp-simple-pay-pro-3/simple-pay.php';

	/**
	 * Smash ballon instagram feed plugin base URL
	 *
	 * @since 4.0.8
	 */
	const SMASH_BALLOON_INSTAGRAM_FEEDS_URL = 'instagram-feed/instagram-feed.php';
	const SMASH_BALLOON_INSTAGRAM_FEEDS_PRO_URL = 'instagram-feed-pro/instagram-feed.php';

	/**
	 * Smash ballon facebook feed plugin base URL
	 *
	 * @since 4.0.8
	 */
	const SMASH_BALLOON_FACEBOOK_FEEDS_URL = 'custom-facebook-feed/custom-facebook-feed.php';
	const SMASH_BALLOON_FACEBOOK_FEEDS_PRO_URL = 'custom-facebook-feed-pro/custom-facebook-feed.php';

	/**
	 * Smash twitter feed plugin base URL
	 *
	 * @since 4.0.8
	 */
	const SMASH_BALLON_TWITTER_FEEDS_URL = 'custom-twitter-feeds/custom-twitter-feed.php';
	const SMASH_BALLON_TWITTER_FEEDS_PRO_URL = 'custom-twitter-feeds-pro/custom-twitter-feed.php';

	/**
	 * Smash youtube feed plugin base URL
	 *
	 * @since 4.0.8
	 */
	const SMASH_BALLOON_YOUTUBE_FEEDS_URL = 'feeds-for-youtube/youtube-feed.php';
	const SMASH_BALLOON_YOUTUBE_FEEDS_PRO_URL = 'youtube-feed-pro/youtube-feed.php';

	/**
	 * FunnelKit Automation Plugin Base URL
	 *
	 * @since 4.0.10
	 */
	const FUNNELKIT_AUTOMATION_URL = 'wp-marketing-automations/wp-marketing-automations.php';
	const FUNNELKIT_AUTOMATION_PRO_URL = 'wp-marketing-automations-pro/wp-marketing-automations-pro.php';

	/**
	 * WP Charitable Plugin Base URL.
	 *
	 * @since 4.0.10
	 */
	const WP_CHARITABLE_URL = 'charitable/charitable.php';

	/**
	 * Duplicator Plugin Base URL.
	 *
	 * @since 4.0.10
	 */
	const DUPLICATOR_URL = 'duplicator/duplicator.php';
	const DUPLICATOR_PRO_URL = 'duplicator-pro/duplicator-pro.php';

	/**
	 * An array of links to install the plugins from.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	public static $plugin_links = array(
		'optinmonster'                  => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
		'wpforms'                       => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
		'wp-mail-smtp'                  => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
		'seedprod'                      => 'https://downloads.wordpress.org/plugin/coming-soon.zip',
		'rafflepress'                   => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
		'monsterinsights'               => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
		'aioseo'                        => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
		'affiliateWp'                   => 'https://downloads.wordpress.org/plugin/affiliatewp-external-referral-links.zip',
		'edd'                           => 'https://downloads.wordpress.org/plugin/easy-digital-downloads.zip',
		'wpcode'                        => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
		'wp-simple-pay'                 => 'https://downloads.wordpress.org/plugin/stripe.zip',
		'smash-balloon-instagram-feeds' => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
		'smash-balloon-facebook-feeds'  => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
		'smash-balloon-twitter-feeds'   => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
		'smash-balloon-youtube-feeds'   => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
		'wp-marketing-automations'      => 'https://downloads.wordpress.org/plugin/wp-marketing-automations.zip',
		'charitable'                    => 'https://downloads.wordpress.org/plugin/charitable.zip',
		'duplicator'                    => 'https://downloads.wordpress.org/plugin/duplicator.zip',
	);

	/**
	 * Get list of addons
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public static function get_addons() {
		$parsed_addons     = array();
		$installed_plugins = get_plugins();

		// OptinMonster.
		$parsed_addons['optinmonster'] = array(
			'active'    => is_plugin_active( self::OPTIN_MONSTER_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-om.png',
			'title'     => __( 'OptinMonster', 'pushengage' ),
			'excerpt'   => __( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'pushengage' ),
			'installed' => array_key_exists( self::OPTIN_MONSTER_URL, $installed_plugins ),
			'basename'  => self::OPTIN_MONSTER_URL,
			'slug'      => 'optinmonster',
			'settings'  => admin_url( 'admin.php?page=optin-monster-dashboard' ),
		);

		/**
		 * FunnelKit Automation Plugin
		 *
		 * @since 4.0.10
		 */
		$is_funnelkit_automation_pro_installed = false;
		if ( array_key_exists( self::FUNNELKIT_AUTOMATION_PRO_URL, $installed_plugins ) ) {
			$is_funnelkit_automation_pro_installed = true;
		}
		$parsed_addons['wp-marketing-automations'] = array(
			'active'    => $is_funnelkit_automation_pro_installed ? is_plugin_active( self::FUNNELKIT_AUTOMATION_PRO_URL ) : is_plugin_active( self::FUNNELKIT_AUTOMATION_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-funnelkit.svg',
			'title'     => $is_funnelkit_automation_pro_installed ? __( 'FunnelKit Automations Pro', 'pushengage' ) : __( 'FunnelKit Automations', 'pushengage' ),
			'excerpt'   => __(
				'Elevate your WooCommerce store with FunnelKit Automations.This versatile WordPress plugin enables automated customer journeys, from abandoned cart recovery to targeted email sequences.',
				'pushengage'
			),
			'installed' => $is_funnelkit_automation_pro_installed ? true : array_key_exists( self::FUNNELKIT_AUTOMATION_URL, $installed_plugins ),
			'basename'  => $is_funnelkit_automation_pro_installed ? self::FUNNELKIT_AUTOMATION_PRO_URL : self::FUNNELKIT_AUTOMATION_URL,
			'slug'      => 'wp-marketing-automations',
			'settings'  => admin_url( 'admin.php?page=autonami' ),
		);

		/**
		 * WP Code
		 *
		 * @since 4.0.8
		 */
		$is_wp_code_pro_installed = false;
		if ( array_key_exists( self::WP_CODE_PRO_URL, $installed_plugins ) ) {
			$is_wp_code_pro_installed = true;
		}
		$parsed_addons['wpcode'] = array(
			'active'    => $is_wp_code_pro_installed ? is_plugin_active( self::WP_CODE_PRO_URL ) : is_plugin_active( self::WP_CODE_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-wpcode.svg',
			'title'     => $is_wp_code_pro_installed ? __( 'WP Code Pro', 'pushengage' ) : __( 'WP Code', 'pushengage' ),
			'excerpt'   => __( 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.', 'pushengage' ),
			'installed' => $is_wp_code_pro_installed ? true : array_key_exists( self::WP_CODE_URL, $installed_plugins ),
			'basename'  => $is_wp_code_pro_installed ? self::WP_CODE_PRO_URL : self::WP_CODE_URL,
			'slug'      => 'wpcode',
			'settings'  => admin_url( 'admin.php?page=wpcode' ),
		);

		// MonsterInsight
		$is_monster_insights_pro_installed = false;
		if ( array_key_exists( self::MONSTER_INSIGHTS_PRO_URL, $installed_plugins ) ) {
			$is_monster_insights_pro_installed = true;
		}

		$parsed_addons['monsterinsights'] = array(
			'active'    => $is_monster_insights_pro_installed ? is_plugin_active( self::MONSTER_INSIGHTS_PRO_URL ) : is_plugin_active( self::MONSTER_INSIGHTS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-mi.png',
			'title'     => $is_monster_insights_pro_installed ? __( 'MonsterInsights Pro', 'pushengage' ) : __( 'MonsterInsights', 'pushengage' ),
			'excerpt'   => __( 'MonsterInsights makes it effortless to properly connect your WordPress site with Google Analytics, so you can start making data-driven decisions to grow your business.', 'pushengage' ),
			'installed' => $is_monster_insights_pro_installed ? true : array_key_exists( self::MONSTER_INSIGHTS_URL, $installed_plugins ),
			'basename'  => $is_monster_insights_pro_installed ? self::MONSTER_INSIGHTS_PRO_URL : self::MONSTER_INSIGHTS_URL,
			'slug'      => 'monsterinsights',
			'settings'  => admin_url( 'admin.php?page=monsterinsights_settings' ),
		);

		// WPForms.
		$is_wpforms_pro_installed = false;
		if ( array_key_exists( self::WP_FORMS_PRO_URL, $installed_plugins ) ) {
			$is_wpforms_pro_installed = true;
		}

		$parsed_addons['wpforms'] = array(
			'active'    => $is_wpforms_pro_installed ? is_plugin_active( self::WP_FORMS_PRO_URL ) : is_plugin_active( self::WP_FORMS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-wpforms.png',
			'title'     => $is_wpforms_pro_installed ? __( 'WPForms Pro', 'pushengage' ) : __( 'WPForms', 'pushengage' ),
			'excerpt'   => __( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 150+ form templates. Trusted by over 5 million websites as the best forms plugin.', 'pushengage' ),
			'installed' => $is_wpforms_pro_installed ? true : array_key_exists( self::WP_FORMS_URL, $installed_plugins ),
			'basename'  => $is_wpforms_pro_installed ? self::WP_FORMS_PRO_URL : self::WP_FORMS_URL,
			'slug'      => 'wpforms',
			'settings'  => admin_url( 'admin.php?page=wpforms-settings' ),
		);

		// AIOSEO.
		$is_aioseo_pro_installed = false;
		if ( array_key_exists( self::SEO_PACK_PRO_URL, $installed_plugins ) ) {
			$is_aioseo_pro_installed = true;
		}
		$parsed_addons['aioseo'] = array(
			'active'    => $is_aioseo_pro_installed ? is_plugin_active( self::SEO_PACK_PRO_URL ) : is_plugin_active( self::SEO_PACK_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-all-in-one-seo.png',
			'title'     => $is_aioseo_pro_installed ? __( 'AIOSEO Pro', 'pushengage' ) : __( 'AIOSEO', 'pushengage' ),
			'excerpt'   => __( 'The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'pushengage' ),
			'installed' => $is_aioseo_pro_installed ? true : array_key_exists( self::SEO_PACK_URL, $installed_plugins ),
			'basename'  => $is_aioseo_pro_installed ? self::SEO_PACK_PRO_URL : self::SEO_PACK_URL,
			'slug'      => 'aioseo',
			'settings'  => admin_url( 'admin.php?page=aioseo' ),
		);

		// SeedProd.
		$is_seedprod_pro_installed = false;
		if ( array_key_exists( self::SEED_PROD_PRO_URL, $installed_plugins ) ) {
			$is_seedprod_pro_installed = true;
		}
		$parsed_addons['seedprod'] = array(
			'active'    => $is_seedprod_pro_installed ? is_plugin_active( self::SEED_PROD_PRO_URL ) : is_plugin_active( self::SEED_PROD_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-seedprod.png',
			'title'     => $is_seedprod_pro_installed ? __( 'SeedProd Pro', 'pushengage' ) : __( 'SeedProd', 'pushengage' ),
			'excerpt'   => __( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'pushengage' ),
			'installed' => $is_seedprod_pro_installed ? true : array_key_exists( self::SEED_PROD_URL, $installed_plugins ),
			'basename'  => $is_seedprod_pro_installed ? self::SEED_PROD_PRO_URL : self::SEED_PROD_URL,
			'slug'      => 'seedprod',
			'settings'  => $is_seedprod_pro_installed ? admin_url( 'admin.php?page=seedprod_pro' ) : admin_url( 'admin.php?page=seedprod_lite' ),
		);

		// WP Mail SMTP.
		$is_wp_mail_smtp_pro_installed = false;
		if ( array_key_exists( self::WP_MAIL_SMTP_PRO_URL, $installed_plugins ) ) {
			$is_wp_mail_smtp_pro_installed = true;
		}
		$parsed_addons['wp-mail-smtp'] = array(
			'active'    => $is_wp_mail_smtp_pro_installed ? is_plugin_active( self::WP_MAIL_SMTP_PRO_URL ) : is_plugin_active( self::WP_MAIL_SMTP_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-smtp.png',
			'title'     => $is_wp_mail_smtp_pro_installed ? __( 'WP Mail SMTP Pro', 'pushengage' ) : __( 'WP Mail SMTP', 'pushengage' ),
			'excerpt'   => __( 'Improve your WordPress email deliverability and make sure that your website emails reach user’s inbox with the #1 SMTP plugin for WordPress. Over 2 million websites use it to fix WordPress email issues.', 'pushengage' ),
			'installed' => $is_wp_mail_smtp_pro_installed ? true : array_key_exists( self::WP_MAIL_SMTP_URL, $installed_plugins ),
			'basename'  => $is_wp_mail_smtp_pro_installed ? self::WP_MAIL_SMTP_PRO_URL : self::WP_MAIL_SMTP_URL,
			'slug'      => 'wp-mail-smtp',
			'settings'  => admin_url( 'admin.php?page=wp-mail-smtp' ),
		);

		// RafflePress
		$is_raffle_press_pro_installed = false;
		if ( array_key_exists( self::RAFFLE_PRESS_PRO_URL, $installed_plugins ) ) {
			$is_raffle_press_pro_installed = true;
		}
		$parsed_addons['rafflepress'] = array(
			'active'    => $is_raffle_press_pro_installed ? is_plugin_active( self::RAFFLE_PRESS_PRO_URL ) : is_plugin_active( self::RAFFLE_PRESS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/pluign-rafflepress.png',
			'title'     => $is_raffle_press_pro_installed ? __( 'RafflePress Pro', 'pushengage' ) : __( 'RafflePress', 'pushengage' ),
			'excerpt'   => __( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'pushengage' ),
			'installed' => $is_raffle_press_pro_installed ? true : array_key_exists( self::RAFFLE_PRESS_URL, $installed_plugins ),
			'basename'  => $is_raffle_press_pro_installed ? self::RAFFLE_PRESS_PRO_URL : self::RAFFLE_PRESS_URL,
			'slug'      => 'rafflepress',
			'settings'  => $is_raffle_press_pro_installed ? admin_url( 'admin.php?page=rafflepress_pro#/settings' ) : admin_url( 'admin.php?page=rafflepress_lite' ),
		);

		// AffiliateWP
		$parsed_addons['affiliateWp'] = array(
			'active'      => class_exists( 'AffiliateWP_External_Referral_Links' ),
			'icon'        => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-affiliate.png',
			'title'       => __( 'AffiliateWP', 'pushengage' ),
			'excerpt'     => __( 'The #1 affiliate management plugin for WordPress. Easily create an affiliate program for your eCommerce store or membership site within minutes and start growing your sales with the power of referral marketing.', 'pushengage' ),
			'installed'   => array_key_exists( 'affiliate-wp/affiliate-wp.php', $installed_plugins ),
			'basename'    => 'affiliate-wp/affiliate-wp.php',
			'slug'        => 'affiliateWp',
			'settings'    => admin_url( 'admin.php?page=affiliate-wp' ),
			'redirectUrl' => 'https://affiliatewp.com/?utm_source=pushengageplugin&utm_medium=link&utm_campaign=About%20PushEngage',
		);

		// Easy Digital Downloads (EDD)
		$is_edd_pro_installed = false;
		if ( array_key_exists( self::EDD_PRO_URL, $installed_plugins ) ) {
			$is_edd_pro_installed = true;
		}
		$parsed_addons['edd'] = array(
			'active'    => $is_edd_pro_installed ? is_plugin_active( self::EDD_PRO_URL ) : is_plugin_active( self::EDD_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-edd.png',
			'title'     => $is_edd_pro_installed ? __( 'Easy Digital Downloads Pro', 'pushengage' ) : __( 'Easy Digital Downloads', 'pushengage' ),
			'excerpt'   => __( 'The best WordPress eCommerce plugin for selling digital downloads. Start selling eBooks, software, music, digital art, and more within minutes. Accept payments, manage subscriptions, advanced access control, and more.', 'pushengage' ),
			'installed' => $is_edd_pro_installed ? true : array_key_exists( self::EDD_URL, $installed_plugins ),
			'basename'  => $is_edd_pro_installed ? self::EDD_PRO_URL : self::EDD_URL,
			'slug'      => 'edd',
			'settings'  => admin_url( 'edit.php?post_type=download&page=edd-settings' ),
		);

		/**
		 * WP Simple Pay
		 *
		 * @since 4.0.8
		 */
		$is_wp_simple_pay_pro_installed = false;
		if ( array_key_exists( self::WP_SIMPLE_PAY_PRO_URL, $installed_plugins ) ) {
			$is_wp_simple_pay_pro_installed = true;
		}
		$parsed_addons['wp-simple-pay'] = array(
			'active'    => $is_wp_simple_pay_pro_installed ? is_plugin_active( self::WP_SIMPLE_PAY_PRO_URL ) : is_plugin_active( self::WP_SIMPLE_PAY_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-wpsp.png',
			'title'     => $is_wp_simple_pay_pro_installed ? __( 'WP Simple Pay Pro', 'pushengage' ) : __( 'WP Simple Pay', 'pushengage' ),
			'excerpt'   => __( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'pushengage' ),
			'installed' => $is_wp_simple_pay_pro_installed ? true : array_key_exists( self::WP_SIMPLE_PAY_URL, $installed_plugins ),
			'basename'  => $is_wp_simple_pay_pro_installed ? self::WP_SIMPLE_PAY_PRO_URL : self::WP_SIMPLE_PAY_URL,
			'slug'      => 'wp-simple-pay',
			'settings'  => admin_url( 'edit.php?post_type=simple-pay' ),
		);

		/**
		 * Smash Balloon Instagram Feeds
		 *
		 * @since 4.0.8
		 */
		$is_instagram_feed_pro_installed = false;
		if ( array_key_exists( self::SMASH_BALLOON_INSTAGRAM_FEEDS_PRO_URL, $installed_plugins ) ) {
			$is_instagram_feed_pro_installed = true;
		}
		$parsed_addons['smash-balloon-instagram-feeds'] = array(
			'active'    => $is_instagram_feed_pro_installed ? is_plugin_active( self::SMASH_BALLOON_INSTAGRAM_FEEDS_PRO_URL ) : is_plugin_active( self::SMASH_BALLOON_INSTAGRAM_FEEDS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-smash-balloon-instagram-feeds.png',
			'title'     => $is_instagram_feed_pro_installed ? __( 'Smash Balloon Instagram Feeds Pro', 'pushengage' ) : __( 'Smash Balloon Instagram Feeds', 'pushengage' ),
			'excerpt'   => __( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'pushengage' ),
			'installed' => $is_instagram_feed_pro_installed ? true : array_key_exists( self::SMASH_BALLOON_INSTAGRAM_FEEDS_URL, $installed_plugins ),
			'basename'  => $is_instagram_feed_pro_installed ? self::SMASH_BALLOON_INSTAGRAM_FEEDS_PRO_URL : self::SMASH_BALLOON_INSTAGRAM_FEEDS_URL,
			'slug'      => 'smash-balloon-instagram-feeds',
			'settings'  => admin_url( 'admin.php?page=sb-instagram-feed' ),
		);

		/**
		 * Smash Balloon Facebook Feeds
		 *
		 * @since 4.0.8
		 */
		$is_facebook_feed_pro_installed = false;
		if ( array_key_exists( self::SMASH_BALLOON_FACEBOOK_FEEDS_PRO_URL, $installed_plugins ) ) {
			$is_facebook_feed_pro_installed = true;
		}
		$parsed_addons['smash-balloon-facebook-feeds'] = array(
			'active'    => $is_facebook_feed_pro_installed ? is_plugin_active( self::SMASH_BALLOON_FACEBOOK_FEEDS_PRO_URL ) : is_plugin_active( self::SMASH_BALLOON_FACEBOOK_FEEDS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-smash-balloon-facebook-feeds.png',
			'title'     => $is_facebook_feed_pro_installed ? __( 'Smash Balloon Facebook Feeds Pro', 'pushengage' ) : __( 'Smash Balloon Facebook Feeds', 'pushengage' ),
			'excerpt'   => __( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'pushengage' ),
			'installed' => $is_facebook_feed_pro_installed ? true : array_key_exists( self::SMASH_BALLOON_FACEBOOK_FEEDS_URL, $installed_plugins ),
			'basename'  => $is_facebook_feed_pro_installed ? self::SMASH_BALLOON_FACEBOOK_FEEDS_PRO_URL : self::SMASH_BALLOON_FACEBOOK_FEEDS_URL,
			'slug'      => 'smash-balloon-facebook-feeds',
			'settings'  => admin_url( 'admin.php?page=cff-top' ),
		);

		/**
		 * Smash Balloon Twitter Feeds
		 *
		 * @since 4.0.8
		 */
		$is_twitter_feed_pro_installed = false;
		if ( array_key_exists( self::SMASH_BALLON_TWITTER_FEEDS_PRO_URL, $installed_plugins ) ) {
			$is_twitter_feed_pro_installed = true;
		}
		$parsed_addons['smash-balloon-twitter-feeds'] = array(
			'active'    => $is_twitter_feed_pro_installed ? is_plugin_active( self::SMASH_BALLON_TWITTER_FEEDS_PRO_URL ) : is_plugin_active( self::SMASH_BALLON_TWITTER_FEEDS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-smash-balloon-twitter-feeds.png',
			'title'     => $is_twitter_feed_pro_installed ? __( 'Smash Balloon Twitter Feeds Pro', 'pushengage' ) : __( 'Smash Balloon Twitter Feeds', 'pushengage' ),
			'excerpt'   => __( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'pushengage' ),
			'installed' => $is_twitter_feed_pro_installed ? true : array_key_exists( self::SMASH_BALLON_TWITTER_FEEDS_URL, $installed_plugins ),
			'basename'  => $is_twitter_feed_pro_installed ? self::SMASH_BALLON_TWITTER_FEEDS_PRO_URL : self::SMASH_BALLON_TWITTER_FEEDS_URL,
			'slug'      => 'smash-balloon-twitter-feeds',
			'settings'  => admin_url( 'admin.php?page=custom-twitter-feeds' ),
		);

		/**
		 * Smash Balloon YouTube Feeds
		 *
		 * @since 4.0.8
		 */
		$is_youtube_feed_pro_installed = false;
		if ( array_key_exists( self::SMASH_BALLOON_YOUTUBE_FEEDS_PRO_URL, $installed_plugins ) ) {
			$is_youtube_feed_pro_installed = true;
		}
		$parsed_addons['smash-balloon-youtube-feeds'] = array(
			'active'    => $is_youtube_feed_pro_installed ? is_plugin_active( self::SMASH_BALLOON_YOUTUBE_FEEDS_PRO_URL ) : is_plugin_active( self::SMASH_BALLOON_YOUTUBE_FEEDS_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-smash-balloon-youtube-feeds.png',
			'title'     => $is_youtube_feed_pro_installed ? __( 'Smash Balloon YouTube Feeds Pro', 'pushengage' ) : __( 'Smash Balloon YouTube Feeds', 'pushengage' ),
			'excerpt'   => __( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'pushengage' ),
			'installed' => $is_youtube_feed_pro_installed ? true : array_key_exists( self::SMASH_BALLOON_YOUTUBE_FEEDS_URL, $installed_plugins ),
			'basename'  => $is_youtube_feed_pro_installed ? self::SMASH_BALLOON_YOUTUBE_FEEDS_PRO_URL : self::SMASH_BALLOON_YOUTUBE_FEEDS_URL,
			'slug'      => 'smash-balloon-youtube-feeds',
			'settings'  => admin_url( 'admin.php?page=youtube-feed' ),
		);

		/**
		 * WP Charitable Plugin
		 *
		 * @since 4.0.10
		 */
		$parsed_addons['charitable'] = array(
			'active'    => is_plugin_active( self::WP_CHARITABLE_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-charitable.svg',
			'title'     => __( 'Charitable', 'pushengage' ),
			'excerpt'   => __( 'Charitable is the best WordPress donation plugin for non-profits, charities, and fundraising organizations. Easily accept donations, create fundraising campaigns, and manage donors with the most powerful donation plugin for WordPress.', 'pushengage' ),
			'installed' => array_key_exists( self::WP_CHARITABLE_URL, $installed_plugins ),
			'basename'  => self::WP_CHARITABLE_URL,
			'slug'      => 'charitable',
			'settings'  => admin_url( 'admin.php?page=charitable-settings' ),
		);

		/**
		 * Duplicator Plugin
		 *
		 * @since 4.0.10
		 */
		$is_duplicator_pro_installed = false;
		if ( array_key_exists( self::DUPLICATOR_PRO_URL, $installed_plugins ) ) {
			$is_duplicator_pro_installed = true;
		}
		$parsed_addons['duplicator'] = array(
			'active'    => $is_duplicator_pro_installed ? is_plugin_active( self::DUPLICATOR_PRO_URL ) : is_plugin_active( self::DUPLICATOR_URL ),
			'icon'      => PUSHENGAGE_PLUGIN_URL . '/assets/img/plugin-duplicator.svg',
			'title'     => $is_duplicator_pro_installed ? __( 'Duplicator Pro', 'pushengage' ) : __( 'Duplicator', 'pushengage' ),
			'excerpt'   => __( 'The most popular WordPress migration plugin for backing up, restoring, and moving your WordPress site to a new host or domain. Trusted by over 1 million websites for easy WordPress backups and migrations.', 'pushengage' ),
			'installed' => $is_duplicator_pro_installed ? true : array_key_exists( self::DUPLICATOR_URL, $installed_plugins ),
			'basename'  => $is_duplicator_pro_installed ? self::DUPLICATOR_PRO_URL : self::DUPLICATOR_URL,
			'slug'      => 'duplicator',
			'settings'  => admin_url( 'admin.php?page=duplicator' ),
		);

		return $parsed_addons;
	}

	/**
	 * Check if specific addon is installed or not
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug Slug of the addon
	 * @return boolean
	 */
	public static function is_addon_installed( $slug ) {
		$addon = self::get_addons();
		if ( isset( $addon[ $slug ] ) ) {
			return $addon[ $slug ]['installed'];
		}

		return false;
	}

	/**
	 * Get specific addon by addon slug
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug Slug of the addon
	 * @return array|null
	 */
	public static function get_addon( $slug ) {
		$addon = self::get_addons();
		if ( isset( $addon[ $slug ] ) ) {
			return $addon[ $slug ];
		}

		return null;
	}

	/**
	 * Install / Activate addon
	 *
	 * @param string $slug
	 * @return string|boolean
	 */
	public static function install( $slug ) {
		// sanitize addon slug
		$slug = isset( $slug ) ? sanitize_text_field( wp_unslash( $slug ) ) : false;

		if ( ! $slug ) {
			return false;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		$url = esc_url_raw(
			add_query_arg(
				array(
					'page' => 'pushengage',
				),
				admin_url( 'admin.php' )
			)
		);

		// Create the plugin upgrader with our custom skin.
		$installer = new PluginUpgraderSilent( new PluginUpgraderSkin() );

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		// Activate the plugin silently.
		$addon      = self::get_addon( $slug );
		$plugin_url = ! empty( $addon['basename'] ) ? $addon['basename'] : '';
		$activated  = activate_plugin( $plugin_url );

		if ( ! is_wp_error( $activated ) ) {
			return $slug;
		}

		// Using output buffering to prevent the FTP form from being displayed in the screen.
		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		ob_end_clean();

		// Check for file system permissions.
		if ( false === $creds ) {
			return false;
		}

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			return false;
		}

		$install_link = ! empty( self::$plugin_links[ $slug ] ) ? self::$plugin_links[ $slug ] : null;

		$installer->install( $install_link );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$plugin_base_name = $installer->plugin_info();

		if ( ! $plugin_base_name ) {
			return false;
		}

		// Activate the plugin silently.
		$activated = activate_plugin( $plugin_base_name );

		if ( is_wp_error( $activated ) ) {
			return false;
		}

		return $plugin_base_name;
	}
}
