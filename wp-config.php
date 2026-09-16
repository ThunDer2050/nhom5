<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'nhom5' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'B7hbbb^kU5~2WZQsfVo{UV;,4_X v%:g~){5u%Q7rDdz~j`q!U1ldQ+qobVd55Jr' );
define( 'SECURE_AUTH_KEY',  'rq=sW40>(Dmf[/2Fm<JsGKqL8I?Y)IDSu5TNcAhB(hr$>MD+w(u|AQ&S#k7lCMA=' );
define( 'LOGGED_IN_KEY',    'xiI}BHvVuM;h@~-5}F~u@]~[}3BEF:Z/*xD&gc.BPCq q6z1|W)c$?uiK0*T8RB`' );
define( 'NONCE_KEY',        'H1Hnjd)F~S(l?5pABNiZvyQel{=02lOE5MU=~`Z{6m[!Z&-]RU?#7z0.Ebi8GM[D' );
define( 'AUTH_SALT',        '&-j]j_*%@H.V=igpL`6d7*`F-YS_KMxP>bg~g-8tPsMaY}h,64 z=tRZGd9FJo)2' );
define( 'SECURE_AUTH_SALT', 'n7ECG&fjZb-EnI N7x,%jP(dhFW8r7P%5beZ0P)huOrh|8abKSDxvnfRBM>FTU}A' );
define( 'LOGGED_IN_SALT',   '>zn`+3e-!F6{Q09 ,,Gm~).U|B6([/fQ3q;l=dy[r(f-o_+1+|}7-cqSx,s,](!E' );
define( 'NONCE_SALT',       '<5ae#qy)LM;I#vu57k1T7BU,]nKJ+2ODxyz1@e}?pi~d?hzw8|~~*Wo[UeN<01Nb' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
