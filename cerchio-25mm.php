<?php
require_once "includes/functions.php";

# For Circle
$size = cm_to_px($_GET['size']);
$width = $size;
$height = $size;
$padding = $_GET['padding'] ?? 30; // px

# Circle Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$hole_size = mm_to_px($_GET['hole_size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$direction = $_GET['direction'] ?? "vertical";



// Get SVG
function get_svg($holes, $type = "")
{
    global $size, $hole_size, $FILL_P_STROKE_WIDTH, $FILL_P_STROKE_COLOR, $FILL_PATH_GAP, $STROKE_WIDTH, $STROKE_COLOR;

    $is_pdf = $type === 'pdf';
    $r  = $size / 2;

    $c_size = $r - ($FILL_PATH_GAP + $hole_size);
    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";

    $fill_circle = $type == 'png' ? '' : "<circle cx='{$r}' cy='{$r}' r='{$c_size}' {$stroke} fill='{$fill}' />";


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
            {$fill_circle}
            <!-- Circle -->
            <circle
                cx="{$r}"
                cy="{$r}"
                r="{$r}"
                stroke="{$STROKE_COLOR}"
                stroke-width="{$STROKE_WIDTH}"
                fill="none"
            />

            {$holes}
        </svg>
    BODY;

    return $svg;
}

// Generate Holes
function generate($spacer = null)
{
    global $count, $direction;


    $positions = ['tc', 'rc', 'bc', 'lc'];

    switch ((int)$count) {
        case 1:
            $positions = ['tc'];
            break;
        case 2:
            $positions = $direction === 'vertical' ? ['tc', 'bc'] : ['rc', 'lc'];
            break;
    }


    $out = '';
    foreach ($positions as $pos) {

        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos
        ]);
    }

    return $out;
}
# Get Hole
function get_hole($data)
{
    global $padding, $width, $height, $size, $hole_size;
    $size_old = $size;
    $size = $hole_size;
    $spacer   = $data['spacer'];
    $pos      = $data['pos'];
    $r   = $size / 2;
    $gap = ($padding + $r);
    $h_w = $width / 2;
    $h_h = $height / 2;

    switch ($pos) {
        case 'tc':
            $x = $h_w;
            $y = $gap;
            break;
        case 'bc':
            $x = $h_w;
            $y = $height - $gap;
            break;
        case 'lc':
            $x = $padding + $r;
            $y = $h_h;
            break;
        case 'rc':
            $x = $width - ($padding + $r);
            $y = $h_h;
            break;
        default:
            return ''; // ignore invalid pos
    }

    $hole =  make_hole([
        'x'      => $x,
        'y'      => $y,
        'spacer' => $spacer,
    ]);

    $size = $size_old;
    return $hole;
}


# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false
]);
