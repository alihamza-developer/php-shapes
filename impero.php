<?php
require_once "includes/functions.php";
// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 0; // px
$padding = $_GET['padding'] ?? 30; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "";

# Path Info (don't moidfy)
$BASE_PATH = "M90 822c0,2 1,4 4,4 187,36 377,55 567,55 191,0 381,-19 568,-55 2,0 4,-2 3,-4 -9,-93 10,-186 54,-268 48,-68 48,-159 0,-228 -44,-81 -63,-174 -54,-267 1,-2 -1,-4 -3,-5 -187,-36 -377,-54 -568,-54 -190,0 -380,18 -567,54 -3,1 -4,3 -4,5 9,93 -10,185 -53,267 -49,69 -49,160 0,228 43,82 62,175 53,268z";
$PATH_WIDTH = 1322;
$PATH_HEIGHT = 881;


// Get SVG
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_COLOR, $STROKE_WIDTH;
    global $width, $height;

    $is_pdf = ($type === 'pdf');
    $path = get_resized_path($width, $height);

    // Padding transform for PDF
    $padding = $is_pdf ? $PDF_OUTLINE_GAP - 5 : 0;
    $scale_x = ($width - 2 * $padding) / $width;
    $scale_y = ($height - 2 * $padding) / $height;
    $translate = $padding;

    // Outline
    $outline = $is_pdf ? "<path d='{$path}' stroke='{$PDF_OUTLINE_COLOR}' stroke-width='{$PDF_OUTLINE_WIDTH}' fill='none' />" : "";

    $group_start = $is_pdf ? "<g transform='translate({$translate},{$translate}) scale({$scale_x},{$scale_y})'>" : '';
    $group_end = $is_pdf ? "</g>" : "";

    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        {$outline}
        {$holes}

        {$group_start}
            <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
        {$group_end}
    </svg>
    SVG;

    return $svg;
}

// Generate Holes
function generate($spacer = null, $gap = 0)
{
    global $width, $height, $count, $position;

    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];


    $x_cord = abs($cords[84]) + abs($cords[76]);
    $y_cord = abs($cords[11]);

    $positions = ['tl', 'tr', 'bl', 'br'];

    if ($count == 2)
        $positions = $position == 'top' ? ['tl', 'tr'] : ['bl', 'br'];

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


    $r = $size / 2;
    $gap_x = $x_cord + $padding + $r + $pdf_gap;
    $gap_y = $y_cord + $padding + $r + $pdf_gap;

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
]);
