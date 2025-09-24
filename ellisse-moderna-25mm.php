<?php
require_once "includes/functions.php";

$CURVE = 70;

// For Circle
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 40; // px

// Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$direction = $_GET['direction'] ?? "vertical";


// Get SVG
function get_svg($holes = "", $type = "")
{
    global $width, $height, $STROKE_COLOR, $STROKE_WIDTH;
    global $CURVE, $FILL_PATH_GAP, $size, $padding, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;

    $is_pdf = $type === 'pdf';

    $midX   = $width / 2;
    $top    = $CURVE;
    $left   = $STROKE_WIDTH;
    $right  = $width - 2;
    $bottom = $height - $CURVE;
    $b = $bottom + $CURVE;

    // Path
    $path = <<<PATH
        M {$left} {$top}
        Q {$midX} 0 {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} {$left} {$bottom}
        Z
    PATH;

    # Fill Path Start
    $f_width = $width - ($FILL_PATH_GAP + $size + $CURVE);
    $f_height = $height - ($FILL_PATH_GAP + $size + $CURVE);

    $midX   = $f_width / 2;
    $top    = $CURVE;
    $right  = $f_width - $STROKE_WIDTH;
    $bottom = $f_height - $CURVE;
    $b = $bottom + $CURVE;

    // Path for both outline + main stroke
    $path_fill = <<<PATH
        M {$left} {$top}
        Q {$midX} 0 {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} {$left} {$bottom}
        Z
    PATH;

    $f_x = $width / 2 - ($f_width / 2);
    $f_y = $height / 2 - ($f_height / 2);
    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";
    $fill_path_final = $type == "png" ? "" : "<g transform='translate($f_x,$f_y)'><path d='{$path_fill}' fill='{$fill}' {$stroke} /></g>";
    # Fill Path End


    // Main SVG
    $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$fill_path_final}
            <!-- Main Path -->
            <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
            {$holes}
        </svg>
    SVG;

    return $svg;
}

function generate($spacer = null)
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
    $r      = $size / 2;

    $gap = $CURVE + $padding + $r;

    $h_w = $width  / 2;
    $h_h = $height / 2;

    [$vert, $horiz] = str_split($pos); // e.g. "tc" → ["t","c"]

    if ($horiz === 'c') {
        // top/bottom center
        if ($vert === 't') {
            $x = $h_w;
            $y = $padding + $r + ($CURVE / 3);
        } elseif ($vert === 'b') {
            $x = $h_w;
            $y = $height - ($padding + $r + ($CURVE / 3));
        }
        // left/right center
        elseif ($vert === 'l') {
            $x = $padding + $r;
            $y = $h_h;
        } elseif ($vert === 'r') {
            $x = $width - ($padding + $r);
            $y = $h_h;
        }
    } else {
        // classic corners
        $x = ($horiz === 'l') ? ($padding + $r) : ($width - ($padding + $r));
        $y = ($vert  === 't') ? $gap           : ($height - $gap);
    }

    return make_hole([
        'x'      => $x,
        'y'      => $y,
        'spacer' => $spacer,
    ]);
}


# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false
]);
