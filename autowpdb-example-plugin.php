<?php
/**
 * Plugin Name: AutoWPDB Example Plugin
 * Plugin URI: https://github.com/Screenfeed/autowpdb-example-plugin
 * Description: A plugin showing how to use Screenfeed/AutoWPDB.
 * Version: 0.2
 * Requires PHP: 7.0
 * Author: Grégory Viguier
 * Author URI: https://www.screenfeed.fr/
 * Licence: GPLv2
 *
 * Text Domain: autowpdb-example-plugin
 * Domain Path: languages
 *
 * @package Screenfeed/autowpdb-example-plugin
 */

declare( strict_types=1 );

namespace Screenfeed\AutoWPDBExamplePlugin;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

add_action(
	'plugins_loaded',
	function() {
		$autoload_path = __DIR__ . '/vendor/autoload.php';

		if ( file_exists( $autoload_path ) ) {
			require_once $autoload_path;
		}

		( new Plugin() )->init();
	}
);
