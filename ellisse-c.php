<?php
require_once "includes/functions.php";
// For Circle
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 15; // px
$frame = (bool) ($_GET['frame'] ?? null);

// Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$direction = $_GET['direction'] ?? "vertical";


// Get SVG for Ellipse
function get_svg($holes = "")
{
    global $FRAME_GAP, $FRAME_WIDTH, $STROKE_WIDTH, $STROKE_COLOR, $frame;

    global $width, $height;


    $cx = ($width / 2) - 2;
    $cy = ($height / 2) - 2;
    $rx = ($width / 2);
    $ry = ($height / 2);

    # Frame
    $frame_rx = ($width - ($FRAME_GAP + $FRAME_WIDTH)) / 2;
    $frame_ry = ($height - ($FRAME_GAP + $FRAME_WIDTH)) / 2;

    $frame_e = $frame ? <<<SVG
        <!-- Outline -->
        <ellipse 
            cx="{$cx}" 
            cy="{$cy}" 
            rx="{$frame_rx}" 
            ry="{$frame_ry}" 
            fill="none" 
            stroke="black"
            stroke-width="{$FRAME_WIDTH}"
        />
    SVG : "";

    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$frame_e}

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
    global $count, $direction, $frame, $FRAME_GAP, $FRAME_WIDTH;

    $gap += $frame ? $FRAME_GAP + $FRAME_WIDTH : 0;

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
    'dir' => __DIR__,
    'compress' => false
]);
