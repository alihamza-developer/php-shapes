<?php
require_once "includes/functions.php";
require_once "includes/shapes/rhombus.php";

// For Plate
$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$radius = $_GET['radius'] ?? 0; // px
$padding = $_GET['padding'] ?? 70; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";

clear_output_dir();
# Download Process
$svg = download_svg(); // Download SVG
$png = download_png(); // Download PNG

// Download PDF
$temp_svg = generate_file_name("svg");
file_put_contents($temp_svg, get_svg(generate(null), 'pdf'));
$pdf = download_pdf($temp_svg, __DIR__); // Download PDF
@unlink($temp_svg);

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
