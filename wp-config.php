<?php
define( 'DB_NAME', 'khzxauntestmcp' );
define( 'DB_USER', 'khzxauntestmcp' );
define( 'DB_PASSWORD', 'Kansei2026' );
define( 'DB_HOST', 'khzxauntestmcp.mysql.db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define('AUTH_KEY',         '9WD6E;,gi,cvSy5}|mB~XqEM}+8y;!-!-ul~n$R|+;q~-._09y=fD|6)>EeprGGC');
define('SECURE_AUTH_KEY',  '[ 4:Kmb:zUVi=6|=ARO7KkdFzioRP%|:3Xfg|f5(0HQ~;-eVH(Su%.fr>h(W(2|c');
define('LOGGED_IN_KEY',    'TkCwbNqSFOhB7E+32p$7H%K[RN>|]t5`n(MHZ)|zDlq&ZAK ],82_V$]#6b$0(f~');
define('NONCE_KEY',        'r1+f|}bS*~]l|u>1*L,ulwUEz?N/UM<gD=S)|1n69D581X&[aeoHV[[HH}(F]>T}');
define('AUTH_SALT',        'V49vG @*sZA%x,;J+w(GN,r%9DLium TC7^$-jt:Kf_,tJenBwc3?-O_>9k.r;TB');
define('SECURE_AUTH_SALT', 'XJ<XA-]$z%hM=AbWLu Rj+/xcNe@0RvgNMNu]&Ch)i(el_<csEYl?p3@OV@.gi-p');
define('LOGGED_IN_SALT',   '+<2X7w0W m;3IViFa(U;;p$p2:s8TxSeb2Y!_j,FZr?)R+u4uZ$<JU)ydN^NOZPS');
define('NONCE_SALT',       '&54#t^-cdLG!*Ch.<J92>aT|T=HT6T|k=3V/old|hQN$y 1z(<HM1tToFw~<@Pnb');

$table_prefix = 'wp_';

define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
