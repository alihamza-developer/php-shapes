<?php
require_once "includes/functions.php";
// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$frame = (bool) ($_GET['frame'] ?? null);
$padding = $FRAME_HOLES_GAP;
// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 8); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "";


# Path Info (don't moidfy)
$BASE_PATH = "M0 87l0 770c0,7 3,13 9,18 5,4 12,5 18,4 181,-41 366,-62 551,-63 52,0 104,3 155,11 187,39 377,57 568,53 12,0 21,-10 21,-22l0 -772c0,-6 -2,-11 -6,-15 -5,-5 -10,-7 -16,-7 -191,4 -382,-14 -569,-53 -51,-7 -103,-11 -155,-11 -188,1 -376,23 -559,65 -10,3 -17,12 -17,22z";
$PATH_WIDTH = 1322;
$PATH_HEIGHT = 881;



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
    global $width, $height, $count, $position, $frame, $FRAME_GAP, $FRAME_WIDTH;
    $gap = $frame ? $FRAME_WIDTH + $FRAME_GAP : 0;

    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];
    $y_cord = abs($cords[1]);

    $positions = ['lc', 'rc'];
    if ($count < 2) $positions = [];
    $out = '';
    foreach ($positions as $pos) {

        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos,
            'y_cord' => $y_cord,
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


    [$vert, $horiz] = str_split($pos);
    $x = ($vert === "l") ? ($r + $padding) : ($width - ($padding + $r));
    $y =  $height / 2 + $r;

    return make_hole([
        'x' => $x,
        'y' => $y,
        'spacer' => $spacer,
    ]);
}

# Start Downloader
start_downloader([
    'dir' => __DIR__,
    "compress" => false
]);
