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
    global $width, $height, $FILL_PATH_GAP, $size, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    $is_pdf = $type === 'pdf';

    $cx = ($width / 2) - $STROKE_WIDTH;
    $cy = ($height / 2) - $STROKE_WIDTH;
    $rx = $cx;
    $ry = $cy;


    # Fill Path
    $e_rx = $rx - ($FILL_PATH_GAP + $size);
    $e_ry = $ry - ($FILL_PATH_GAP + $size);
    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";
    $fill_circle = $type == 'png' ? '' : "<ellipse cx='{$cx}' cy='{$cy}' rx='{$e_rx}' ry='{$e_ry}' {$stroke} fill='{$fill}' />";


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$fill_circle}

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
    'dir' => __DIR__,
    'compress' => false
]);
