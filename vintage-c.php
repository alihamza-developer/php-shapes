<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$frame = (bool) ($_GET['frame'] ?? null);
$padding = $FRAME_HOLES_GAP;

// For Holes
$count = intval($_GET['holes'] ?? 0);
$size = mm_to_px($_GET['size'] ?? 3); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";

# Path Info (don't moidfy)
$BASE_PATH = "M264 793l-132 0c-11,0 -23,-4 -31,-13 -8,-8 -13,-19 -13,-31l0 -88c-117,-123 -117,-317 0,-440l0 -89c0,-11 5,-23 13,-31 8,-8 20,-13 31,-13l132 0c252,-117 542,-117 794,0l132 0c11,0 23,5 31,13 8,8 13,20 13,31l0 89c117,123 117,317 0,440l0 88c0,12 -5,23 -13,31 -8,9 -20,13 -31,13l-132 0c-252,118 -542,118 -794,0z";
$PATH_WIDTH = 1322;
$PATH_HEIGHT = 882;


// Get SVG
function get_svg($holes = "")
{
    global $STROKE_COLOR, $STROKE_WIDTH, $FRAME_WIDTH, $FRAME_GAP, $FRAME_WIDTH;
    global $width, $height, $frame;

    $path = get_resized_path($width, $height);

    // Frame Width,Height
    $f_w = $width - $FRAME_GAP;
    $f_h = $height - $FRAME_GAP;
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
    global $width, $height, $count, $position, $frame, $FRAME_GAP, $FRAME_WIDTH;
    $gap = $frame ? $FRAME_WIDTH + $FRAME_GAP : 0;
    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];
    $x_cord = abs($cords[18]);
    $y_cord = abs($cords[41]);

    $positions = $count < 2 ? [] : ['lc', 'rc'];

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


    $r = $size / 2;
    $gap_x = $padding + $r;

    [$vert, $horiz] = str_split($pos);
    $x = ($vert === "l") ? $gap_x : $width - $gap_x;
    $y = $height / 2;

    return make_hole([
        'x' => $x,
        'y' => $y,
        'spacer' => $spacer,
    ]);
}

# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false
]);
