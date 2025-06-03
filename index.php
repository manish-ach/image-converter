<?php

function select_image_with_fzf(): ?string {
    $homeDir = getenv('HOME');
    $escapedHome = escapeshellarg($homeDir);

    // Construct command carefully
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

// Check if FFI is available
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

// Use absolute path for better reliability
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

/*echo "Enter path to input image: \n";*/
/*$input = trim(fgets(STDIN));*/

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

/*echo "Enter the output file name (no extension): ";*/
/*$output_file_name = trim(fgets(STDIN));*/

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
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    $output = $outputDir . DIRECTORY_SEPARATOR . $inputBaseName . $targetExt;
}

$output_dir = __DIR__ . "/output/";
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$result = $ffi->convert_image($input, $output, $format_code);

if ($result === 0) {
    echo "Image conversion successful, image in output dir\n";
} else {
    echo "Conversion failed with error code: $result\n";
}
?>
