<?php
function select_image_with_fzf(): ?string {
    $homeDir = getenv('HOME');
    $escapedHome = escapeshellarg($homeDir);
    $cmd = "find $escapedHome "
         . "-path " . escapeshellarg("$homeDir/Library") . " -prune -o "
         . "-path " . escapeshellarg("$homeDir/.*") . " -prune -o "
         . "-type f \\( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.webp' \\) -print | fzf";
    $selected = shell_exec($cmd);
    if ($selected === null) {
        return null;
    }
    $selected = trim($selected);
    return $selected !== "" ? $selected : null;
}

function get_kitty_format_flag(string $path): int {
    $info = getimagesize($path);
    $mime = $info['mime'] ?? '';
    return match($mime) {
        'image/webp' => 102,
        'image/jpeg' => 101,
        'image/png' => 100,
        default => 100, // default to PNG
    };
}

function display_image_in_kitty(string $path): void {
    if (!file_exists($path)) {
        echo "Error: Output file does not exist at $path\n";
        return;
    }
    
    echo "Displaying image...\n";
    $cmd = "kitty +kitten icat " . escapeshellarg($path);
    passthru($cmd);
}

// Checking if FFI is available
if (!extension_loaded("ffi")) {
    die("FFI extension is not loaded. Please enable it in php.ini\n");
}

// Get shared object extension according to the platform
$ext = "";
$os = PHP_OS_FAMILY;
switch($os){
case "Linux":
    $ext = "so";
        break;
case "Windows":
    $ext = "dll";
        break;
default:
    $ext = "dylib";
        break;
}

// Using absolute path for better reliability
$lib_path = __DIR__ . "/lib/librust_image_converter.$ext";
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

$input = select_image_with_fzf();
if (!$input) {
    echo "file not selected";
    exit(1);
}

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

if (!array_key_exists($format_code, $extMap)) {
    echo "Invalid details provided\n";
    exit(1);
}

$inputBaseName = pathinfo($input, PATHINFO_FILENAME);
$inputDir = dirname($input);
$targetExt = $extMap[$format_code];

echo "Save location :\n";
echo "1 => input directory: $inputDir\n";
echo "2 => default directory: ./output/\n";
echo "choose 1 or 2(default) => ";
$location_choice = trim(fgets(STDIN));

if ($location_choice === "1") {
    $output = $inputDir . DIRECTORY_SEPARATOR . $inputBaseName . $targetExt;
} else {
    $outputDir = __DIR__ . "/output/";
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    $output = $outputDir . DIRECTORY_SEPARATOR . $inputBaseName . $targetExt;
}

$result = $ffi->convert_image($input, $output, $format_code);
if ($result === 0) {
    echo "Image conversion successful!\n";
    echo "Output file: $output\n";
    
    // Verifying the output file exists and has content
    if (file_exists($output)) {
        $fileSize = filesize($output);
        echo "File size: " . number_format($fileSize) . " bytes\n";
        
        // Displaying images
        echo "Displaying image...\n";
        display_image_in_kitty($output);
    } else {
        echo "Error: Output file was not created\n";
    }
} else {
    echo "Conversion failed with error code: $result\n";
}
?>
