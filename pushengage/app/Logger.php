<?php
/**
 * Logger class for WordPress plugin error logging
 *
 * @package PushEngage
 * @since 4.1.2
 */

namespace PushEngage;

/**
 * Logger class for handling error messages with different log levels.
 *
 * It will write the log to the custom log file if WP_PUSHENGAGE_DEBUG is
 * enabled and to the WordPress debug log if WP_DEBUG is enabled.
 *
 * NOTE:
 * Do not log sensitive data to this logger as the log file is not
 * protected. The log file can be accessed by anyone via browser.
 *
 * A user should only enable the file logging if they are sure that the
 * log file is not accessible by anyone or asked by the PushEngage support
 * team. They should disable the file logging after the issue is resolved
 * and delete the log files.
 *
 *
 * @since 4.1.2
 */
class Logger {

	/**
	 * Singleton instance
	 *
	 * @var Logger
	 */
	private static $instance = null;

	/**
	 * Log levels constants
	 */
	const LOG_LEVEL_DEBUG   = 'debug';
	const LOG_LEVEL_INFO    = 'info';
	const LOG_LEVEL_WARNING = 'warning';
	const LOG_LEVEL_ERROR   = 'error';
	const LOG_LEVEL_FATAL   = 'fatal';

	private $log_levels = array(
		self::LOG_LEVEL_DEBUG   => 0,
		self::LOG_LEVEL_INFO    => 1,
		self::LOG_LEVEL_WARNING => 2,
		self::LOG_LEVEL_ERROR   => 3,
		self::LOG_LEVEL_FATAL   => 4,
	);

	/**
	 * Maximum log file size in bytes (5MB)
	 */
	const MAX_LOG_FILE_SIZE = 5242880; // 5MB in bytes

	/**
	 * Maximum number of backup log files to keep
	 */
	const MAX_BACKUP_FILES = 5;

	/**
	 * Plugin name for logging context
	 *
	 * @var string
	 */
	private $plugin_name = 'PushEngage';

	/**
	 * Minimum log level to record
	 *
	 * @var string
	 */
	private $min_log_level = self::LOG_LEVEL_ERROR;

	/**
	 * Whether logging to custom log file is disabled
	 *
	 * @var bool
	 */
	private $enable_custom_log = false;

	/**
	 * Whether custom log files can be written
	 *
	 * @var bool
	 */
	private $can_write_custom_log = false;

	/**
	 * Private constructor to prevent direct instantiation
	 *
	 * @since 4.1.2
	 */
	private function __construct() {

		// We are checking for WP_PUSHENGAGE_DEBUG_LEVEL to set the minimum
		// log level.
		// TODO: Give an option in misc settings UI to set the minimum log level.
		if ( defined( 'WP_PUSHENGAGE_DEBUG_LEVEL' ) && $this->is_valid_log_level( \WP_PUSHENGAGE_DEBUG_LEVEL ) ) {
			$this->min_log_level = \WP_PUSHENGAGE_DEBUG_LEVEL;
		}

		// We are checking for WP_PUSHENGAGE_DEBUG to enable custom log file.
		// TODO: Give an option in misc settings UI to enable/disable custom
		// log file.
		if ( defined( 'WP_PUSHENGAGE_DEBUG' ) ) {
			$this->enable_custom_log = (bool) \WP_PUSHENGAGE_DEBUG;
		}

		// If logging to custom log file is not disabled, then initialize log
		// directory and rotate log if needed.
		if ( $this->enable_custom_log ) {
			// Initialize log directory first
			$this->initialize_log_directory();

			// Rotate log if custom logging is available
			if ( $this->can_write_custom_log ) {
				$this->rotate_log_if_needed();
			}
		}
	}

	/**
	 * Get singleton instance
	 *
	 * @since 4.1.2
	 * @return Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @since 4.1.2
	 * @return void
	 */
	public function __wakeup() {
		// Prevent unserializing.
	}

	/**
	 * Log a debug message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function debug( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function info( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_INFO, $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function warning( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log an error message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function error( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_ERROR, $message, $context );
	}

	/**
	 * Log a fatal error message
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	public function fatal( $message, $context = array() ) {
		$this->log( self::LOG_LEVEL_FATAL, $message, $context );
	}

	/**
	 * Main logging method
	 *
	 * @since 4.1.2
	 * @param string $level   The log level.
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return void
	 */
	private function log( $level, $message, $context = array() ) {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = $this->format_log_entry( $message, $context );
		$this->write_log( $level, $log_entry );
	}

