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
$spacer = $holes && isset($_GET['spacer']) ? $_GET['spacer'] : "";
$bolt_image = "spacers/$spacer"; // Spacer Image

// PDF
define("PDF_OUTLINE_GAP", cm_to_px(0.4));


define("OUTPUT_PATH", "output/"); // Define output path here

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
function get_svg($content, $type = "")
{
    global $width, $height, $corner_radius;

    $is_pdf = $type === 'pdf';
    $is_png = $type === 'png';
    $gap = $is_pdf ? PDF_OUTLINE_GAP : 0;
    $plate_x = $gap / 2;
    $plate_y = $gap / 2;
    $outline_w = $width + $gap;
    $outline_h = $height + $gap;
    $outline_radius = $corner_radius + ($gap / 2);
    $corner_radius += $gap / 2;

    $outline = <<<SVG
                <!-- Outline -->
                <rect 
                x="0" 
                y="0" 
                width="{$outline_w}" 
                height="{$outline_h}" 
                rx="{$outline_radius}" 
                ry="{$outline_radius}" 
                fill="none" 
                stroke="blue" 
                stroke-width="1" 
                />
            SVG;

    $outline = $is_pdf ? $outline : '';



    $props = <<<PROPS
            stroke="#000"
            storke-width="1"
    PROPS;

    $props = $is_png ? "" : $props;


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$outline_w}" height="{$outline_h}" viewBox="0 0 {$outline_w} {$outline_h}">
            
            {$outline}
            <!-- Plate -->
            <rect
            x="{$plate_x}"
            y="{$plate_y}"
            width="{$width}"
            height="{$height}"
            rx="{$corner_radius}"
            ry="{$corner_radius}"
            fill="none"
            {$props}
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
    $filename = generate_file_name("svg", OUTPUT_PATH, true);
    file_put_contents($filename, $svg); // Svg Path

    return $filename;
}

// Download PNG 
function download_png()
{
    global $bolts;
    $svg = get_svg($bolts, 'png');

    $filename = generate_file_name("png", OUTPUT_PATH, true);

    svg_to_png($svg, $filename); // PNG Path
    return $filename;
}

// Download PDF
function download_pdf($svg, $png)
{
    global $width, $height;

    $pdf = new TCPDF(
        (($width > $height) ? 'L' : 'P'), // Orientation
        'pt',                             // Unit
        [
            $width + PDF_OUTLINE_GAP,     // Size
            $height + PDF_OUTLINE_GAP    // Size
        ]
    );

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $pdf->ImageSVG($svg, 0, 0, $width + PDF_OUTLINE_GAP, $height + PDF_OUTLINE_GAP); // Placing SVG

    // Placing (Targhe Insegne) Logo
    $pdf->ImageSVG("logo.svg", ($width / 2) - (368 / 2), ($height / 2) - 100, 368, 128, '', 'C', 'C', 0, false);

    // Placing (Dimension Text)
    $dim_width = px_to_cm($width + PDF_OUTLINE_GAP);
    $dim_height = px_to_cm($height + PDF_OUTLINE_GAP);
    $text = "Dimensioni File: {$dim_width}cm X {$dim_height}cm";
    $pdf->SetFont("arial", "B", 30);

    $props = [0, 0, $text, 0, false, true, 0, 0, 'C', false, '', 0, false, 'T', 'C'];
    $props[1] = ($height / 2) + 50;
    $pdf->Text(...$props);
    $props[1] += 40;
    $props[2] = "Dimensioni Selezionate: " . px_to_cm($width) . "cm X " . px_to_cm($height) . "cm";
    $pdf->Text(...$props);


    // Output PDF
    $filename = generate_file_name("pdf", OUTPUT_PATH, true);
    $pdf->Output(__DIR__ . '/' . $filename, "F");
    return $filename;
}

@mkdir(OUTPUT_PATH);

$svg_file = download_mask(); // Download Mask
$png = download_png();
$svg = get_svg($holes, "pdf");

$filename = generate_file_name("svg", OUTPUT_PATH, true);
file_put_contents($filename, $svg);

$pdf_file = download_pdf($filename, $png); // Download PDF

@unlink($filename);


echo json_encode([
    'svg' => $svg_file,
    'png' => $png,
    'pdf' => $pdf_file
]);
