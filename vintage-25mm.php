<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 0; // px
$padding = $_GET['padding'] ?? 10; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";

# Path Info (don't moidfy)
$BASE_PATH = "M264 793l-132 0c-11,0 -23,-4 -31,-13 -8,-8 -13,-19 -13,-31l0 -88c-117,-123 -117,-317 0,-440l0 -89c0,-11 5,-23 13,-31 8,-8 20,-13 31,-13l132 0c252,-117 542,-117 794,0l132 0c11,0 23,5 31,13 8,8 13,20 13,31l0 89c117,123 117,317 0,440l0 88c0,12 -5,23 -13,31 -8,9 -20,13 -31,13l-132 0c-252,118 -542,118 -794,0z";
$PATH_WIDTH = 1322;
$PATH_HEIGHT = 882;

// Get SVG
function get_svg($holes = "", $type = "")
{
    global $STROKE_COLOR, $STROKE_WIDTH;
    global $width, $height;
    global $FILL_PATH_GAP, $size, $padding, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;


    $is_pdf = ($type === 'pdf');
    $path = get_resized_path($width, $height);



    $f_width = $width - ($FILL_PATH_GAP + $size + $padding + 50);
    $f_height = $height - ($FILL_PATH_GAP + $size + $padding + 50);
    $path_fill = get_resized_path($f_width, $f_height);
    $f_x = ($width) / 2 - ($f_width / 2);
    $f_y = ($height  - 2) / 2 - ($f_height / 2);
    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";
    $fill_path_final = $type == "png" ? "" : "<g transform='translate($f_x,$f_y)'><path d='{$path_fill}' fill='{$fill}' {$stroke} /></g>";



    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        {$fill_path_final}
        {$holes}
        <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
    </svg>
    SVG;

    return $svg;
}

// Generate Holes
function generate($spacer = null)
{
    global $width, $height, $count, $position;

    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];


    $x_cord = abs($cords[18]);
    $y_cord = abs($cords[41]);

    $positions = ['tl', 'tr', 'bl', 'br'];

    if ($count == 2)
        $positions = $position == 'top' ? ['tl', 'tr'] : ['bl', 'br'];

    $out = '';
    foreach ($positions as $pos) {
        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos,
            'y_cord' => $y_cord,
            'x_cord' => $x_cord
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


    $r = $size / 2;
    $gap_x = $x_cord + $padding + $r;
    $gap_y = $y_cord + $padding + $r;

    [$vert, $horiz] = str_split($pos);
    $x = ($horiz === "l") ? $gap_x : $width - $gap_x;
    $y = ($vert === "t") ? $gap_y : $height - $gap_y;

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
