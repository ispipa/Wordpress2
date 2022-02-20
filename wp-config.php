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

 * * ABSPATH

 *

 * @link https://wordpress.org/support/article/editing-wp-config-php/

 *

 * @package WordPress

 */


// ** Database settings - You can get this info from your web host ** //

/** The name of the database for WordPress */

define( 'DB_NAME', "wp2" );


/** Database username */

define( 'DB_USER', "root" );


/** Database password */

define( 'DB_PASSWORD', "" );


/** Database hostname */

define( 'DB_HOST', "localhost" );


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

define( 'AUTH_KEY',         '-ux&2<&o2=&e+&[.qBtAp,p>NiK-_5Ya<T/Lz`CAql66lV.=m47gV>SQHLSQr%f3' );

define( 'SECURE_AUTH_KEY',  '%au>F3xD[uH]V(#h5S8$x,c4s>j[Dz_2P}&3O)02pQ1tdds-)~hnob5blCS :w)0' );

define( 'LOGGED_IN_KEY',    ']{q4kP^+3,p<Zo.eVq0_,<$*E)Q&vQ?A_>/3/l!iz<+qbAp#Pq*1S7}4(SMrZroY' );

define( 'NONCE_KEY',        'Q;Ue^C}1oKx>!u+=fcX]v{d>:R%5,UqxD~6<0,amBFctHIiB`zncMc-lCErx EeL' );

define( 'AUTH_SALT',        'l=W?PL67EaGz*f.:S6HUBl%>)E]$*yJpx5Rnvle9K`T-I}vz@qEoK!#}wDVEY;cO' );

define( 'SECURE_AUTH_SALT', 'T)rUp[uJ>{tpG6nL.`ntW5/,o*^@j~k<^>(phJK6%VkD^#WPbU!+cVkt@xZp(A/V' );

define( 'LOGGED_IN_SALT',   'mw_hPj#p4eA5e$1HYA=I0Sa9I .=(LHMB|6*n#Q!yMq5X@/3.D]J,jD)Rsu.oeG~' );

define( 'NONCE_SALT',       '_f3/,%a:-hYmlET+N<SA*K{4J6IR`;69u=Oyy^8:?~w=7DCCBz<QU5:0E<|,%<)^' );


/**#@-*/


/**

 * WordPress database table prefix.

 *

 * You can have multiple installations in one database if you give each

 * a unique prefix. Only numbers, letters, and underscores please!

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

 * @link https://wordpress.org/support/article/debugging-in-wordpress/

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

