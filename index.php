<?php

/**
 * 🖼️ PHP Image Converter (via Rust FFI)
 * High-performance image processing powered by Rust
 * 
 * @author Manish Acharya
 * @version 2.1.0
 */

// Configuration - Simple PHP array instead of .env
$config = [
    'output_dir' => './output/',
    'default_quality' => 85,
    'enable_kitty_preview' => true,
    'fzf_options' => '--height 40% --reverse --border --preview "file {}"'
];

// ANSI Color codes for beautiful terminal output
class Colors {
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
    const DIM = "\033[2m";

    // Colors
    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";

    // Background colors
    const BG_BLACK = "\033[40m";
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_MAGENTA = "\033[45m";
    const BG_CYAN = "\033[46m";
    const BG_WHITE = "\033[47m";

    // Bright colors
    const BRIGHT_BLACK = "\033[90m";
    const BRIGHT_RED = "\033[91m";
    const BRIGHT_GREEN = "\033[92m";
    const BRIGHT_YELLOW = "\033[93m";
    const BRIGHT_BLUE = "\033[94m";
    const BRIGHT_MAGENTA = "\033[95m";
    const BRIGHT_CYAN = "\033[96m";
    const BRIGHT_WHITE = "\033[97m";
}

/**
 * Get terminal width
 */
function get_terminal_width(): int {
    return (int) shell_exec('tput cols') ?: 80;
}

/**
 * Strip ANSI codes to get actual string length
 */
function get_display_length(string $str): int {
    return mb_strlen(preg_replace('/\033\[[0-9;]*m/', '', $str));
}

/**
 * Display a beautiful header
 */
function display_header(): void {
    $header = "
╔═══════════════════════════════════════════════════════════════════════════════╗
║                                                                               ║
║        " . Colors::BRIGHT_CYAN . "\t\t\t🖼️  PHP Image Converter" . Colors::RESET . "                             ║
║  " . Colors::DIM . "\t\tHigh-performance image processing powered by Rust" . Colors::RESET . "                   ║
║                                                                               ║
║  " . Colors::BRIGHT_YELLOW . "\t\t⚡ Blazing Fast" . Colors::RESET . "    " . Colors::BRIGHT_GREEN . "🔄 Multi-Format" . Colors::RESET . "    " . Colors::BRIGHT_BLUE . "📺 Kitty Support" . Colors::RESET . "              ║
║                                                                               ║
╚═══════════════════════════════════════════════════════════════════════════════╝
";

    // Get terminal width
    $terminalWidth = get_terminal_width();

    // Calculate the width of your header (the border is 79 characters wide)
    $headerWidth = 79;

    // Calculate left padding needed to center the header
    $leftPadding = max(0, ($terminalWidth - $headerWidth) / 2);
    $leftPadding = (int) floor($leftPadding);

    // Split header into lines and add padding to each line
    $headerLines = explode("\n", $header);
    $centeredHeader = "";

    foreach ($headerLines as $line) {
        if (trim($line) !== '') {  // Skip empty lines
            $centeredHeader .= str_repeat(" ", $leftPadding) . $line . "\n";
        } else {
            $centeredHeader .= $line . "\n\n\n";
        }
    }

    echo $centeredHeader;
}

/**
 * Display a progress spinner
 */
function show_spinner(string $message, callable $task) {
    $spinners = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    $i = 0;

    echo Colors::CYAN . $message . Colors::RESET;

    // Start the task in background (simplified for this example)
    $result = $task();

    // Simple animation
    for ($j = 0; $j < 10; $j++) {
        echo "\r" . Colors::CYAN . $message . " " . Colors::BRIGHT_CYAN . $spinners[$i % count($spinners)] . Colors::RESET;
        $i++;
        usleep(100000); // 0.1 second
    }

    echo "\r" . Colors::GREEN . "✅ " . $message . " Complete!" . Colors::RESET . "\n";
    return $result;
}

/**
 * Display a styled box with content (centered)
 */
