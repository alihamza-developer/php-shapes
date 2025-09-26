<?php
require_once "includes/functions.php";

# For Circle
$size = cm_to_px($_GET['size']);
$width = $size;
$height = $size;
$padding = $_GET['padding'] ?? 25; // px
$frame = (bool) ($_GET['frame'] ?? null);

# Circle Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$hole_size = mm_to_px($_GET['hole_size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$direction = $_GET['direction'] ?? "vertical";

// Get SVG
function get_svg($holes, $type = "")
{
    global $frame, $size, $FRAME_GAP, $FRAME_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    $is_pdf = $type === 'pdf';

    $cx = $cy = $size / 2;
    $frame_r = $r  = $cx - $STROKE_WIDTH;

    $frame_r -= $FRAME_GAP;

    // Outline only for PDF
    $frame_c = $frame ? <<<SVG
        <!-- Outline -->
        <circle 
            cx="{$cx}" 
            cy="{$cy}" 
            r="{$frame_r}" 
            fill="none" 
            stroke="black" 
            stroke-width="{$FRAME_WIDTH}"
        />
    SVG : "";

    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
            {$frame_c}

            <!-- Circle -->
            <circle
                cx="{$cx}"
                cy="{$cy}"
                r="{$r}"
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
    global $count, $direction, $FRAME_GAP, $frame;
    $gap = $frame ? $FRAME_GAP : 0;

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
    global $padding, $width, $height, $size, $hole_size;
    $size_old = $size;
    $size = $hole_size;
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

    $size = $size_old;
    return $hole;
}

# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false
]);
