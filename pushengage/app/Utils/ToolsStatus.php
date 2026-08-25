<?php
/**
 * Runtime status for the tools surfaced on the PushEngage "Tools" screen.
 *
 * Two of those tools are not static marketing cards — their state depends on
 * the site: the WordPress Abilities API (core, 6.9+) and the MCP Adapter
 * plugin that bridges those abilities to external AI clients. This class is
 * the single place that inspects both, so the React app never has to know how
 * either is detected.
 *
 * @since 4.2.9
 */

namespace Pushengage\Utils;

use Pushengage\Integrations\Abilities\AbstractRegistrar;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ToolsStatus
 *
 * @since 4.2.9
 */
class ToolsStatus {

	/**
	 * Ability category the plugin registers its abilities under.
	 *
	 * @var string
	 */
	const ABILITY_CATEGORY = 'pushengage';

	/**
	 * WordPress version that introduced the Abilities API.
	 *
	 * @var string
	 */
	const ABILITIES_MIN_WP = '6.9';

	/**
	 * WordPress version that added the `$args` filter to `wp_get_abilities()`.
	 *
	 * @var string
	 */
	const ABILITIES_ARGS_MIN_WP = '7.1';

	/**
	 * MCP Adapter plugin basename and default server id.
	 *
	 * @var string
	 */
	const MCP_PLUGIN_BASENAME = 'mcp-adapter/mcp-adapter.php';
	const MCP_DEFAULT_SERVER  = 'mcp-adapter-default-server';

	/**
	 * Release downloads for the MCP Adapter. It is not on wordpress.org.
	 *
	 * @var string
	 */
	const MCP_RELEASES_URL = 'https://github.com/WordPress/mcp-adapter/releases';

	/**
	 * Build the full status payload for the Tools screen.
	 *
	 * Deliberately carries only the two live readings. Site identity (name,
	 * URL, WordPress version) is already localized on `window.pushengage` by
	 * EnqueueAssets for every admin page; the React app merges it from there,
	 * so serving it again here would just create a second source of truth.
	 *
	 * @since 4.2.9
	 * @return array
	 */
	public static function get_status() {
		return array(
			'abilities' => self::get_abilities_status(),
			'mcp'       => self::get_mcp_status(),
		);
	}

	/**
	 * Status of the PushEngage abilities registered with the core Abilities API.
	 *
	 * @since 4.2.9
	 * @return array
	 */
	private static function get_abilities_status() {
		$status = array(
			'supported'         => false,
			'requiredWpVersion' => self::ABILITIES_MIN_WP,
			'capability'        => AbstractRegistrar::REQUIRED_CAPABILITY,
			'count'             => 0,
			'groups'            => array(),
		);

		// The Abilities API ships in WP 6.9+. On older versions the plugin never
		// registers anything, so there is nothing to report.
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return $status;
		}

		$status['supported'] = true;

		$abilities = self::get_pushengage_abilities();
		$grouped   = array();

		foreach ( $abilities as $ability ) {
			$name = $ability->get_name();

			// `meta.group` is stamped by AbstractRegistrar from the registrar
			// class name. Abilities registered by third parties into our
			// category may not carry it.
			$group_slug = $ability->get_meta_item( 'group' );
			$group_slug = is_string( $group_slug ) && '' !== $group_slug ? $group_slug : 'other';

			if ( ! isset( $grouped[ $group_slug ] ) ) {
				$grouped[ $group_slug ] = array(
					'slug'  => $group_slug,
					'label' => self::get_group_label( $group_slug ),
					'items' => array(),
				);
			}

			$grouped[ $group_slug ]['items'][] = array(
				'name'        => $name,
				'label'       => $ability->get_label(),
				'description' => $ability->get_description(),
				'readOnly'    => self::is_read_only( $ability ),
			);

			++$status['count'];
		}

		// Present groups in a stable, human-sensible order rather than
		// registration order.
		$status['groups'] = self::sort_groups( $grouped );

