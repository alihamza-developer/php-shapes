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


// Get Path
function get_path($width, $height)
{
    $cords = "M269 40c-86,-27 -176,-41 -266,-40 -2,0 -3,1 -3,3 -1,90 13,180 40,266l0 59c-27,86 -41,176 -40,266 0,1 1,3 3,3 90,0 180,-13 266,-40l59 0c86,27 176,40 266,40 1,0 3,-2 3,-3 0,-90 -13,-180 -40,-266l0 -59c27,-86 40,-176 40,-266 0,-2 -2,-3 -3,-3 -90,-1 -180,13 -266,40l-59 0z";
    $path = preg_replace_callback('/-?\d+\.?\d*/', function ($m) use ($width, $height) {
        static $is_x = true;
        $scale_x = $width / 597;
        $scale_y = $height / 597;
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
    global $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_COLOR, $STROKE_WIDTH;
    global $width, $height;
    global $FILL_PATH_GAP, $size, $padding, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;

    $is_pdf = ($type === 'pdf');
    $path = get_path($width, $height);

    // Padding transform for PDF
    $padding_ = $is_pdf ? $PDF_OUTLINE_GAP - 5 : 0;
    $scale_x = ($width - 2 * $padding_) / $width;
    $scale_y = ($height - 2 * $padding_) / $height;
    $translate = $padding_;

    // Outline
    $outline = $is_pdf ? "<path d='{$path}' stroke='{$PDF_OUTLINE_COLOR}' stroke-width='{$PDF_OUTLINE_WIDTH}' fill='none' />" : "";

    $group_start = $is_pdf ? "<g transform='translate({$translate},{$translate}) scale({$scale_x},{$scale_y})'>" : '';
    $group_end = $is_pdf ? "</g>" : "";



    $f_width = $width - ($FILL_PATH_GAP + $size + $padding + ($is_pdf ? $PDF_OUTLINE_GAP : 0) + 10);
    $f_height = $height - ($FILL_PATH_GAP + $size + $padding + ($is_pdf ? $PDF_OUTLINE_GAP : 0) + 10);
    $path_fill = get_path($f_width, $f_height);
    $f_x = $width / 2 - ($f_width / 2);
    $f_y = $height / 2 - ($f_height / 2);
    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";
    $fill_path_final = $type == "png" ? "" : "<g transform='translate($f_x,$f_y)'><path d='{$path_fill}' fill='{$fill}' {$stroke} /></g>";



    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        {$outline}
        {$fill_path_final}
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
    global $STROKE_WIDTH, $STROKE_COLOR;
    global $width, $height, $padding, $count, $size, $position, $direction;

    $path = get_path($width, $height);
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];


    $r = $size / 2;
    // Offsets for spacing
    $gx = $gap / 2;
    $gy = $gap / 2;

    // Hole maker
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy, $STROKE_WIDTH, $STROKE_COLOR): string {

        if (!empty($spacer)) {
            $xPos = $x - ($size / 2);
            $yPos = $y - ($size / 2);
            return "<image href=\"{$spacer}\" x=\"{$xPos}\" y=\"{$yPos}\" width=\"{$size}\" height=\"{$size}\" preserveAspectRatio=\"xMidYMid meet\" />";
        }

        return "<circle stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' cx='{$x}' cy='{$y}' r='{$r}' fill='none' />";
    };


    $x_cord = abs($cords[66]);
    $y_cord = abs($cords[5]);


    $out = '';

    switch ((int)$count) {
        case 1:
            $out .= $make($width / 2, $y_cord + $r + $padding);
            break;
        case 2:

            if ($direction === 'horizontal') {

                // 2 top corners
                if ($position == 'bottom') {
                    // 2 Bottom Corners
                    $out .= $make($width - ($r + $padding), $height - ($r + $padding + $gap));
                    $out .= $make($r + $padding, $height - ($r + $padding + $gap));
                } else if ($position == 'center') {

                    // 2 center corners
                    $out .= $make(($r + $padding + $x_cord), $height / 2);
                    $out .= $make($width - ($r + $padding + $x_cord), $height / 2);
                } else {
                    // 2 Top Corners
                    $out .= $make($r + $padding, $r + $padding + $gap);
                    $out .= $make($width - ($r + $padding), $r + $padding + $gap);
                }
            } else {
                $out .= $make($width / 2, $y_cord + $r + $padding);
                $out .= $make($width / 2, $height - ($y_cord + $r + $padding));
            }

            break;

        case 4:
            // 2 Top Corners
            $out .= $make($r + $padding, $r + $padding + $gap);
            $out .= $make($width - ($r + $padding), $r + $padding + $gap);

            // 2 Bottom Corners
            $out .= $make($width - ($r + $padding), $height - ($r + $padding + $gap));
            $out .= $make($r + $padding, $height - ($r + $padding + $gap));
            break;

        default:
            // unsupported -> no holes
            break;
    }

    return $out;
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
