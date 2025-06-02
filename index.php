<?php
// Check if FFI is available
if (!extension_loaded("ffi")) {
    die("FFI extension is not loaded. Please enable it in php.ini\n");
}

// Use absolute path for better reliability
$lib_path = __DIR__ . "/lib/librust_image_converter.so";

if (!file_exists($lib_path)) {
    die("Library file not found at: $lib_path\n");
}

try {
    $ffi = FFI::cdef(
        "int convert_image(const char* input_path, const char* output_path, int format_code);",
        $lib_path
    );
} catch (FFI\Exception $e) {
    die("Failed to load library: " . $e->getMessage() . "\n");
}

echo "Enter path to input image: \n";
$input = trim(fgets(STDIN));

echo "Choose output format: \n";
echo "0 => JPG\n";
echo "1 => PNG\n";
echo "2 => WEBP\n";
echo "Output format (0-2): ";
$format_code = (int) trim(fgets(STDIN));

$extMap = [
    0 => ".jpg",
    1 => ".png",
    2 => ".webp",
];

echo "Enter the output file name (no extension): ";
$output_file_name = trim(fgets(STDIN));

// Fixed validation logic
if (!array_key_exists($format_code, $extMap) || empty($output_file_name)) {
    echo "Invalid details provided\n";
    exit(1);
}

// Create output directory if it doesn't exist
$output_dir = __DIR__ . "/output/";
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$output = $output_dir . $output_file_name . $extMap[$format_code];

$result = $ffi->convert_image($input, $output, $format_code);

if ($result === 0) {
    echo "Image conversion successful, image in output dir\n";
} else {
    echo "Conversion failed with error code: $result\n";
}
?>
