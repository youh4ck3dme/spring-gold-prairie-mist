<?php
/**
 * Test Level 14: PclZip & WordPress Core Archive Extraction Strict Acceptance Test
 * 
 * Verifies that the distribution ZIP satisfies:
 * 1. Strict zero-warning PclZip central directory parsing (class-pclzip.php on PHP 8.x).
 * 2. Strict zero-warning PCLZIP_OPT_EXTRACT_AS_STRING in-memory decompression.
 * 3. Strict zero-warning PCLZIP_OPT_PATH filesystem extraction.
 * 4. 100% ZipArchive::CHECKCONS integrity check.
 * 5. Complete presence and integrity of all plugin entrypoints and metadata.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

// Convert ANY PHP warning/notice into an exception
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    throw new ErrorException( "$errstr in $errfile on line $errline", $errno );
} );

echo "====================================================================\n";
echo "🧪 TEST LEVEL 14: Strict PclZip & WordPress Core Archive Extraction\n";
echo "====================================================================\n";

$zip_path = dirname( __DIR__, 2 ) . '/vorkei-statement-bridge.zip';
if ( ! file_exists( $zip_path ) ) {
    echo "❌ FATAL: ZIP file not found at: $zip_path\n";
    exit( 1 );
}

echo "📦 Target ZIP: $zip_path\n";
echo "   Size: " . number_format( filesize( $zip_path ) ) . " bytes\n";
echo "   SHA-256: " . hash_file( 'sha256', $zip_path ) . "\n\n";

// 1. Ensure PclZip is available
$pclzip_candidates = [
    '/tmp/class-pclzip-66.php', // WordPress 6.6
    '/tmp/class-pclzip.php',    // WordPress latest
];

$pclzip_loaded = false;
foreach ( $pclzip_candidates as $pclzip_file ) {
    if ( file_exists( $pclzip_file ) ) {
        require_once $pclzip_file;
        $pclzip_loaded = true;
        echo "✓ Loaded PclZip from: $pclzip_file\n";
        break;
    }
}

if ( ! $pclzip_loaded ) {
    echo "❌ FATAL: class-pclzip.php not found for testing.\n";
    exit( 1 );
}

try {
    // 2. Instantiate PclZip
    $archive = new PclZip( $zip_path );

    // 3. Properties check
    echo "▶ Checking archive properties via PclZip...\n";
    $props = $archive->properties();
    if ( ! is_array( $props ) || $props['status'] !== 'ok' ) {
        throw new Exception( "PclZip properties() failed: " . $archive->errorInfo( true ) );
    }
    echo "✓ Archive properties OK: {$props['nb']} entries detected, status: {$props['status']}\n";

    // 4. In-Memory Extraction (PCLZIP_OPT_EXTRACT_AS_STRING) - WordPress Upgrader uses this!
    echo "▶ Testing in-memory extraction (PCLZIP_OPT_EXTRACT_AS_STRING)...\n";
    $files_as_string = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );
    if ( ! is_array( $files_as_string ) || count( $files_as_string ) === 0 ) {
        throw new Exception( "PclZip extract(PCLZIP_OPT_EXTRACT_AS_STRING) failed: " . $archive->errorInfo( true ) );
    }
    echo "✓ Successfully extracted " . count( $files_as_string ) . " entries as string without any PHP warnings!\n";

    // 5. Filesystem Extraction (PCLZIP_OPT_PATH)
    $temp_dir = sys_get_temp_dir() . '/vorkei_strict_test_' . bin2hex( random_bytes( 6 ) );
    @mkdir( $temp_dir, 0777, true );
    echo "▶ Testing filesystem extraction to $temp_dir...\n";
    $extracted = $archive->extract( PCLZIP_OPT_PATH, $temp_dir );
    if ( ! is_array( $extracted ) || count( $extracted ) === 0 ) {
        throw new Exception( "PclZip extract(PCLZIP_OPT_PATH) failed: " . $archive->errorInfo( true ) );
    }
    echo "✓ Successfully extracted " . count( $extracted ) . " files to filesystem!\n";

    // 6. Verify Critical Files & Structure
    $required_files = [
        'vorkei-statement-bridge/vorkei-statement-bridge.php',
        'vorkei-statement-bridge/readme.txt',
        'vorkei-statement-bridge/README.md',
        'vorkei-statement-bridge/uninstall.php',
        'vorkei-statement-bridge/includes/class-vorkei-activator.php',
        'vorkei-statement-bridge/includes/class-vorkei-deactivator.php',
        'vorkei-statement-bridge/includes/class-vorkei-matcher.php',
        'vorkei-statement-bridge/includes/class-vorkei-rest-controller.php',
        'vorkei-statement-bridge/includes/class-vorkei-updater.php',
        'vorkei-statement-bridge/admin/class-vorkei-admin.php',
        'vorkei-statement-bridge/admin/css/vorkei-admin.css',
        'vorkei-statement-bridge/languages/vorkei-bridge.pot',
    ];

    echo "▶ Verifying extracted file integrity...\n";
    foreach ( $required_files as $req ) {
        $full = "$temp_dir/$req";
        if ( ! file_exists( $full ) ) {
            throw new Exception( "Missing required file in extracted archive: $req" );
        }
        if ( filesize( $full ) === 0 ) {
            throw new Exception( "Extracted file is 0 bytes: $req" );
        }
    }
    echo "✓ All " . count( $required_files ) . " required files present and non-empty.\n";

    // 7. Verify main plugin file syntax
    $main_file = "$temp_dir/vorkei-statement-bridge/vorkei-statement-bridge.php";
    $syntax_output = shell_exec( 'php -l ' . escapeshellarg( $main_file ) );
    if ( strpos( $syntax_output, 'No syntax errors detected' ) === false ) {
        throw new Exception( "PHP syntax error in extracted plugin: $syntax_output" );
    }
    echo "✓ Main plugin PHP syntax check passed: No errors detected.\n";

    // 8. Test ZipArchive CHECKCONS if extension is available
    if ( class_exists( 'ZipArchive' ) ) {
        echo "▶ Testing with native ZipArchive::CHECKCONS...\n";
        $za = new ZipArchive();
        $za_res = $za->open( $zip_path, ZipArchive::CHECKCONS );
        if ( $za_res !== true ) {
            throw new Exception( "ZipArchive::CHECKCONS failed with code: $za_res" );
        }
        $za->close();
        echo "✓ ZipArchive::CHECKCONS passed.\n";
    }

    // Cleanup
    shell_exec( 'rm -rf ' . escapeshellarg( $temp_dir ) );

    echo "\n====================================================================\n";
    echo "🎉 RESULT: 100% PASS - PclZip and ZipArchive compatibility confirmed!\n";
    echo "====================================================================\n";
    exit( 0 );

} catch ( Throwable $t ) {
    echo "\n❌ TEST FAILED: " . $t->getMessage() . "\n";
    if ( isset( $temp_dir ) && is_dir( $temp_dir ) ) {
        shell_exec( 'rm -rf ' . escapeshellarg( $temp_dir ) );
    }
    exit( 1 );
}
