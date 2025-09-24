<?php
require_once "includes/functions.php";
// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 0; // px
$padding = $_GET['padding'] ?? 40; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "";



# Path Info (don't moidfy)
$BASE_PATH = "M874 75c87,67 184,119 288,156 97,22 166,108 168,207 1,100 -71,188 -167,212 -104,36 -201,89 -288,156 -121,102 -298,103 -420,2 -87,-66 -184,-119 -287,-156 -97,-22 -167,-108 -168,-207 -1,-100 71,-188 167,-212 104,-38 201,-91 288,-157 121,-101 297,-101 419,-1z";
$PATH_WIDTH = 1330;
$PATH_HEIGHT = 883;



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
    global $width, $height, $count, $position, $direction;

    $path = get_resized_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);

    $positions = [
        'tc',
        'bc',
        'lc',
        'rc'
    ];

    if ($count === 1) {
        $positions = ['tc'];
    } else if ($count === 2)
        $positions = ($direction == 'vertical') ? ['tc', 'bc'] : ['lc', 'rc'];

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

    $h_w = $width  / 2;
    $h_h = $height / 2;
    $r = $size / 2;
    [$vert, $horiz] = str_split($pos);

    if ($horiz === 'c') {
        // top/bottom center
        if ($vert === 't') {
            $x = $h_w;
            $y = $padding + $r;
        } elseif ($vert === 'b') {
            $x = $h_w;
            $y = $height - ($padding + $r);
        }
        // left/right center
        elseif ($vert === 'l') {
            $x = $padding + $r + $pdf_gap;
            $y = $h_h;
        } elseif ($vert === 'r') {
            $x = $width - ($padding + $r + $pdf_gap);
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
    'dir' => __DIR__
]);