	/**
	 * Check if the log level should be recorded
	 *
	 * @since 4.1.2
	 * @param string $level The log level to check.
	 * @return bool
	 */
	private function should_log( $level ) {
		// If custom log is disabled and WP_DEBUG is also not enabled, then don't log.
		if ( ! $this->enable_custom_log && ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return false;
		}

		$current_level = $this->log_levels[ $level ];
		$min_level     = $this->log_levels[ $this->min_log_level ];

		return $current_level >= $min_level;
	}

	/**
	 * Format the log entry
	 *
	 * @since 4.1.2
	 * @param string $message The message to log.
	 * @param \Exception|\WP_Error|array $context Additional context data
	 * @return string
	 */
	private function format_log_entry( $message, $context = array() ) {
		$log_entry = sanitize_text_field( $message );
		// Handle context based on its type
		if ( ! empty( $context ) ) {
			if ( $context instanceof \Exception ) {
				$log_entry .= $this->format_exception_context( $context );
			} elseif ( is_wp_error( $context ) ) {
				$log_entry .= $this->format_wp_error_context( $context );
			} elseif ( is_array( $context ) ) {
				$log_entry .= $this->format_array_context( $context );
			}
		}

		return $log_entry;
	}

	/**
	 * Format exception context for logging
	 *
	 * @since 4.1.2
	 * @param \Exception $exception The exception object.
	 * @return string
	 */
	private function format_exception_context( $exception ) {
		$context_data = array(
			'exception_class' => get_class( $exception ),
			'message'         => $exception->getMessage(),
			'code'            => $exception->getCode(),
			'file'            => $exception->getFile(),
			'line'            => $exception->getLine(),
			// 'trace'           => $exception->getTraceAsString(),
		);

		// Add previous exception if exists
		if ( $exception->getPrevious() ) {
			$context_data['previous_exception'] = array(
				'class'   => get_class( $exception->getPrevious() ),
				'message' => $exception->getPrevious()->getMessage(),
				'code'    => $exception->getPrevious()->getCode(),
			);
		}

		return ' Exception: ' . wp_json_encode( $context_data );
	}

	/**
	 * Format WP_Error context for logging
	 *
	 * @since 4.1.2
	 * @param \WP_Error $wp_error The WP_Error object.
	 * @return string
	 */
	private function format_wp_error_context( $wp_error ) {
		$context_data = array(
			'error_code'    => $wp_error->get_error_code(),
			'error_message' => $wp_error->get_error_message(),
			'error_data'    => $wp_error->get_error_data(),
		);

		// Get all error codes and messages
		$error_codes = $wp_error->get_error_codes();
		if ( count( $error_codes ) > 1 ) {
			$context_data['all_errors'] = array();
			foreach ( $error_codes as $code ) {
				$context_data['all_errors'][ $code ] = array(
					'message' => $wp_error->get_error_message( $code ),
					'data'    => $wp_error->get_error_data( $code ),
				);
			}
		}

		return ' WP_Error: ' . wp_json_encode( $context_data );
	}

	/**
	 * Format array context for logging
	 *
	 * @since 4.1.2
	 * @param array $context The context array.
	 * @return string
	 */
	private function format_array_context( $context ) {
		$context_json = wp_json_encode( $context );

		if ( false !== $context_json ) {
			return ' Context: ' . $context_json;
		}
		return '';
	}

	/**
	 * Write the log entry to the appropriate destination
	 *
	 * @since 4.1.2
	 * @param string $log_entry The formatted log entry.
	 * @return void
	 */
	private function write_log( $level, $log_entry ) {
		$level = strtoupper( $level );

		// Use WordPress debug log if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_entry = sprintf( '[%s] [%s] %s', $this->plugin_name, $level, $log_entry );
			error_log( $log_entry );
		}