function display_box(string $title, array $content, string $color = Colors::BLUE): void {
    $terminalWidth = get_terminal_width();
    $boxWidth = 60; // Fixed box width for consistency

    // Calculate centering for the entire box
    $boxPadding = max(0, ($terminalWidth - $boxWidth - 2) / 2);
    $boxPaddingLeft = (int) floor($boxPadding);
    $boxPaddingStr = str_repeat(" ", $boxPaddingLeft);

    // Title centering within the box
    $titleLen = get_display_length($title);
    $titlePadding = max(0, ($boxWidth - $titleLen) / 2);
    $titlePaddingLeft = (int) floor($titlePadding);
    $titlePaddingRight = $boxWidth - $titleLen - $titlePaddingLeft;

    // Top border
    echo $boxPaddingStr . $color . "┌" . str_repeat("─", $boxWidth) . "┐" . Colors::RESET . "\n";

    // Title line
    echo $boxPaddingStr . $color . "│" . str_repeat(" ", $titlePaddingLeft) . Colors::BOLD . Colors::WHITE . $title . Colors::RESET . $color . str_repeat(" ", $titlePaddingRight) . "│" . Colors::RESET . "\n";

    // Separator
    echo $boxPaddingStr . $color . "├" . str_repeat("─", $boxWidth) . "┤" . Colors::RESET . "\n";

    // Content lines
    foreach ($content as $line) {
        $lineLen = get_display_length($line);
        $spacePadding = max(0, $boxWidth - $lineLen - 2);
        echo $boxPaddingStr . $color . "│ " . Colors::RESET . $line . str_repeat(" ", $spacePadding) . $color . "│" . Colors::RESET . "\n";
    }

    // Bottom border
    echo $boxPaddingStr . $color . "└" . str_repeat("─", $boxWidth) . "┘" . Colors::RESET . "\n\n";
}

/**
 * Get user input with styled prompt (left-aligned)
 */
function get_styled_input(string $prompt, string $color = Colors::CYAN): string {
    echo $color . "❯ " . Colors::BOLD . $prompt . Colors::RESET . " ";
    return trim(fgets(STDIN));
}

/**
 * Display success message
 */
function display_success(string $message): void {
    echo Colors::BG_GREEN . Colors::WHITE . Colors::BOLD . " ✅ SUCCESS " . Colors::RESET . " " . Colors::GREEN . $message . Colors::RESET . "\n";
}

/**
 * Display error message
 */
function display_error(string $message): void {
    echo Colors::BG_RED . Colors::WHITE . Colors::BOLD . " ❌ ERROR " . Colors::RESET . " " . Colors::RED . $message . Colors::RESET . "\n";
}

/**
 * Display warning message
 */
function display_warning(string $message): void {
    echo Colors::BG_YELLOW . Colors::BLACK . Colors::BOLD . " ⚠️  WARNING " . Colors::RESET . " " . Colors::YELLOW . $message . Colors::RESET . "\n";
}

/**
 * Display info message
 */
function display_info(string $message): void {
    echo Colors::BG_BLUE . Colors::WHITE . Colors::BOLD . " ℹ️  INFO " . Colors::RESET . " " . Colors::BLUE . $message . Colors::RESET . "\n";
}

/**
 * Select image using fzf with enhanced options
 */
function select_image_with_fzf(array $config): ?string {
    display_info("Opening file selector...");

    $homeDir = getenv('HOME');
    $escapedHome = escapeshellarg($homeDir);
    $fzfOptions = $config['fzf_options'];

    $cmd = "find $escapedHome "
         . "-path " . escapeshellarg("$homeDir/Library") . " -prune -o "
         . "-path " . escapeshellarg("$homeDir/.*") . " -prune -o "
         . "-type f \\( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.webp' \\) -print | fzf $fzfOptions --prompt='🖼️  Select Image: '";

    $selected = shell_exec($cmd);
    if ($selected === null) {
        return null;
    }
    $selected = trim($selected);
    return $selected !== "" ? $selected : null;
}

/**
 * Display image info in a beautiful format
 */
function display_image_info(string $path, string $label = "Image Info"): void {
    if (!file_exists($path)) {
        display_error("File not found: $path");
        return;
    }

    $fileSize = filesize($path);
    $info = getimagesize($path);
    $mime = $info['mime'] ?? 'unknown';
    $dimensions = isset($info[0], $info[1]) ? $info[0] . "×" . $info[1] : 'unknown';

    $formatMap = [
        'image/jpeg' => '📸 JPEG',
        'image/png' => '🖼️ PNG',
        'image/webp' => '🌐 WEBP',
    ];

    $format = $formatMap[$mime] ?? '❓ Unknown';
    $sizeFormatted = formatBytes($fileSize);

    display_box($label, [
        "📁 " . Colors::BRIGHT_BLUE . basename($path) . Colors::RESET,
        "📏 Dimensions: " . Colors::BRIGHT_GREEN . $dimensions . Colors::RESET,
        "🎨 Format: " . Colors::BRIGHT_YELLOW . $format . Colors::RESET,
        "💾 Size: " . Colors::BRIGHT_CYAN . $sizeFormatted . Colors::RESET,
    ], Colors::MAGENTA);
}

