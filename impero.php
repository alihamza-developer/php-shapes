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
function get_path()
{
    global $width, $height;
    $cords = "M90 822c0,2 1,4 4,4 187,36 377,55 567,55 191,0 381,-19 568,-55 2,0 4,-2 3,-4 -9,-93 10,-186 54,-268 48,-68 48,-159 0,-228 -44,-81 -63,-174 -54,-267 1,-2 -1,-4 -3,-5 -187,-36 -377,-54 -568,-54 -190,0 -380,18 -567,54 -3,1 -4,3 -4,5 9,93 -10,185 -53,267 -49,69 -49,160 0,228 43,82 62,175 53,268z";
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
    global $width, $height, $padding, $count, $size, $position, $direction;

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


    $x_cord = abs($cords[84]) + abs($cords[76]);
    $y_cord = abs($cords[11]);


    $out = '';

    switch ((int)$count) {
        case 2:
            if ($position === 'bottom') {
                // 2 Bottom Corners
                $out .= $make($padding + ($x_cord + $r), $height - ($y_cord + $r + $padding));
                $out .= $make($width - ($padding + ($x_cord + $r)), $height - ($y_cord + $r + $padding));
            } else {
                // 2 Top Corners
                $out .= $make($padding + ($x_cord + $r), $y_cord + $r + $padding);
                $out .= $make($width - ($padding + ($x_cord + $r)), $y_cord + $r + $padding);
            }
            break;

        case 4:
            // 2 Top Corners
            $out .= $make($padding + ($x_cord + $r), $y_cord + $r + $padding);
            $out .= $make($width - ($padding + ($x_cord + $r)), $y_cord + $r + $padding);

            // 2 Bottom Corners
            $out .= $make($padding + ($x_cord + $r), $height - ($y_cord + $r + $padding));
            $out .= $make($width - ($padding + ($x_cord + $r)), $height - ($y_cord + $r + $padding));

            break;

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