		// only write to custom log file if custom log is enabled.
		if ( $this->enable_custom_log ) {
			$timestamp = current_time( 'Y-m-d H:i:s', true );
			$log_entry = sprintf( '[%s] [%s] %s', $timestamp, $level, $log_entry );
			$log_entry .= PHP_EOL;
			$this->write_to_custom_log_file( $log_entry );
		}
	}

	/**
	 * Create empty index.html file in directory to prevent directory listing
	 *
	 * @since 4.1.2
	 * @param string $directory The directory path where to create index.html.
	 * @return bool True if file was created or already exists, false on failure.
	 */
	private function create_index_html( $directory ) {
		$index_file = $directory . '/index.html';
		if ( ! file_exists( $index_file ) ) {
			$created = file_put_contents( $index_file, '' );
			return false !== $created;
		}
		return true;
	}

	/**
	 * Initialize log directory
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function initialize_log_directory() {
		$log_dir = $this->get_log_dir_path();

		// Create log directory if it doesn't exist.
		if ( ! file_exists( $log_dir ) ) {
			$created = wp_mkdir_p( $log_dir );
			if ( ! $created ) {
				$this->can_write_custom_log = false;
				return;
			}
			$this->create_index_html( $log_dir );
		}

		$log_file = $this->get_log_file_path();
		if ( ! file_exists( $log_file ) ) {
			$bytes_written = file_put_contents( $log_file, '' );
			if ( false === $bytes_written ) {
				$this->can_write_custom_log = false;
				return;
			}
		}
		// Check if log file is writable
		if ( ! is_writable( $log_file ) ) {
			$this->can_write_custom_log = false;
			return;
		}

		$this->can_write_custom_log = true;
	}

	/**
	 * Write log entry to custom log file
	 *
	 * @since 4.1.2
	 * @param string $log_entry The formatted log entry.
	 * @return void
	 */
	private function write_to_custom_log_file( $log_entry ) {
		// Check if we can write to custom log files and directory is ready
		if ( $this->enable_custom_log && $this->can_write_custom_log ) {
			$log_file = $this->get_log_file_path();
			file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
		}
	}

	/**
	 * Rotate log file if it exceeds maximum size
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function rotate_log_if_needed() {
		// Check if log file exists
		$log_file = $this->get_log_file_path();
		if ( ! file_exists( $log_file ) ) {
			return;
		}

		$file_size = filesize( $log_file );
		if ( $file_size < self::MAX_LOG_FILE_SIZE ) {
			return;
		}

		// Move current log file to backup with timestamp
		$timestamp = gmdate( 'Y-m-d\TH-i-s' );
		$backup_file = $this->get_log_dir_path() . '/debug-log-' . $timestamp . '.txt';

		// Try to rename the file, if it fails, just continue
		$renamed = rename( $log_file, $backup_file );
		if ( ! $renamed ) {
			// If rename fails, try to copy and delete
			$copied = copy( $log_file, $backup_file );
			if ( $copied ) {
				file_put_contents( $log_file, '' );
			}
		}

		// Ensure debug-log.txt file exists after rotation
		if ( ! file_exists( $log_file ) ) {
			file_put_contents( $log_file, '' );
		}

		// Delete old backup files
		$this->delete_old_backup_files();
	}

	/**
	 * Delete old backup log files
	 *
	 * @since 4.1.2
	 * @return void
	 */
	private function delete_old_backup_files() {
		$log_dir = $this->get_log_dir_path();

		// Get all existing backup files
		$backup_files = glob( $log_dir . '/debug-log-*.txt' );

		// Sort files by modification time (oldest first)
		usort(
			$backup_files,
			function( $a, $b ) {
				return filemtime( $a ) - filemtime( $b );
			}
		);

		// Remove oldest files if we exceed the limit
		$files_to_remove = count( $backup_files ) - self::MAX_BACKUP_FILES + 1;
		if ( $files_to_remove > 0 ) {
			for ( $i = 0; $i < $files_to_remove; $i++ ) {
				if ( isset( $backup_files[ $i ] ) ) {
					// Try to delete, but don't fail if it doesn't work
					@unlink( $backup_files[ $i ] );
				}
			}
		}
	}

	/**
	 * Check if the log level is valid
	 *
	 * @since 4.1.2
	 * @param string $level The log level to check.
	 * @return bool
	 */
	private function is_valid_log_level( $level ) {
		$valid_levels = array(
			self::LOG_LEVEL_DEBUG,
			self::LOG_LEVEL_INFO,
			self::LOG_LEVEL_WARNING,
			self::LOG_LEVEL_ERROR,
			self::LOG_LEVEL_FATAL,
		);
		return in_array( $level, $valid_levels, true );
	}

	/**
	 * Get log directory path
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_log_dir_path() {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/pushengage/logs';
		return $log_dir;
	}

	/**
	 * Get log file path
	 *
	 * @since 4.1.2
	 * @return string
	 */
	public function get_log_file_path() {
		return $this->get_log_dir_path() . '/debug-log.txt';
	}

	/**
	 * Get log file size in bytes
	 *
	 * @since 4.1.2
	 * @return int
	 */
	public function get_log_file_size() {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) ) {
			return 0;
		}

		return filesize( $log_file );
	}
}