/**
 * Format bytes into human readable format
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Display conversion formats with beautiful styling (centered)
 */
function display_format_options(): int {
    $formats = [
        0 => ['name' => 'JPEG', 'desc' => 'High compatibility, lossy compression', 'icon' => '📸', 'color' => Colors::BRIGHT_RED],
        1 => ['name' => 'PNG', 'desc' => 'Lossless quality, transparency support        ', 'icon' => '🖼️', 'color' => Colors::BRIGHT_GREEN],
        2 => ['name' => 'WEBP', 'desc' => 'Best compression, modern format', 'icon' => '🌐', 'color' => Colors::BRIGHT_BLUE],
    ];

    $terminalWidth = get_terminal_width();
    $boxWidth = 60;
    $boxPadding = max(0, ($terminalWidth - $boxWidth - 2) / 2);
    $boxPaddingLeft = (int) floor($boxPadding);
    $boxPaddingStr = str_repeat(" ", $boxPaddingLeft);

    // Title
    $title = "🎨 Choose Output Format";
    $titleLen = get_display_length($title);
    $titlePadding = max(0, ($boxWidth - $titleLen) / 2);
    $titleLeftPad = (int) floor($titlePadding);
    $titleRightPad = $boxWidth - $titleLen - $titleLeftPad;

    echo $boxPaddingStr . Colors::CYAN . "┌" . str_repeat("─", $boxWidth) . "┐" . Colors::RESET . "\n";
    echo $boxPaddingStr . Colors::CYAN . "│" . str_repeat(" ", $titleLeftPad) . Colors::BOLD . Colors::WHITE . $title . Colors::RESET . Colors::CYAN . str_repeat(" ", $titleRightPad) . "│" . Colors::RESET . "\n";
    echo $boxPaddingStr . Colors::CYAN . "├" . str_repeat("─", $boxWidth) . "┤" . Colors::RESET . "\n";

    foreach ($formats as $code => $format) {
        $content = $format['icon'] . " [" . $code . "] " . $format['name'] . " - " . $format['desc'];
        $contentLen = get_display_length($content);
        $padding = max(0, $boxWidth - $contentLen - 2);

        echo $boxPaddingStr . Colors::CYAN . "│ " . Colors::RESET;
        echo $format['color'] . $format['icon'] . " " . Colors::BOLD . "[" . $code . "]" . Colors::RESET . " ";
        echo Colors::WHITE . $format['name'] . Colors::RESET . " - ";
        echo Colors::DIM . $format['desc'] . Colors::RESET;
        echo str_repeat(" ", $padding);
        echo Colors::CYAN . "│" . Colors::RESET . "\n";
    }

    echo $boxPaddingStr . Colors::CYAN . "└" . str_repeat("─", $boxWidth) . "┘" . Colors::RESET . "\n\n";

    do {
        $choice = get_styled_input("Select format (0-2):", Colors::BRIGHT_CYAN);
        $formatCode = (int) $choice;

        if (!array_key_exists($formatCode, $formats)) {
            display_warning("Please enter a valid option (0, 1, or 2)");
            continue;
        }

        $selectedFormat = $formats[$formatCode];
        echo Colors::GREEN . "✓ Selected: " . $selectedFormat['icon'] . " " . Colors::BOLD . $selectedFormat['name'] . Colors::RESET . "\n\n";
        return $formatCode;

    } while (true);
}

/**
 * Display location options (centered)
 */
