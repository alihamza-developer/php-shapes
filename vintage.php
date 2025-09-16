<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 0; // px
$padding = $_GET['padding'] ?? 50; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? false; // Spacer
$position = $_GET['position'] ?? "";


// Get Path
function get_path()
{
    global $width, $height;
    $cords = "M264 793l-132 0c-11,0 -23,-4 -31,-13 -8,-8 -13,-19 -13,-31l0 -88c-117,-123 -117,-317 0,-440l0 -89c0,-11 5,-23 13,-31 8,-8 20,-13 31,-13l132 0c252,-117 542,-117 794,0l132 0c11,0 23,5 31,13 8,8 13,20 13,31l0 89c117,123 117,317 0,440l0 88c0,12 -5,23 -13,31 -8,9 -20,13 -31,13l-132 0c-252,118 -542,118 -794,0z";
    $path = preg_replace_callback('/-?\d+\.?\d*/', function ($m) use ($width, $height) {
        static $is_x = true;
        $scale_x = $width / 1322;
        $scale_y = $height / 882;
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

    $is_pdf = ($type === 'pdf');
    $path = get_path();

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
    global $STROKE_WIDTH, $STROKE_COLOR;
    global $width, $height, $padding, $count, $size, $position;

    $path = get_path();
    preg_match_all('/-?\d+\.?\d*/', $path, $matches);
    $cords = $matches[0];


    $r = $size / 2;
    // Offsets for spacing
    $gx = $gap / 2;
    $gy = $gap / 2;

    // Hole maker
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy, $STROKE_WIDTH, $STROKE_COLOR): string {
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            $xPos = $x - ($size / 2);
            $yPos = $y - ($size / 2);
            return "<image href=\"{$spacer}\" x=\"{$xPos}\" y=\"{$yPos}\" width=\"{$size}\" height=\"{$size}\" preserveAspectRatio=\"xMidYMid meet\" />";
        }

        return "<circle stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' cx='{$x}' cy='{$y}' r='{$r}' fill='none' />";
    };

    $x_cord = abs($cords[18]);
    $y_cord = abs($cords[41]);


    $out = '';

    switch ((int)$count) {

        case 2:
            if ($position == 'top') {
                $out .= $make($x_cord + $padding, $y_cord + $padding);
                $out .= $make(($width - $x_cord) - $padding, $y_cord + $padding);
            } else {
                $out .= $make($x_cord + $padding, ($height -  $y_cord) - $padding);
                $out .= $make(($width - $x_cord) - $padding, ($height -  $y_cord) - $padding);
            }
            break;

        case 4:
            // Four corners
            $out .= $make($x_cord + $padding, $y_cord + $padding);
            $out .= $make(($width - $x_cord) - $padding, $y_cord + $padding);

            $out .= $make($x_cord + $padding, ($height -  $y_cord) - $padding);
            $out .= $make(($width - $x_cord) - $padding, ($height -  $y_cord) - $padding);
            break;


        case 2:
            // if ($position == 'bottom') {

            //     $out .= $make($top_y, $bottom_y);
            //     $out .= $make($right_x, $bottom_y);
            // } else {
            //     $out .= $make($top_y, $top_y);
            //     $out .= $make($right_x, $top_y);
            // }
            // break;


        default:
            // unsupported -> no holes
            break;
    }

    return $out;
}

# Download Process
$svg = download_svg(); // Download SVG
$png = download_png(); // Download PNG
$pdf = download_pdf(__DIR__); // Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
