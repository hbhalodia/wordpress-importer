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

// Patch old WP test suites (< 5.9) that reject PHPUnit 8+.
// WP 5.9+ checks WP_TESTS_PHPUNIT_POLYFILLS_PATH and skips the version gate.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/' );
}

// For WP < 5.9 test suites: the compat.php has a hard PHPUnit version gate
// that calls exit(1) for PHPUnit 8+. Comment out exit() calls to let the
// Yoast polyfills handle compatibility, while preserving any compat shims.
$_compat_file = $_tests_dir . '/includes/phpunit6/compat.php';
if ( file_exists( $_compat_file ) ) {
	$_compat_contents = file_get_contents( $_compat_file );
	if ( false === strpos( $_compat_contents, 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && false !== strpos( $_compat_contents, 'exit' ) ) {
		$_compat_contents = str_replace( 'exit( 1 );', '// exit( 1 );', $_compat_contents );
		$_compat_contents = str_replace( 'exit(1);', '// exit(1);', $_compat_contents );
		file_put_contents( $_compat_file, $_compat_contents );
	}
}

// Provide a stub for PHPUnit\Util\Getopt which was removed in PHPUnit 9.x
// but referenced by old WP test suite compat files.
if ( ! class_exists( 'PHPUnit\Util\Getopt' ) ) {
	// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
	class PHPUnit_Util_Getopt_Stub {}
	class_alias( 'PHPUnit_Util_Getopt_Stub', 'PHPUnit\Util\Getopt' );
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
