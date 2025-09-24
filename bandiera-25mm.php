<?php
require_once "includes/functions.php";
// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$padding = $_GET['padding'] ?? 30; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "";


// Get Path
function get_path($width, $height)
{
    $cords = "M0 87l0 770c0,7 3,13 9,18 5,4 12,5 18,4 181,-41 366,-62 551,-63 52,0 104,3 155,11 187,39 377,57 568,53 12,0 21,-10 21,-22l0 -772c0,-6 -2,-11 -6,-15 -5,-5 -10,-7 -16,-7 -191,4 -382,-14 -569,-53 -51,-7 -103,-11 -155,-11 -188,1 -376,23 -559,65 -10,3 -17,12 -17,22z";
    $path = preg_replace_callback('/-?\d+\.?\d*/', function ($m) use ($width, $height) {
        static $is_x = true;
        $scale_x = $width / 1322;
        $scale_y = $height /  881;
        $val = (float)$m[0];
        if ($is_x) $val *= $scale_x;
        else $val *= $scale_y;
        $is_x = !$is_x;
        return $val;
    }, $cords);
    return $path;
}

// Get SVG
function get_svg($holes = "", $type = "")
{

    global $width, $height, $size, $padding, $STROKE_COLOR, $STROKE_WIDTH, $FILL_PATH_GAP, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;

    $path = get_path($width, $height);

    $f_width = $width - ($FILL_PATH_GAP + $size + $padding);
    $f_height = $height - ($FILL_PATH_GAP + $size + $padding);
    $path_fill = get_path($f_width, $f_height);
    $is_pdf = ($type === 'pdf');
    $f_x = $width / 2 - ($f_width / 2);
    $f_y = $height / 2 - ($f_height / 2);
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

    $path = get_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];
    $y_cord = abs($cords[1]);

    $positions = ['tl', 'tr', 'bl', 'br'];

    switch ((int)$count) {
        case 2:
            $positions = ($position === 'bottom')
                ? ['bl', 'br']
                : ['tl', 'tr'];
            break;
    }

    $out = '';
    foreach ($positions as $pos) {

        $out .= get_hole([
            'spacer' => $spacer,
            'pos' => $pos,
            'y_cord' => $y_cord
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
    $y_cord = $data['y_cord'];


    $r = $size / 2;
    $gap = $y_cord + $padding + $r;

    [$vert, $horiz] = str_split($pos);
    $x = ($horiz === "l") ? ($padding + $r) : ($width - ($padding + $r));
    $y = ($vert === "t") ? $gap : ($height - ($y_cord + $r));

    return make_hole([
        'x' => $x,
        'y' => $y,
        'spacer' => $spacer,
    ]);
}


# Download Process
$svg = download_svg(false); // Download SVG
$png = download_png(); // Download PNG
$pdf = download_pdf(__DIR__); // Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
