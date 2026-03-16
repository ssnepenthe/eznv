<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
/** Not used directly by sqlite integration, but may be needed by WordPress */
define( 'DB_NAME', 'wordpress' );

/** The directory where your sqlite database will be stored */
// define( 'DB_DIR', '/custom/path/to/db/dir/' );

/** The name of your sqlite database file */
// define( 'DB_FILE', 'custom-db-file-name.sqlite' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'SECURE_AUTH_KEY',   'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'LOGGED_IN_KEY',     'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'NONCE_KEY',         'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'AUTH_SALT',         'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'SECURE_AUTH_SALT',  'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'LOGGED_IN_SALT',    'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );
define( 'NONCE_SALT',        'change-me-plzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/* Add any custom values between this line and the "stop editing" line. */

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', true );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
}

if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', 'http://localhost:8080/wp-content' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