		return $status;
	}

	/**
	 * Fetch abilities belonging to the PushEngage category.
	 *
	 * `wp_get_abilities()` only accepts filter arguments from WP 7.1; on 6.9 and
	 * 7.0 it returns everything, so filter by category in PHP instead.
	 *
	 * @since 4.2.9
	 * @return \WP_Ability[]
	 */
	private static function get_pushengage_abilities() {
		$wp_version = get_bloginfo( 'version' );

		if ( version_compare( $wp_version, self::ABILITIES_ARGS_MIN_WP, '>=' ) ) {
			return wp_get_abilities( array( 'category' => self::ABILITY_CATEGORY ) );
		}

		return array_filter(
			wp_get_abilities(),
			static function ( $ability ) {
				return self::ABILITY_CATEGORY === $ability->get_category();
			}
		);
	}

	/**
	 * Whether an ability only reads data.
	 *
	 * Prefers the ability's own `annotations.readonly` declaration — every
	 * read-only PushEngage ability sets it, and it is the contract MCP clients
	 * consume. Abilities without the annotation (PushEngage write abilities
	 * declare `destructive`/`idempotent` instead, and third parties registering
	 * into our category may declare nothing) fall back to the verb heuristic:
	 * `get-*` and `list-*` slugs read, action verbs (`send-`, `create-`,
	 * `update-`, `add-`) mutate. The namespace prefix is stripped generically
	 * because third-party abilities in this category keep their own namespace.
	 *
	 * @since 4.2.9
	 * @param \WP_Ability $ability Ability instance.
	 * @return bool
	 */
	private static function is_read_only( $ability ) {
		$annotations = $ability->get_meta_item( 'annotations' );

		if ( is_array( $annotations ) && isset( $annotations['readonly'] ) ) {
			return (bool) $annotations['readonly'];
		}

		$name  = $ability->get_name();
		$slash = strpos( $name, '/' );
		$slug  = false !== $slash ? substr( $name, $slash + 1 ) : $name;

		return 0 === strpos( $slug, 'get-' ) || 0 === strpos( $slug, 'list-' );
	}

	/**
	 * Ordered map of known ability group slugs to human labels.
	 *
	 * Single source of truth for both the display label and the display order,
	 * so adding a registrar needs exactly one entry here — a separate label map
	 * and order list would silently drift apart.
	 *
	 * @since 4.2.9
	 * @return array<string, string> Slug => label, in display order.
	 */
	private static function get_group_labels() {
		return array(
			'notification' => __( 'Notifications', 'pushengage' ),
			'segment'      => __( 'Audience', 'pushengage' ),
			'analytics'    => __( 'Analytics', 'pushengage' ),
			'automation'   => __( 'Automations', 'pushengage' ),
			'whatsapp'     => __( 'WhatsApp', 'pushengage' ),
			'settings'     => __( 'Settings', 'pushengage' ),
			'plugin-info'  => __( 'Plugin info', 'pushengage' ),
			'debug'        => __( 'Debug', 'pushengage' ),
			'other'        => __( 'Other', 'pushengage' ),
		);
	}

	/**
	 * Human label for an ability group slug.
	 *
	 * Unknown slugs (a third-party registrar, or a new PushEngage registrar
	 * added before the map is updated) fall back to a humanized slug so the
	 * screen degrades gracefully instead of rendering a raw slug.
	 *
	 * @since 4.2.9
	 * @param string $slug Group slug.
	 * @return string
	 */
	private static function get_group_label( $slug ) {
		$labels = self::get_group_labels();

		if ( isset( $labels[ $slug ] ) ) {
			return $labels[ $slug ];
		}

		return ucfirst( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Order ability groups for display, following the label map's order.
	 *
	 * @since 4.2.9
	 * @param array $grouped Groups keyed by slug.
	 * @return array Numerically indexed, ordered groups.
	 */
	private static function sort_groups( $grouped ) {
		$sorted = array();

		foreach ( array_keys( self::get_group_labels() ) as $slug ) {
			if ( isset( $grouped[ $slug ] ) ) {
				$sorted[] = $grouped[ $slug ];
				unset( $grouped[ $slug ] );
			}
		}

		// Unknown groups go last, in registration order.
		foreach ( $grouped as $group ) {
			$sorted[] = $group;
		}

		return $sorted;
	}

	/**
	 * Status of the MCP Adapter plugin.
	 *
	 * The adapter ships no admin UI of its own, so this screen is the only
	 * place an operator can confirm it is running and find the endpoint their
	 * AI client should connect to.
	 *
	 * @since 4.2.9
	 * @return array
	 */
	private static function get_mcp_status() {
		$is_active = self::is_mcp_adapter_running();

		// Only offer an activation link the requesting user can actually use.
		// On multisite, sub-site admins see network-installed plugin files
		// (installed=true) but activating maps to `manage_network_plugins`, so
		// the link would dead-end in wp_die. An empty activateUrl tells the
		// client to fall back to its explore/read-only presentation.
		$can_activate = current_user_can( 'activate_plugin', self::MCP_PLUGIN_BASENAME );

		$status = array(
			'active'      => $is_active,
			'installed'   => $is_active || self::is_plugin_installed( self::MCP_PLUGIN_BASENAME ),
			'endpoint'    => null,
			'installUrl'  => self::get_plugin_install_url(),
			'activateUrl' => $can_activate ? self::get_plugin_activate_url() : '',
		);

		if ( $is_active ) {
			$status['endpoint'] = self::get_mcp_endpoint();
		}

		return $status;
	}

	/**
	 * Whether the MCP Adapter is actually running on this site.
	 *
	 * Requires two independent signals:
	 *
	 * Activation — `is_plugin_active()` (single-site and network) or the
	 * `WP_MCP_DIR` constant the adapter's own bootstrap defines, which keeps
	 * mu-plugin and custom-bootstrap installs detected. `class_exists()` alone
	 * is deliberately NOT an activation signal: the adapter is also shipped as
	 * a composer library, and plugins that bundle it — WooCommerce carries a
	 * copy under `vendor/wordpress/mcp-adapter/` — make the class autoloadable
	 * while nothing is serving our abilities.
	 *
	 * Readiness — the adapter class actually loaded. Activation alone is not
	 * proof of that: the bootstrap defines `WP_MCP_DIR` before wiring its
	 * autoloader and returns early when autoloading fails, so a broken install
	 * would otherwise be reported as running with an endpoint that 404s.
	 *
	 * @since 4.2.9
	 * @return bool
	 */
	private static function is_mcp_adapter_running() {
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::MCP_PLUGIN_BASENAME ) || defined( 'WP_MCP_DIR' );
	}

	/**
	 * Resolve the REST endpoint of the adapter's default MCP server.
	 *
	 * Null when the site disables the default server entirely through the
	 * adapter's `mcp_adapter_create_default_server` filter — advertising an
	 * endpoint nothing serves would send agents to a 404.
	 *
	 * The route itself is filterable through `mcp_adapter_default_server_config`.
	 * When the adapter has already booted (REST or WP-CLI requests), the route
	 * is read back off the registered server. During the admin-ajax request
	 * that feeds the Tools screen the adapter has not initialized — it only
	 * creates servers on `rest_api_init`, and `did_action()` gates the lookup
	 * so this never instantiates the adapter singleton on an action that has
	 * already passed. In that case the route is derived by running the same
	 * config filter over the adapter's documented routing defaults, so a
	 * filtered route still surfaces correctly here.
	 *
	 * Every call is guarded — the adapter is pre-1.0 and its API may shift.
	 *
	 * @since 4.2.9
	 * @return string|null
	 */
	private static function get_mcp_endpoint() {
		/** This filter is documented in mcp-adapter/includes/Core/McpAdapter.php */
		if ( ! apply_filters( 'mcp_adapter_create_default_server', true ) ) {
			return null;
		}

		if ( did_action( 'mcp_adapter_init' )
			&& method_exists( '\WP\MCP\Core\McpAdapter', 'instance' ) ) {
			$adapter = \WP\MCP\Core\McpAdapter::instance();

			if ( is_object( $adapter ) && method_exists( $adapter, 'get_server' ) ) {
				$server = $adapter->get_server( self::MCP_DEFAULT_SERVER );

				if ( is_object( $server )
					&& method_exists( $server, 'get_server_route_namespace' )
					&& method_exists( $server, 'get_server_route' ) ) {
					return rest_url(
						trailingslashit( $server->get_server_route_namespace() ) . $server->get_server_route()
					);
				}
			}
		}

		// Mirror DefaultServerFactory::create(): run the adapter's config
		// filter over its documented routing defaults so a site that moves the
		// route sees its real endpoint. Non-routing keys (transports, handlers,
		// tool lists) are omitted — they reference adapter classes and play no
		// part in the URL.
		/** This filter is documented in mcp-adapter/includes/Servers/DefaultServerFactory.php */
		$config = apply_filters(
			'mcp_adapter_default_server_config',
			array(
				'server_id'              => self::MCP_DEFAULT_SERVER,
				'server_route_namespace' => 'mcp',
				'server_route'           => self::MCP_DEFAULT_SERVER,
			)
		);

		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$config = wp_parse_args(
			$config,
			array(
				'server_route_namespace' => 'mcp',
				'server_route'           => self::MCP_DEFAULT_SERVER,
			)
		);

		return rest_url(
			trailingslashit( (string) $config['server_route_namespace'] ) . (string) $config['server_route']
		);
	}

	/**
	 * Whether a plugin is present on disk but possibly inactive.
	 *
	 * A direct file check rather than `get_plugins()`: that helper re-scans
	 * the whole plugins directory and parses every plugin's header on each
	 * request (its cache group is non-persistent), which is a lot of I/O to
	 * answer "does this one known file exist".
	 *
	 * @since 4.2.9
	 * @param string $basename Plugin basename.
	 * @return bool
	 */
	private static function is_plugin_installed( $basename ) {
		return file_exists( WP_PLUGIN_DIR . '/' . $basename );
	}

	/**
	 * Where to get the MCP Adapter.
	 *
	 * The adapter is not published on wordpress.org, so the one-click
	 * `update.php?action=install-plugin` route does not exist for it — that
	 * endpoint only resolves slugs against the .org plugin directory and would
	 * fail. Operators download a release build from GitHub and upload it, so
	 * this is an external link rather than an admin action.
	 *
	 * @since 4.2.9
	 * @return string
	 */
	private static function get_plugin_install_url() {
		return self::MCP_RELEASES_URL;
	}

	/**
	 * Nonced URL that activates an already-installed MCP Adapter.
	 *
	 * @since 4.2.9
	 * @return string
	 */
	private static function get_plugin_activate_url() {
		return self::build_nonced_admin_url(
			'plugins.php',
			array(
				'action' => 'activate',
				'plugin' => self::MCP_PLUGIN_BASENAME,
			),
			'activate-plugin_' . self::MCP_PLUGIN_BASENAME
		);
	}

	/**
	 * Build a nonced admin URL safe to hand to a JSON consumer.
	 *
	 * Deliberately avoids `wp_nonce_url()`, which runs the finished URL through
	 * `esc_html()` and so returns `&amp;` between arguments. That is correct
	 * when the URL is echoed straight into an HTML attribute from PHP, but this
	 * value travels as JSON to the React app, which assigns it to `href`
	 * verbatim. The browser then reads the separator literally and the request
	 * arrives with an `amp;_wpnonce` parameter instead of `_wpnonce`, so
	 * WordPress finds no nonce and shows "Are you sure you want to do this?".
	 *
	 * @since 4.2.9
	 * @param string $path   Admin file, e.g. `plugins.php`.
	 * @param array  $args   Query arguments. Appended verbatim by
	 *                       `add_query_arg()`, which does NOT URL-encode
	 *                       values — pass only URL-safe constants.
	 * @param string $action Nonce action.
	 * @return string
	 */
	private static function build_nonced_admin_url( $path, $args, $action ) {
		$args['_wpnonce'] = wp_create_nonce( $action );

		return add_query_arg( $args, self_admin_url( $path ) );
	}
}
