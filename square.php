<?php
require_once "functions.php";


$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$type = isset($_GET['type']) ? $_GET['type'] : "cornor"; // (cornor,rounded)
$corner_radius = $type === 'rounded' ? 30 : 0;

// Holes 
$holes = ($_GET['holes'] == "true");
$bolts = "";
$hole_size    = isset($_GET['hole_size']) ? mm_to_px($_GET['hole_size']) : 37; // Default 37
$hole_margin = isset($_GET['padding']) ? $_GET['padding'] : 20;


// If holes enabled
if ($holes) {
    $holes = "";
    $r = $hole_size / 2;

    $positions = [
        [$hole_margin + $r, $hole_margin + $r],                       // top-left
        [$width - $hole_margin - $r, $hole_margin + $r],              // top-right
        [$hole_margin + $r, $height - $hole_margin - $r],             // bottom-left
        [$width - $hole_margin - $r, $height - $hole_margin - $r],    // bottom-right
    ];

    // Holes
    foreach ($positions as [$cx, $cy])
        $holes .= "<circle cx='{$cx}' cy='{$cy}' r='{$r}' fill='none' stroke='black'/>";

    // Bolts
    foreach ($positions as [$cx, $cy]) {
        $x = $cx - $hole_size / 2;
        $y = $cy - $hole_size / 2;
        $bolts .= "<image href='bolts/gold.png' x='{$x}' y='{$y}' width='{$hole_size}' height='{$hole_size}' />";
    }
}

// Get SVG
function get_svg($content)
{
    global $width, $height, $corner_radius;

    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <!-- Plate -->
            <rect
            x="0"
            y="0"
            width="{$width}"
            height="{$height}"
            rx="{$corner_radius}"
            ry="{$corner_radius}"
            fill="none"
            stroke="#333"
            />

            {$content}
        </svg>
    BODY;

    return $svg;
}

// Download Cliping Mask
function download_mask()
{
    global $holes;
    $svg = get_svg($holes);
    $svg = compress_svg($svg, "svg");
    $filename = generate_file_name("svg");
    file_put_contents("output/$filename", $svg);
    return $filename;
}

// Download PNG 
function download_png()
{
    global $bolts;
    $svg = get_svg($bolts, "png");
    $filename = generate_file_name("png");

    svg_to_png($svg, "output/{$filename}");
    return $filename;
}

// Download PDF
function download_pdf($svg, $png)
{
    global $width, $height;
    $pdf = new TCPDF(($width > $height) ? 'L' : 'P', 'pt', [$width, $height]);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    // Place SVG
    $pdf->ImageSVG($file = "output/$svg", 0, 0, $width, $height, '', '', 'C', 0, false);
    $pdf->Image($file = "output/$png", 0, 0, $width, $height, '', '', 'C', 0, false, 'C');
    $pdf->Output(__DIR__ . "/output/output.pdf", "F");
}

clear_output_dir();

$svg = download_mask();
$png = download_png();
download_pdf($svg, $png);
