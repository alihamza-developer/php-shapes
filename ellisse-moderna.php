<?php
require_once "includes/functions.php";

$CURVE = 70;

// For Circle
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 50; // px

// Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";


// Get SVG
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_COLOR, $PDF_OUTLINE_GAP, $PDF_OUTLINE_WIDTH, $CURVE;
    global $width, $height, $STROKE_COLOR, $STROKE_WIDTH;

    $is_pdf = $type === 'pdf';
    $gap = $is_pdf ? ($PDF_OUTLINE_GAP - 8) : 0;

    $midX   = $width / 2;
    $top    = $CURVE + $gap;
    $left   = $gap;
    $right  = ($width - $gap) - $STROKE_WIDTH;
    $bottom = $height - $CURVE - $gap;

    $b = $bottom + $CURVE;

    // Path for both outline + main stroke
    $path = <<<PATH
        M {$left} {$top}
        Q {$midX} {$gap} {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} {$left} {$bottom}
        Z
    PATH;

    $top -= $gap;
    $right += $gap;
    $bottom += $gap;
    $b += $gap;

    // Outline (blue border) increase the cords to make it like a gap between main path and outine
    $outline = <<<PATH
        M 0 {$top}
        Q {$midX} 0 {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} 0 {$bottom}
        Z
    PATH;
    $outline = $is_pdf ? $outline : "";
    // Main SVG
    $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <!-- Outline Path -->
            <path d="{$outline}" stroke="{$PDF_OUTLINE_COLOR}" stroke-width="{$PDF_OUTLINE_WIDTH}" fill="none" />

            <!-- Main Path -->
            <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />

            {$holes}
        </svg>
    SVG;

    return $svg;
}

function generate($spacer = null, $gap = 0)
{
    global $count, $direction;

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
            'pdf_gap' => $gap
        ]);
    }

    return $out;
}

# Get Hole
function get_hole($data)
{
    global $padding, $width, $height, $size, $CURVE;

    $spacer = $data['spacer'];
    $pos    = $data['pos'];
    $pdf_gap    = $data['pdf_gap'];
    $r      = $size / 2;

    $gap = $CURVE + $padding + $r + $pdf_gap;

    $h_w = $width  / 2;
    $h_h = $height / 2;

    [$vert, $horiz] = str_split($pos); // e.g. "tc" → ["t","c"]

    if ($horiz === 'c') {
        // top/bottom center
        if ($vert === 't') {
            $x = $h_w + $pdf_gap;
            $y = $padding + $r + ($CURVE / 3);
        } elseif ($vert === 'b') {
            $x = $h_w + $pdf_gap;
            $y = $height - ($padding + $r + ($CURVE / 3));
        }
        // left/right center
        elseif ($vert === 'l') {
            $x = $padding + $r + $pdf_gap;
            $y = $h_h;
        } elseif ($vert === 'r') {
            $x = $width - ($padding + $r + $pdf_gap);
            $y = $h_h;
        }
    } else {
        // classic corners
        $x = ($horiz === 'l') ? ($padding + $r + $pdf_gap) : ($width - ($padding + $r + $pdf_gap));
        $y = ($vert  === 't') ? $gap : ($height - $gap);
    }

    return make_hole([
        'x'      => $x,
        'y'      => $y,
        'spacer' => $spacer,
    ]);
}


# Start Downloader
start_downloader([
    'dir' => __DIR__
]);
