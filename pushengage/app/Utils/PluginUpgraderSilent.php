<?php
namespace Pushengage\Utils;

/**
 * PluginUpgraderSilentAjax
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;

/** \WP_Upgrader class */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/** \Plugin_Upgrader class */
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

class PluginUpgraderSilent extends \Plugin_Upgrader {
	/**
	 * Run an upgrade/installation.
	 *
	 * Attempt to download the package (if it is not a local file), unpack it, and
	 * install it in the destination folder.
	 *
	 * @since 1.5.6.1
	 *
	 * @param array $options {
	 *     Array or string of arguments for upgrading/installing a package.
	 *
	 *     @type string $package                     The full path or URI of the package to install.
	 *                                               Default empty.
	 *     @type string $destination                 The full path to the destination folder.
	 *                                               Default empty.
	 *     @type bool   $clear_destination           Whether to delete any files already in the
	 *                                               destination folder. Default false.
	 *     @type bool   $clear_working               Whether to delete the files form the working
	 *                                               directory after copying to the destination.
	 *                                               Default false.
	 *     @type bool   $abort_if_destination_exists Whether to abort the installation if the destination
	 *                                               folder already exists. When true, `$clear_destination`
	 *                                               should be false. Default true.
	 *     @type bool   $is_multi                    Whether this run is one of multiple upgrade/installation
	 *                                               actions being performed in bulk. When true, the skin
	 *                                               WP_Upgrader::header() and WP_Upgrader::footer()
	 *                                               aren't called. Default false.
	 *     @type array  $hook_extra                  Extra arguments to pass to the filter hooks called by
	 *                                               WP_Upgrader::run().
	 * }
	 * @return array|false|WP_error The result from self::install_package() on success, otherwise a WP_Error,
	 *                              or false if unable to connect to the filesystem.
	 */
	public function run( $options ) {

		$defaults = array(
			'package'                     => '', // Please always pass this.
			'destination'                 => '', // And this
			'clear_destination'           => false,
			'abort_if_destination_exists' => true, // Abort if the Destination directory exists, Pass clear_destination as false please
			'clear_working'               => true,
			'is_multi'                    => false,
			'hook_extra'                  => array(), // Pass any extra $hook_extra args here, this will be passed to any hooked filters.
		);

		$options = wp_parse_args( $options, $defaults );

		/**
		 * Filter the package options before running an update.
		 *
		 * See also {@see 'upgrader_process_complete'}.
		 *
		 * @since 4.3.0
		 *
		 * @param array $options {
		 *     Options used by the upgrader.
		 *
		 *     @type string $package                     Package for update.
		 *     @type string $destination                 Update location.
		 *     @type bool   $clear_destination           Clear the destination resource.
		 *     @type bool   $clear_working               Clear the working resource.
		 *     @type bool   $abort_if_destination_exists Abort if the Destination directory exists.
		 *     @type bool   $is_multi                    Whether the upgrader is running multiple times.
		 *     @type array  $hook_extra {
		 *         Extra hook arguments.
		 *
		 *         @type string $action               Type of action. Default 'update'.
		 *         @type string $type                 Type of update process. Accepts 'plugin', 'theme', or 'core'.
		 *         @type bool   $bulk                 Whether the update process is a bulk update. Default true.
		 *         @type string $plugin               Path to the plugin file relative to the plugins directory.
		 *         @type string $theme                The stylesheet or template name of the theme.
		 *         @type string $language_update_type The language pack update type. Accepts 'plugin', 'theme',
		 *                                            or 'core'.
		 *         @type object $language_update      The language pack update offer.
		 *     }
		 * }
		 */
		$options = apply_filters( 'upgrader_package_options', $options );

		$this->call_skin_header( $options );

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array( WP_CONTENT_DIR, $options['destination'] ) );
		// Mainly for non-connected filesystem.
		if ( ! $res ) {
			$this->call_skin_footer( $options );
			return false;
		}

		$this->skin->before();

		if ( is_wp_error( $res ) ) {
			$this->skin->error( $res );
			$this->skin->after();
			$this->call_skin_footer( $options );

			return $res;
		}

		/*
		 * Download the package (Note, This just returns the filename
		 * of the file if the package is a local file)
		 */
		$download = $this->download_package( $options['package'], true );

		// Allow for signature soft-fail.
		// WARNING: This may be removed in the future.
		if ( is_wp_error( $download ) && $download->get_error_data( 'softfail-filename' ) ) {
			$this->verify_signature_verification( $download );
			$download = $download->get_error_data( 'softfail-filename' );
		}

