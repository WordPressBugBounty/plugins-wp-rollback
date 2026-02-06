<?php

/**
 * Simple admin notices service using native WordPress functions.
 *
 * @package WpRollback\SharedCore\Core
 * @since 3.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Class AdminNotices
 *
 * Provides a simple interface for displaying admin notices using WordPress native functions.
 *
 * @since 3.0.0
 */
class AdminNotices
{
	/**
	 * Store notices to be displayed
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private static array $notices = [];

	/**
	 * Whether hooks have been registered
	 *
	 * @since 3.0.0
	 * @var bool
	 */
	private static bool $hooksRegistered = false;

	/**
	 * Display an admin notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id Unique identifier for the notice.
	 * @param string $message The notice message.
	 * @param string $type Notice type: 'error', 'warning', 'success', 'info'.
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $capability User capability required to see the notice.
	 */
	public static function add(
		string $id,
		string $message,
		string $type = 'info',
		bool $dismissible = false,
		string $capability = 'activate_plugins'
	): void {
		if ( ! self::$hooksRegistered ) {
			add_action( 'admin_notices', [ self::class, 'displayNotices' ] );
			self::$hooksRegistered = true;
		}

		self::$notices[ $id ] = [
			'message'     => $message,
			'type'        => $type,
			'dismissible' => $dismissible,
			'capability'  => $capability,
		];
	}

	/**
	 * Display a success notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id Unique identifier for the notice.
	 * @param string $message The notice message.
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $capability User capability required to see the notice.
	 */
	public static function success(
		string $id,
		string $message,
		bool $dismissible = false,
		string $capability = 'activate_plugins'
	): void {
		self::add( $id, $message, 'success', $dismissible, $capability );
	}

	/**
	 * Display an error notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id Unique identifier for the notice.
	 * @param string $message The notice message.
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $capability User capability required to see the notice.
	 */
	public static function error(
		string $id,
		string $message,
		bool $dismissible = false,
		string $capability = 'activate_plugins'
	): void {
		self::add( $id, $message, 'error', $dismissible, $capability );
	}

	/**
	 * Display a warning notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id Unique identifier for the notice.
	 * @param string $message The notice message.
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $capability User capability required to see the notice.
	 */
	public static function warning(
		string $id,
		string $message,
		bool $dismissible = false,
		string $capability = 'activate_plugins'
	): void {
		self::add( $id, $message, 'warning', $dismissible, $capability );
	}

	/**
	 * Display an info notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id Unique identifier for the notice.
	 * @param string $message The notice message.
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @param string $capability User capability required to see the notice.
	 */
	public static function info(
		string $id,
		string $message,
		bool $dismissible = false,
		string $capability = 'activate_plugins'
	): void {
		self::add( $id, $message, 'info', $dismissible, $capability );
	}

	/**
	 * Display all registered notices.
	 *
	 * @since 3.0.0
	 */
	public static function displayNotices(): void
	{
		foreach ( self::$notices as $id => $notice ) {
			// Check user capability
			if ( ! empty( $notice['capability'] ) && ! current_user_can( $notice['capability'] ) ) {
				continue;
			}

			$class = 'notice notice-' . esc_attr( $notice['type'] );
			if ( $notice['dismissible'] ) {
				$class .= ' is-dismissible';
			}

			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				esc_attr( $class ),
				wp_kses_post( $notice['message'] )
			);
		}
	}

	/**
	 * Clear all notices.
	 *
	 * @since 3.0.0
	 */
	public static function clear(): void
	{
		self::$notices = [];
	}

	/**
	 * Remove a specific notice by ID.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id The notice ID to remove.
	 */
	public static function remove( string $id ): void
	{
		if ( isset( self::$notices[ $id ] ) ) {
			unset( self::$notices[ $id ] );
		}
	}
}
