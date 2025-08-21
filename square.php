<?php
require_once "functions.php";

$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$type = isset($_GET['type']) ? $_GET['type'] : "cornor"; // (cornor,rounded)
$corner_radius = $type === 'rounded' ? 30 : 0;

// Holes 
$holes = ($_GET['holes'] == "true");
$hole_size = isset($_GET['hole_size']) ? mm_to_px($_GET['hole_size']) : 37; // Default 37
$hole_margin = isset($_GET['padding']) ? $_GET['padding'] : 30;

// Bolts
$bolts = "";
$bolt_image = "bolts/gold.png";


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
        $bolts .= "<image href='$bolt_image' x='{$x}' y='{$y}' width='{$hole_size}' height='{$hole_size}' />";
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
            fill="#ffffff"
            stroke="#333"
            storke-width="2"
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
    $orientation = ($width > $height) ? 'L' : 'P';
    $filename = generate_file_name("pdf");

    $pdf = new TCPDF($orientation, 'pt', [$width, $height]);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $pdf->ImageSVG("output/$svg", 0, 0, $width, $height); // Placing SVG
    $pdf->Image("output/$png", 0, 0, $width, $height); // Placing PNG


    // Placing Targhe (Logo)
    $pdf->ImageSVG("logo.svg", ($width / 2) - (368 / 2), ($height / 2) - (368 / 2), 368, '', 'C', 'C', 0, false);

    // Placing (Dimension Text)
    $width_cm = px_to_cm($width);
    $height_cm = px_to_cm($height);
    $text = "Dimension File: {$width_cm}cm X {$height_cm}cm";
    $pdf->SetFont("arial", "B", 30);
    $t_w = $pdf->GetStringWidth($text, "arial", "B", 30);
    $t_h = $pdf->getStringHeight($t_w, $text);
    $pdf->Text((($width / 2) - ($t_w / 2)), (($height / 2) - ($t_h / 2)), $text); // Print Final Text


    // Creating Output
    $pdf->Output(__DIR__ . "/output/$filename", "F");
}


clear_output_dir();

$svg = download_mask();
$png = download_png();
download_pdf($svg, $png);