		if ( is_wp_error( $download ) ) {
			$this->skin->error( $download );
			$this->skin->after();
			$this->call_skin_footer( $options );
			return $download;
		}

		$delete_package = ( $download !== $options['package'] ); // Do not delete a "local" file

		// Unzips the file into a temporary directory.
		$working_dir = $this->unpack_package( $download, $delete_package );
		if ( is_wp_error( $working_dir ) ) {
			$this->skin->error( $working_dir );
			$this->skin->after();
			$this->call_skin_footer( $options );
			return $working_dir;
		}

		// With the given options, this installs it to the destination directory.
		$result = $this->install_package(
			array(
				'source'                      => $working_dir,
				'destination'                 => $options['destination'],
				'clear_destination'           => $options['clear_destination'],
				'abort_if_destination_exists' => $options['abort_if_destination_exists'],
				'clear_working'               => $options['clear_working'],
				'hook_extra'                  => $options['hook_extra'],
			)
		);

		$this->skin->set_result( $result );
		if ( is_wp_error( $result ) ) {
			$this->skin->error( $result );
			// $this->skin->feedback( 'process_failed' );
		}

		$this->skin->after();

		if ( ! $options['is_multi'] ) {

			/**
			 * Fire when the upgrader process is complete.
			 *
			 * See also {@see 'upgrader_package_options'}.
			 *
			 * @since 3.6.0
			 * @since 3.7.0 Added to WP_Upgrader::run().
			 * @since 4.6.0 `$translations` was added as a possible argument to `$hook_extra`.
			 *
			 * @param WP_Upgrader $this WP_Upgrader instance. In other contexts, $this, might be a
			 *                          Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
			 * @param array       $hook_extra {
			 *     Array of bulk item update data.
			 *
			 *     @type string $action       Type of action. Default 'update'.
			 *     @type string $type         Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
			 *     @type bool   $bulk         Whether the update process is a bulk update. Default true.
			 *     @type array  $plugins      Array of the basename paths of the plugins' main files.
			 *     @type array  $themes       The theme slugs.
			 *     @type array  $translations {
			 *         Array of translations update data.
			 *
			 *         @type string $language The locale the translation is for.
			 *         @type string $type     Type of translation. Accepts 'plugin', 'theme', or 'core'.
			 *         @type string $slug     Text domain the translation is for. The slug of a theme/plugin or
			 *                                'default' for core translations.
			 *         @type string $version  The version of a theme, plugin, or core.
			 *     }
			 * }
			 */
			do_action( 'upgrader_process_complete', $this, $options['hook_extra'] );

			$this->skin->footer();
		}

