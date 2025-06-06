<?php
$pharFile = 'app.phar';

// Clean up existing phar
if (file_exists($pharFile)) {
    unlink($pharFile);
}

// First, make sure the Rust library is built
$libExtension = PHP_OS_FAMILY === 'Windows' ? 'dll' : (PHP_OS === 'Darwin' ? 'dylib' : 'so');
$libFile = "lib/librust_image_converter.$libExtension";

if (!file_exists($libFile)) {
    echo "Building Rust library...\n";
    system('make');
    if (!file_exists($libFile)) {
        die("Failed to build Rust library. Run 'make' first.\n");
    }
}

// Create phar
$phar = new Phar($pharFile);
$phar->startBuffering();

// Add PHP files
$phar->buildFromDirectory(__DIR__, '/\.php$/');

// Add the library file as binary data
$phar->addFile($libFile, "lib/librust_image_converter.$libExtension");

// Add other necessary files
if (file_exists('README.md')) $phar->addFile('README.md');

// Create a bootstrap that extracts the library
$bootstrap = '#!/usr/bin/env php
<?php
Phar::mapPhar();

// Get the correct library extension for this platform
$libExtension = PHP_OS_FAMILY === "Windows" ? "dll" : (PHP_OS === "Darwin" ? "dylib" : "so");

// Extract the library to a temporary location
$tempDir = sys_get_temp_dir() . "/image_converter_" . md5(__FILE__);
$libPath = $tempDir . "/librust_image_converter.$libExtension";

if (!file_exists($libPath)) {
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $pharLibPath = "phar://" . __FILE__ . "/lib/librust_image_converter.$libExtension";
    if (!file_exists($pharLibPath)) {
        die("Library not found in phar archive\n");
    }
    
    $libData = file_get_contents($pharLibPath);
    if ($libData === false) {
        die("Failed to read library from phar\n");
    }
    
    if (file_put_contents($libPath, $libData) === false) {
        die("Failed to extract library to temp directory\n");
    }
    chmod($libPath, 0755);
}

// Verify the library exists and is readable
if (!file_exists($libPath) || !is_readable($libPath)) {
    die("Library not accessible at: $libPath\n");
}

// Set environment variable for the library path
putenv("RUST_LIB_PATH=" . $libPath);

// Also try setting it as a global for PHP scripts
$_ENV["RUST_LIB_PATH"] = $libPath;

// Include the main application
require "phar://" . __FILE__ . "/index.php";

__HALT_COMPILER();
';

$phar->setStub($bootstrap);
$phar->stopBuffering();

// Make executable
chmod($pharFile, 0755);

echo "Phar created: $pharFile\n";

// Create symlink for easier access
$symlinkName = 'image-converter';
if (file_exists($symlinkName) || is_link($symlinkName)) {
    unlink($symlinkName);
}

if (symlink($pharFile, $symlinkName)) {
    echo "Symlink created: $symlinkName -> $pharFile\n";
    chmod($symlinkName, 0755);
} else {
    echo "Warning: Could not create symlink. You may need to run as administrator/root.\n";
    echo "You can manually create it with: ln -s $pharFile $symlinkName\n";
}

echo "The Rust library will be extracted to temp directory on first run.\n";
echo "Usage: ./$symlinkName or php $pharFile\n";
?>
