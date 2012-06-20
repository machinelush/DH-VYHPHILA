<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'vyhphila_org');

/** MySQL database username */
define('DB_USER', 'vyhphilaorg');

/** MySQL database password */
define('DB_PASSWORD', 'uw6!Mte^');

/** MySQL hostname */
define('DB_HOST', 'mysql.vyhphila.org');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'GGS9G"Y"FpgNlzcj@j3)4&d9UT$*dJt@zWwdsW$xXRMDOw^1nI(/uBC%Dcml$|`9');
define('SECURE_AUTH_KEY',  'ybMJEKYS~h7o:P)ocpEm42?jN8GaVPx(T@Qw(|ON/DiuIC9*/Pvqus7%it2q9S8i');
define('LOGGED_IN_KEY',    ')Zq9r4kq8m8XkJH_:)G2Fn4HI6NqblwlZbVKFR_I$?rP;?aPH&bOJ&i6x(zm8zLp');
define('NONCE_KEY',        'Wa8R(6(B+*D_H8"p3W&`"Rjf7y263YN2moDon;$!?qPHDMWu8LbjDkqP$!Fzm_XJ');
define('AUTH_SALT',        'Hf62$4D0NdxVCaJ`;ZnGbf4^uf`beuOI0:dI71dGgjNrRcB8*#/CN2tC_K5c8q#D');
define('SECURE_AUTH_SALT', 'IibUs5~4*ERi@8vuduVU9SOk~25Zpj$h?r9mtZ%EDHPeAa~a2Gg$y`3#x(xJAgns');
define('LOGGED_IN_SALT',   '4wj*X;MzOC(CX8Q7waWijsm"x*p40X$Qq(3/x/FFaw+f"R?Qxmw:Y:O%gS!Gd*W"');
define('NONCE_SALT',       'zA3?@v3I0w^8xKr9m|10sECd?(*mZdVx7a$40^SGYaev5lLR3Uzhy/~+et@h:b_r');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_d6tx2w_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

