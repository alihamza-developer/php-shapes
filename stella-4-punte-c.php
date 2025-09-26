<?php
require_once "includes/functions.php";
// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$frame = $_GET['frame'] ?? false;
$padding = $FRAME_HOLES_GAP;

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 8); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$direction = $_GET['direction'] ?? "";


# Path Info (don't moidfy)
$BASE_PATH = "M269 40c-86,-27 -176,-41 -266,-40 -2,0 -3,1 -3,3 -1,90 13,180 40,266l0 59c-27,86 -41,176 -40,266 0,1 1,3 3,3 90,0 180,-13 266,-40l59 0c86,27 176,40 266,40 1,0 3,-2 3,-3 0,-90 -13,-180 -40,-266l0 -59c27,-86 40,-176 40,-266 0,-2 -2,-3 -3,-3 -90,-1 -180,13 -266,40l-59 0z";
$PATH_WIDTH = 597;
$PATH_HEIGHT = 597;


// Get SVG
function get_svg($holes = "")
{
    global $STROKE_COLOR, $STROKE_WIDTH, $FRAME_WIDTH, $FRAME_GAP, $FRAME_WIDTH;
    global $width, $height, $frame;

    $path = get_resized_path($width, $height);

    // Frame Width,Height
    $f_w = $width - $FRAME_GAP - $FRAME_WIDTH;
    $f_h = $height - $FRAME_GAP - $FRAME_WIDTH;
    $path_frame = get_resized_path($f_w, $f_h);
    $f_x = $width / 2 - ($f_w / 2);
    $f_y = $height / 2 - ($f_h / 2);
    $path_frame = $frame ? "<g transform='translate($f_x,$f_y)'><path d='{$path_frame}' fill='none' stroke='black' stroke-width='{$FRAME_WIDTH}' /></g>" : "";

    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        {$holes}

        {$path_frame}
        <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
    </svg>
    SVG;

    return $svg;
}


// Generate Holes
function generate($spacer = null, $gap = 0)
{
    global $width, $height, $count, $direction, $frame, $FRAME_GAP, $FRAME_WIDTH;

    $gap = $frame ? $FRAME_WIDTH + $FRAME_GAP : 0;
    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];

    $x_cord = abs($cords[66]);
    $y_cord = abs($cords[5]);

    $positions = [
        'tl',
        'tr',
        'bl',
        'br'
    ];

    if ($count === 2)
        $positions = ($direction == 'vertical') ? ['tc', 'bc'] : ['lc', 'rc'];

    $out = '';
    foreach ($positions as $pos) {
        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos,
            'y_cord' => $y_cord,
            'x_cord' => $x_cord,
            'pdf_gap' => $gap
        ]);
    }

    return $out;
}

# Get Hole
function get_hole($data)
{
    global $padding, $width, $height, $size;

    $spacer = $data['spacer'];
    $pos = $data['pos'];
    $x_cord = $data['x_cord'];
    $y_cord = $data['y_cord'];
    $pdf_gap = $data['pdf_gap'];

    $h_w = $width  / 2;
    $h_h = $height / 2;
    $r = $size / 2;
    $gap_x = $padding + $r + $pdf_gap;
    $gap_y = $padding + $r + $pdf_gap;
    [$vert, $horiz] = str_split($pos);

    $x = ($horiz === "l") ? $gap_x : $width - $gap_x;
    $y = ($vert === "t") ? $gap_y : $height - $gap_y;

    if ($horiz === 'c') {
        // top/bottom center
        if ($vert === 't') {
            $x = $h_w;
            $y = $padding + $r + $y_cord;
        } elseif ($vert === 'b') {
            $x = $h_w;
            $y = $height - ($padding + $r + $y_cord);
        }
        // left/right center
        elseif ($vert === 'l') {
            $x = $padding + $r + $pdf_gap + $x_cord;
            $y = $h_h;
        } elseif ($vert === 'r') {
            $x = $width - ($padding + $r + $pdf_gap + $x_cord);
            $y = $h_h;
        }
    }

    return make_hole([
        'x' => $x,
        'y' => $y,
        'spacer' => $spacer,
    ]);
}

# Start Downloader
start_downloader([
    'dir' => __DIR__,
]);
