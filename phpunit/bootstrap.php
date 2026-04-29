<?php
/**
 * PHPUnit bootstrap file for the WordPress Importer
 *
 * @package Sample_Plugin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Check if we're installed in a src checkout.
$pos = stripos( __FILE__, '/src/wp-content/plugins/' );
if ( ! $_tests_dir && false !== $pos ) {
	$_tests_dir = substr( __FILE__, 0, $pos ) . '/tests/phpunit/';
}

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	exit( 1 );
}

define( 'WP_LOAD_IMPORTERS', true );

define( 'DIR_TESTDATA_WP_IMPORTER', __DIR__ . '/data' );

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the importer
 */
function _manually_load_importer() {
	if ( ! class_exists( 'WP_Import' ) ) {
		require dirname( __DIR__ ) . '/src/wordpress-importer.php';
	}
}
tests_add_filter( 'plugins_loaded', '_manually_load_importer' );

// Include the PHPUnit Polyfills autoloader.
require dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Define WP_TESTS_PHPUNIT_POLYFILLS_PATH for WP 5.9+ test suites.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/' );
}

// For WP < 5.9 test suites: the bootstrap.php has a hard PHPUnit version check
// that rejects PHPUnit 8+ and exits BEFORE checking for polyfills. Patch it at
// runtime by commenting out the exit call in the version gate block.
$_wp_bootstrap = $_tests_dir . '/includes/bootstrap.php';
if ( file_exists( $_wp_bootstrap ) ) {
	$_wp_bootstrap_contents = file_get_contents( $_wp_bootstrap );
	if ( false !== strpos( $_wp_bootstrap_contents, "only compatible with PHPUnit up to 7.x" )
		&& false === strpos( $_wp_bootstrap_contents, 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' )
	) {
		// Remove the version gate that rejects PHPUnit 8+.
		$_wp_bootstrap_contents = preg_replace(
			'/\bif\s*\(\s*version_compare\s*\(\s*\$phpunit_version\s*,\s*[\'"]5\.7\.21[\'"]\s*,\s*[\'"]<[\'"]\s*\)\s*\|\|\s*version_compare\s*\(\s*\$phpunit_version\s*,\s*[\'"]8\.0[\'"]\s*,\s*[\'"]>=[\'"]\s*\)\s*\)\s*\{[^}]+\}/s',
			'/* PHPUnit version gate removed by wordpress-importer - using Yoast PHPUnit Polyfills instead. */',
			$_wp_bootstrap_contents
		);
		file_put_contents( $_wp_bootstrap, $_wp_bootstrap_contents );
	}
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
