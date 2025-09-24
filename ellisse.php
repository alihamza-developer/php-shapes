<?php
require_once "includes/functions.php";
// For Circle
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 50; // px

// Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$direction = $_GET['direction'] ?? "vertical";


// Get SVG for Ellipse
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    global $width, $height;
    $is_pdf = $type === 'pdf';
    $gap = $is_pdf ? $PDF_OUTLINE_GAP : 0;

    $cx = (($width + $gap) / 2) - $STROKE_WIDTH;
    $cy = (($height + $gap) / 2) - $STROKE_WIDTH;
    $rx = ($width / 2);
    $ry = ($height / 2);

    // Shrink to keep stroke inside
    $rx -= $is_pdf ? $PDF_OUTLINE_GAP + 2 : 2;
    $ry -= $is_pdf ? $PDF_OUTLINE_GAP + 2 : 2;

    $outline_rx = $rx + ($gap / 2);
    $outline_ry = $ry + ($gap / 2);

    // Outline only for PDF
    $outline = <<<SVG
        <!-- Outline -->
        <ellipse 
            cx="{$cx}" 
            cy="{$cy}" 
            rx="{$outline_rx}" 
            ry="{$outline_ry}" 
            fill="none" 
            stroke="{$PDF_OUTLINE_COLOR}"
            stroke-width="{$PDF_OUTLINE_WIDTH}"
        />
    SVG;
    $outline = $is_pdf ? $outline : '';

    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$outline}

            <!-- Ellipse -->
            <ellipse
                cx="{$cx}"
                cy="{$cy}"
                rx="{$rx}"
                ry="{$ry}"
                stroke="{$STROKE_COLOR}"
                stroke-width="{$STROKE_WIDTH}"
                fill="none"
            />
            
            {$holes}
        </svg>
    BODY;

    return $svg;
}

// Generate Holes
function generate($spacer = null, $gap = 0)
{
    global $count, $direction;

    $positions = ['tc', 'rc', 'bc', 'lc'];

    switch ((int)$count) {
        case 1:
            $positions = ['tc'];
            break;
        case 2:
            $positions = $direction === 'vertical' ? ['tc', 'bc'] : ['rc', 'lc'];
            break;
    }


    $out = '';
    foreach ($positions as $pos) {

        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos,
            'pdf_gap' => $gap
        ]);
    }

    return $out;
}

# Get Hole
function get_hole($data)
{
    global $padding, $width, $height, $size;
    $spacer   = $data['spacer'];
    $pos      = $data['pos'];
    $pdf_gap  = $data['pdf_gap'];
    $r   = $size / 2;
    $gap = ($r + $padding + $pdf_gap);
    $h_w = $width / 2;
    $h_h = $height / 2;

    switch ($pos) {
        case 'tc':
            $x = $h_w;
            $y = $gap;
            break;
        case 'bc':
            $x = $h_w;
            $y = $height - $gap;
            break;
        case 'lc':
            $x = $gap;
            $y = $h_h;
            break;
        case 'rc':
            $x = $width - $gap;
            $y = $h_h;
            break;
        default:
            return ''; // ignore invalid pos
    }

    $hole =  make_hole([
        'x'      => $x,
        'y'      => $y,
        'spacer' => $spacer,
    ]);

    return $hole;
}

# Start Downloader
start_downloader([
    'dir' => __DIR__
]);
