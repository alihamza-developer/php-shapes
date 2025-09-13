<?php
require_once "includes/functions.php";
require_once "includes/shapes/square.php";

// For Plate
$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$radius = $_GET['radius'] ?? 25; // px
$padding = $_GET['padding'] ?? 35; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";


$svg = download_svg(); # Download SVG
$png = download_png(); # Download PNG
$pdf = download_pdf(__DIR__); # Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