		return $result;
	}

	/**
	 * Call header of skin
	 *
	 * @param array $options
	 * @return boolean
	 */
	public function call_skin_header( $options ) {
		if ( ! $options['is_multi'] ) { // call $this->header separately if running multiple times
			$this->skin->header();
		}

		return true;
	}

	/**
	 * Call footer of skin
	 *
	 * @param array $options
	 * @return boolean
	 */
	public function call_skin_footer( $options ) {
		if ( ! $options['is_multi'] ) {
			$this->skin->footer();
		}

		return true;
	}

	/**
	 * Verify signature verification of downloaded plugin
	 *
	 * @param array|object $download
	 * @return void
	 */
	public function verify_signature_verification( $download ) {
		// Don't output the 'no signature could be found' failure message for now.
		if ( 'signature_verification_no_signature' !== $download->get_error_code() || WP_DEBUG ) {
			// Outout the failure error as a normal feedback, and not as an error:
			// $this->skin->feedback( $download->get_error_message() );

			// Report this failure back to WordPress.org for debugging purposes.
			wp_version_check(
				array(
					'signature_failure_code' => $download->get_error_code(),
					'signature_failure_data' => $download->get_error_data(),
				)
			);
		}
	}

	/**
	 * Toggle maintenance mode for the site.
	 *
	 * Create/delete the maintenance file to enable/disable maintenance mode.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 *
	 * @param bool $enable True to enable maintenance mode, false to disable.
	 */
	public function maintenance_mode( $enable = false ) { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
		global $wp_filesystem;
		$file = $wp_filesystem->abspath() . '.maintenance';
		if ( $enable ) {
			// $this->skin->feedback( 'maintenance_start' );
			// Create maintenance file to signal that we are upgrading
			$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
			$wp_filesystem->delete( $file );
			$wp_filesystem->put_contents( $file, $maintenance_string, FS_CHMOD_FILE );
		} elseif ( ! $enable && $wp_filesystem->exists( $file ) ) {
			// $this->skin->feedback( 'maintenance_end' );
			$wp_filesystem->delete( $file );
		}
	}

	/**
	 * Download a package.
	 *
	 * @since 2.8.0
	 * @since 5.5.0 Added the `$hook_extra` parameter.
	 *
	 * @param string $package          The URI of the package. If this is the full path to an
	 *                                 existing local file, it will be returned untouched.
	 * @param bool   $check_signatures Whether to validate file signatures. Default false.
	 * @param array  $hook_extra       Extra arguments to pass to the filter hooks. Default empty array.
	 * @return string|WP_Error The full path to the downloaded package file, or a WP_Error object.
	 */
	public function download_package( $package, $check_signatures = false, $hook_extra = array() ) { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps

		/**
		 * Filters whether to return the package.
		 *
		 * @since 3.7.0
		 * @since 5.5.0 Added the `$hook_extra` parameter.
		 *
		 * @param bool        $reply      Whether to bail without returning the package.
		 *                                Default false.
		 * @param string      $package    The package file name.
		 * @param WP_Upgrader $this       The WP_Upgrader instance.
		 * @param array       $hook_extra Extra arguments passed to hooked filters.
		 */
		$reply = apply_filters( 'upgrader_pre_download', false, $package, $this, $hook_extra );
		if ( false !== $reply ) {
			return $reply;
		}

		if ( ! preg_match( '!^(http|https|ftp)://!i', $package ) && file_exists( $package ) ) { // Local file or remote?
			return $package; // Must be a local file.
		}

		if ( empty( $package ) ) {
			return new WP_Error( 'no_package', $this->strings['no_package'] );
		}

		// $this->skin->feedback( 'downloading_package', $package );

		$download_file = download_url( $package, 300, $check_signatures );

		if ( is_wp_error( $download_file ) && ! $download_file->get_error_data( 'softfail-filename' ) ) {
			return new WP_Error( 'download_failed', $this->strings['download_failed'], $download_file->get_error_message() );
		}

		return $download_file;
	}

	/**
	 * Unpack a compressed package file.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
	 *
	 * @param string $package        Full path to the package file.
	 * @param bool   $delete_package Optional. Whether to delete the package file after attempting
	 *                               to unpack it. Default true.
	 * @return string|WP_Error The path to the unpacked contents, or a WP_Error on failure.
	 */
	public function unpack_package( $package, $delete_package = true ) { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
		global $wp_filesystem;

		// $this->skin->feedback( 'unpack_package' );

		$upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/';

		// Clean up contents of upgrade directory beforehand.
		$upgrade_files = $wp_filesystem->dirlist( $upgrade_folder );
		if ( ! empty( $upgrade_files ) ) {
			foreach ( $upgrade_files as $file ) {
				$wp_filesystem->delete( $upgrade_folder . $file['name'], true );
			}
		}

		// We need a working directory - Strip off any .tmp or .zip suffixes
		$working_dir = $upgrade_folder . basename( basename( $package, '.tmp' ), '.zip' );

		// Clean up working directory
		if ( $wp_filesystem->is_dir( $working_dir ) ) {
			$wp_filesystem->delete( $working_dir, true );
		}

		// Unzip package to working directory
		$result = unzip_file( $package, $working_dir );

		// Once extracted, delete the package if required.
		if ( $delete_package ) {
			wp_delete_file( $package );
		}

		if ( is_wp_error( $result ) ) {
			$wp_filesystem->delete( $working_dir, true );
			if ( 'incompatible_archive' === $result->get_error_code() ) {
				return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], $result->get_error_data() );
			}

			return $result;
		}

		return $working_dir;
	}

	/**
	 * Install a package.
	 *
	 * Copies the contents of a package form a source directory, and installs them in
	 * a destination directory. Optionally removes the source. It can also optionally
	 * clear out the destination folder if it already exists.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem        WordPress filesystem subclass.
	 * @global array              $wp_theme_directories
	 *
	 * @param array|string $args {
	 *     Optional. Array or string of arguments for installing a package. Default empty array.
	 *
	 *     @type string $source                      Required path to the package source. Default empty.
	 *     @type string $destination                 Required path to a folder to install the package in.
	 *                                               Default empty.
	 *     @type bool   $clear_destination           Whether to delete any files already in the destination
	 *                                               folder. Default false.
	 *     @type bool   $clear_working               Whether to delete the files form the working directory
	 *                                               after copying to the destination. Default false.
	 *     @type bool   $abort_if_destination_exists Whether to abort the installation if
	 *                                               the destination folder already exists. Default true.
	 *     @type array  $hook_extra                  Extra arguments to pass to the filter hooks called by
	 *                                               WP_Upgrader::install_package(). Default empty array.
	 * }
	 *
	 * @return array|WP_Error The result (also stored in `WP_Upgrader::$result`), or a WP_Error on failure.
	 */
	public function install_package( $args = array() ) { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
		global $wp_filesystem, $wp_theme_directories;

		$defaults = array(
			'source'                      => '', // Please always pass this
			'destination'                 => '', // and this
			'clear_destination'           => false,
			'clear_working'               => false,
			'abort_if_destination_exists' => true,
			'hook_extra'                  => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		// These were previously extract()'d.
		$source            = $args['source'];
		$destination       = $args['destination'];
		$clear_destination = $args['clear_destination'];

		set_time_limit( 300 );

		$this->check_source_and_destination_error( $source, $destination );

		/**
		 * Filter the install response before the installation has started.
		 *
		 * Returning a truthy value, or one that could be evaluated as a WP_Error
		 * will effectively short-circuit the installation, returning that value
		 * instead.
		 *
		 * @since 2.8.0
		 *
		 * @param bool|WP_Error $response   Response.
		 * @param array         $hook_extra Extra arguments passed to hooked filters.
		 */
		$res = apply_filters( 'upgrader_pre_install', true, $args['hook_extra'] );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		// Retain the Original source and destinations
		$remote_source     = $args['source'];
		$local_destination = $destination;

		$source_files       = array_keys( $wp_filesystem->dirlist( $remote_source ) );
		$remote_destination = $wp_filesystem->find_folder( $local_destination );
		$source             = $this->generate_source_from_file_or_folder( $wp_filesystem, $args, $source_files );

		/**
		 * Filter the source file location for the upgrade package.
		 *
		 * @since 2.8.0
		 * @since 4.4.0 The $hook_extra parameter became available.
		 *
		 * @param string      $source        File source location.
		 * @param string      $remote_source Remote file source location.
		 * @param WP_Upgrader $this          WP_Upgrader instance.
		 * @param array       $hook_extra    Extra arguments passed to hooked filters.
		 */
		$source = apply_filters( 'upgrader_source_selection', $source, $remote_source, $this, $args['hook_extra'] );

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		// Has the source location changed? If so, we need a new source_files list.
		if ( $source !== $remote_source ) {
			$source_files = array_keys( $wp_filesystem->dirlist( $source ) );
		}

		/*
		 * Protection against deleting files in any important base directories.
		 * Theme_Upgrader & Plugin_Upgrader also trigger this, as they pass the
		 * destination directory (WP_PLUGIN_DIR / wp-content/themes) intending
		 * to copy the directory into the directory, whilst they pass the source
		 * as the actual files to copy.
		 */
		$protected_directories = $this->generate_protected_directories( $wp_theme_directories );

		if ( in_array( $destination, $protected_directories, true ) ) {
			$remote_destination = trailingslashit( $remote_destination ) . trailingslashit( basename( $source ) );
			$destination        = trailingslashit( $destination ) . trailingslashit( basename( $source ) );
		}

		if ( $clear_destination ) {
			// We're going to clear the destination if there's something there.
			// $this->skin->feedback( 'remove_old' );

			$removed = $this->clear_destination( $remote_destination );

			/**
			 * Filter whether the upgrader cleared the destination.
			 *
			 * @since 2.8.0
			 *
			 * @param mixed  $removed            Whether the destination was cleared. true on success, WP_Error on failure
			 * @param string $local_destination  The local package destination.
			 * @param string $remote_destination The remote package destination.
			 * @param array  $hook_extra         Extra arguments passed to hooked filters.
			 */
			$removed = apply_filters( 'upgrader_clear_destination', $removed, $local_destination, $remote_destination, $args['hook_extra'] );

			if ( is_wp_error( $removed ) ) {
				return $removed;
			}
		}

		$this->delete_remote_source( $wp_filesystem, $args, $remote_source, $remote_destination );

		// Create destination if needed
		if ( ! $wp_filesystem->exists( $remote_destination ) ) {
			if ( ! $wp_filesystem->mkdir( $remote_destination, FS_CHMOD_DIR ) ) {
				return new WP_Error( 'mkdir_failed_destination', $this->strings['mkdir_failed'], $remote_destination );
			}
		}
		// Copy new version of item into place.
		$result = copy_dir( $source, $remote_destination );
		if ( is_wp_error( $result ) ) {
			$this->clear_working_dir( $wp_filesystem, $args, $remote_source );

			return $result;
		}

		// Clear the Working folder?
		$this->clear_working_dir( $wp_filesystem, $args, $remote_source );
		$destination_name = $this->get_destination_name( $local_destination, $destination );

		$this->result = compact( 'source', 'source_files', 'destination', 'destination_name', 'local_destination', 'remote_destination', 'clear_destination' );

		/**
		 * Filter the installation response after the installation has finished.
		 *
		 * @since 2.8.0
		 *
		 * @param bool  $response   Installation response.
		 * @param array $hook_extra Extra arguments passed to hooked filters.
		 * @param array $result     Installation result data.
		 */
		$res = apply_filters( 'upgrader_post_install', true, $args['hook_extra'], $this->result );

		if ( is_wp_error( $res ) ) {
			$this->result = $res;

			return $res;
		}

		// Bombard the calling function will all the info which we've just used.
		return $this->result;
	}

	/**
	 * Generate source from file system, source files
	 *
	 * @param WP_FileSystem $wp_filesystem
	 * @param array $args
	 * @param array $source_files
	 *
	 * @since 4.0.0
	 *
	 * @return string|void
	 */
	public function generate_source_from_file_or_folder( $wp_filesystem, $args, $source_files ) {
		if (
			1 === count( $source_files ) &&
			$wp_filesystem->is_dir( trailingslashit( $args['source'] ) . $source_files[0] . '/' )
		) {
			return trailingslashit( $args['source'] ) . trailingslashit( $source_files[0] );
		} elseif ( count( $source_files ) === 0 ) {
			throw new WP_Error(
				'incompatible_archive_empty',
				esc_html( $this->strings['incompatible_archive'] ),
				esc_html( $this->strings['no_files'] )
			);
		} else {
			return trailingslashit( $args['source'] );
		}
	}

	/**
	 * Check for source & destination error
	 * If found throw wp error
	 *
	 * @param mixed $source
	 * @param mixed $destination
	 * @return void
	 */
	public function check_source_and_destination_error( $source, $destination ) {
		if ( empty( $source ) || empty( $destination ) ) {
			throw new WP_Error( 'bad_request', esc_html( $this->strings['bad_request'] ) );
		}
	}

	/**
	 * Clear working directory
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $wp_filesystem
	 * @param mixed $args
	 * @param mixed $remote_source
	 *
	 * @return void
	 */
	public function clear_working_dir( $wp_filesystem, $args, $remote_source ) {
		if ( $args['clear_working'] ) {
			$wp_filesystem->delete( $remote_source, true );
		}
	}

	/**
	 * Generate protected directories
	 *
	 * @param array $wp_theme_directories
	 * @return array
	 */
	public function generate_protected_directories( $wp_theme_directories ) {
		$protected_directories = array( ABSPATH, WP_CONTENT_DIR, WP_PLUGIN_DIR, WP_CONTENT_DIR . '/themes' );

		if ( is_array( $wp_theme_directories ) ) {
			$protected_directories = array_merge( $protected_directories, $wp_theme_directories );
		}

		return $protected_directories;
	}

	/**
	 * Delete remote source file
	 *
	 * @param mixed $wp_filesystem
	 * @param array $args
	 * @param array $remote_source
	 * @param array $remote_destination
	 * @return void
	 */
	public function delete_remote_source( $wp_filesystem, $args, $remote_source, $remote_destination ) {
		if ( $args['abort_if_destination_exists'] && $wp_filesystem->exists( $remote_destination ) ) {
			$_files = $wp_filesystem->dirlist( $remote_destination );
			if ( ! empty( $_files ) ) {
				$wp_filesystem->delete( $remote_source, true );

				throw new WP_Error( 'folder_exists', esc_html( $this->strings['folder_exists'] ), esc_html( $remote_destination ) );
			}
		}
	}

	/**
	 * Get destination name
	 *
	 * @since 4.0.0
	 *
	 * @param string $local_destination
	 * @param string $destination
	 * @return string
	 */
	public function get_destination_name( $local_destination, $destination ) {
		$destination_name = basename( str_replace( $local_destination, '', $destination ) );
		if ( '.' === $destination_name ) {
			$destination_name = '';
		}

		return $destination_name;
	}
}