function display_location_options(string $inputDir, array $config): string {
    $terminalWidth = get_terminal_width();
    $boxWidth = 60;
    $boxPadding = max(0, ($terminalWidth - $boxWidth - 2) / 2);
    $boxPaddingLeft = (int) floor($boxPadding);
    $boxPaddingStr = str_repeat(" ", $boxPaddingLeft);

    // Title
    $title = "📁 Choose Save Location";
    $titleLen = get_display_length($title);
    $titlePadding = max(0, ($boxWidth - $titleLen) / 2);
    $titleLeftPad = (int) floor($titlePadding);
    $titleRightPad = $boxWidth - $titleLen - $titleLeftPad;

    echo $boxPaddingStr . Colors::CYAN . "┌" . str_repeat("─", $boxWidth) . "┐" . Colors::RESET . "\n";
    echo $boxPaddingStr . Colors::CYAN . "│" . str_repeat(" ", $titleLeftPad) . Colors::BOLD . Colors::WHITE . $title . Colors::RESET . Colors::CYAN . str_repeat(" ", $titleRightPad) . "│" . Colors::RESET . "\n";
    echo $boxPaddingStr . Colors::CYAN . "├" . str_repeat("─", $boxWidth) . "┤" . Colors::RESET . "\n";

    // Option 1
    $opt1Text = "📂 [1] Same as input";
    $opt1Len = get_display_length($opt1Text);
    $opt1Padding = max(0, $boxWidth - $opt1Len - 2);

    echo $boxPaddingStr . Colors::CYAN . "│ " . Colors::RESET . Colors::BRIGHT_GREEN . $opt1Text . Colors::RESET;
    echo str_repeat(" ", $opt1Padding) . Colors::CYAN . "│" . Colors::RESET . "\n";

    // Option 1 description
    $opt1DescText = "   " . Colors::DIM . $inputDir . Colors::RESET;
    $opt1DescLen = get_display_length($opt1DescText);
    $opt1DescPadding = max(0, $boxWidth - $opt1DescLen - 2);

    echo $boxPaddingStr . Colors::CYAN . "│ " . Colors::RESET . $opt1DescText;
    echo str_repeat(" ", $opt1DescPadding) . Colors::CYAN . "│" . Colors::RESET . "\n";

    // Option 2
    $opt2Text = "🗂️  [2] Output folder";
    $opt2Len = get_display_length($opt2Text);
    $opt2Padding = max(0, $boxWidth - $opt2Len - 2);

    echo $boxPaddingStr . Colors::CYAN . "│ " . Colors::RESET . Colors::BRIGHT_BLUE . $opt2Text . Colors::RESET;
    echo str_repeat(" ", $opt2Padding) . Colors::CYAN . "│" . Colors::RESET . "\n";

    // Option 2 description
    $opt2DescText = "   " . Colors::DIM . $config['output_dir'] . Colors::RESET;
    $opt2DescLen = get_display_length($opt2DescText);
    $opt2DescPadding = max(0, $boxWidth - $opt2DescLen - 2);

    echo $boxPaddingStr . Colors::CYAN . "│ " . Colors::RESET . $opt2DescText;
    echo str_repeat(" ", $opt2DescPadding) . Colors::CYAN . "│" . Colors::RESET . "\n";

    echo $boxPaddingStr . Colors::CYAN . "└" . str_repeat("─", $boxWidth) . "┘" . Colors::RESET . "\n\n";

    do {
        $choice = get_styled_input("Select location (1-2, default: 2):", Colors::BRIGHT_CYAN);
        $choice = $choice ?: "2";

        if ($choice === "1") {
            echo Colors::GREEN . "✓ Selected: Input directory" . Colors::RESET . "\n\n";
            return $inputDir;
        } elseif ($choice === "2") {
            echo Colors::GREEN . "✓ Selected: Output directory" . Colors::RESET . "\n\n";
            return $config['output_dir'];
        } else {
            display_warning("Please enter 1 or 2");
        }
    } while (true);
}

/**
 * Display image in Kitty terminal
 */
function display_image_in_kitty(string $path): void {
    if (!file_exists($path)) {
        display_error("Output file does not exist at $path");
        return;
    }

    display_info("Displaying image in terminal...");
    $cmd = "kitty +kitten icat " . escapeshellarg($path);
    passthru($cmd);
    echo "\n";
}

/**
 * Get library path - handles both phar and direct execution
 */
function get_library_path(): string {
    // First, check if we're running from phar and have extracted library
    $pharLibPath = getenv('RUST_LIB_PATH');
    if ($pharLibPath && file_exists($pharLibPath)) {
        return $pharLibPath;
    }

    // Check $_ENV as well (alternative way the bootstrap might set it)
    if (isset($_ENV['RUST_LIB_PATH']) && file_exists($_ENV['RUST_LIB_PATH'])) {
        return $_ENV['RUST_LIB_PATH'];
    }

    // Fallback to local lib directory (for direct execution)
    $ext = match(PHP_OS_FAMILY) {
        "Linux" => "so",
        "Windows" => "dll",
        default => "dylib"
    };

    return __DIR__ . "/lib/librust_image_converter.$ext";
}

