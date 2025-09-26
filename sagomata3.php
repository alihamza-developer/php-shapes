<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 30; // px
// For Holes
$count = intval($_GET['holes'] ?? 0); // (2)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer


# Path Info (don't moidfy)
$BASE_PATH = "M0 7l3 0c2,0 4,-2 4,-4l0 -3 151 0 0 3c0,1 0,2 1,3 0,1 1,1 2,1l4 0 0 55 -4 0c-1,0 -2,0 -2,1 -1,0 -1,1 -1,2l0 4 -151 0 0 -4c0,-1 0,-2 -1,-2 -1,-1 -2,-1 -3,-1l-3 0 0 -55z";
$PATH_WIDTH = 165;
$PATH_HEIGHT = 69;

// Get SVG
function get_svg($holes = "", $type = "")
{
    global $width, $height, $STROKE_COLOR, $STROKE_WIDTH, $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH;

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
