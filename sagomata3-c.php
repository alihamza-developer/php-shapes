<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$frame = (bool) ($_GET['frame'] ?? null);
$padding = $FRAME_HOLES_GAP;

// For Holes
$count = intval($_GET['holes'] ?? 0); // (2)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer


# Path Info (don't moidfy)
$BASE_PATH = "M0 7l3 0c2,0 4,-2 4,-4l0 -3 151 0 0 3c0,1 0,2 1,3 0,1 1,1 2,1l4 0 0 55 -4 0c-1,0 -2,0 -2,1 -1,0 -1,1 -1,2l0 4 -151 0 0 -4c0,-1 0,-2 -1,-2 -1,-1 -2,-1 -3,-1l-3 0 0 -55z";
$PATH_WIDTH = 165;
$PATH_HEIGHT = 69;


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
    global $count;
    $positions = [
        'lc',
        'rc',
    ];
    if ($count < 2) $positions = [];

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

    $spacer = $data['spacer'];
    $pos = $data['pos'];
    $pdf_gap = $data['pdf_gap'];

    $h_h = $height / 2;
    $r = $size / 2;
    [$vert, $horiz] = str_split($pos);

    $x = ($vert === 'l') ? ($padding + $r + $pdf_gap) : $width - ($padding + $r + $pdf_gap);
    $y = $h_h;

    return make_hole([
        'x' => $x,
        'y' => $y,
        'spacer' => $spacer,
    ]);
}

// # Start Downloader
start_downloader([
    'dir' => __DIR__,
]);