/**
 * Main application logic
 */
function main(): void {
    global $config;

    // Clear screen and show header
    system('clear');
    display_header();

    // Check FFI availability
    if (!extension_loaded("ffi")) {
        display_error("FFI extension is not loaded. Please enable it in php.ini");
        exit(1);
    }

    // Get library path
    $lib_path = get_library_path();
    
    if (!file_exists($lib_path)) {
        display_error("Library file not found at: $lib_path");
        display_info("Run 'make' to build the Rust library");
        exit(1);
    }

    // Load FFI
    try {
        $ffi = FFI::cdef(
            "int convert_image(const char* input_path, const char* output_path, int format_code);",
            $lib_path
        );
        display_success("Rust FFI library loaded successfully from: " . basename($lib_path));
    } catch (FFI\Exception $e) {
        display_error("Failed to load library: " . $e->getMessage());
        display_info("Library path attempted: $lib_path");
        exit(1);
    }

    // Select input image
    $input = select_image_with_fzf($config);
    if (!$input) {
        display_warning("No file selected. Exiting...");
        exit(0);
    }

    display_success("Selected: " . basename($input));
    display_image_info($input, "📷 Input Image");

    // Choose output format
    $formatCode = display_format_options();

    $extMap = [0 => ".jpg", 1 => ".png", 2 => ".webp"];
    $targetExt = $extMap[$formatCode];

    // Choose save location
    $inputDir = dirname($input);
    $outputDir = display_location_options($inputDir, $config);

    // Ensure output directory exists
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
        display_info("Created output directory: $outputDir");
    }

    // Generate output filename
    $inputBaseName = pathinfo($input, PATHINFO_FILENAME);
    $output = rtrim($outputDir, '/') . DIRECTORY_SEPARATOR . $inputBaseName . $targetExt;

    // Check if file exists and ask for confirmation
    if (file_exists($output)) {
        display_warning("File already exists: " . basename($output));
        $overwrite = get_styled_input("Overwrite? (y/N):", Colors::YELLOW);
        if (strtolower($overwrite) !== 'y') {
            $customName = get_styled_input("Enter custom filename (without extension):", Colors::CYAN);
            if ($customName) {
                $output = rtrim($outputDir, '/') . DIRECTORY_SEPARATOR . $customName . $targetExt;
            }
        }
    }

    // Perform conversion with spinner
    echo "\n";
    display_info("Converting " . basename($input) . " → " . basename($output));

    $result = show_spinner("🔄 Processing image", function() use ($ffi, $input, $output, $formatCode) {
        return $ffi->convert_image($input, $output, $formatCode);
    });

    // Check result
    if ($result === 0) {
        echo "\n";
        display_success("Image conversion completed!");

        if (file_exists($output)) {
            $inputSize = filesize($input);
            $outputSize = filesize($output);
            $savings = round((($inputSize - $outputSize) / $inputSize) * 100, 1);

            display_box("📊 Conversion Results", [
                "📁 Output: " . Colors::BRIGHT_BLUE . basename($output) . Colors::RESET,
                "📍 Location: " . Colors::DIM . dirname($output) . Colors::RESET,
                "📏 Original: " . Colors::BRIGHT_RED . formatBytes($inputSize) . Colors::RESET,
                "📦 Converted: " . Colors::BRIGHT_GREEN . formatBytes($outputSize) . Colors::RESET,
                "💾 Space saved: " . Colors::BRIGHT_CYAN . ($savings > 0 ? $savings . "%" : "0%") . Colors::RESET,
            ], Colors::GREEN);

            display_image_info($output, "🎉 Output Image");

            // Display image if Kitty is available and enabled
            if ($config['enable_kitty_preview'] && getenv('TERM') === 'xterm-kitty') {
                display_image_in_kitty($output);
            }

        } else {
            display_error("Output file was not created");
        }
    } else {
        display_error("Conversion failed with error code: $result");
        exit(1);
    }

    echo Colors::BRIGHT_GREEN . "\n🎉 All done! Thanks for using PHP Image Converter!" . Colors::RESET . "\n\n";
}

// Run the application
main();

?>
