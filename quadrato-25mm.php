<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 25; // px
$padding = $_GET['padding'] ?? 35; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";


// Generate Holes
function generate($spacer = null, $gap = 0): string
{
    global $STROKE_WIDTH, $STROKE_COLOR;
    global $width, $height, $padding, $count, $position, $size, $direction;

    $cx = $width / 2;
    $cy = $height / 2;
    $r  = $size / 2;

    $gx = $gap / 2;
    $gy = $gap / 2;

    // Normalizers
    $norm = static function ($s): string {
        $s = strtolower((string)$s);
        return str_replace(['-', '_', ' '], '', $s);
    };

    $dir = $norm($direction);
    if ($dir === 'h') $dir = 'horizontal';
    if ($dir === 'v') $dir = 'vertical';

    $pos = $norm($position ?? 'center');

    // Alias some common shorthands / typos
    $aliases = [
        'tl' => 'topleft',
        'tc' => 'topcenter',
        'tr' => 'topright',
        'lc' => 'leftcenter',
        'rc' => 'rightcenter',
        'bl' => 'bottomleft',
        'bc' => 'bottomcenter',
        'br' => 'bottomright',
        'c'  => 'center',
        'mid' => 'center',
        'middle' => 'center',
        'left' => 'leftcenter',
        'right' => 'rightcenter',
        'top' => 'topcenter',
        'bottom' => 'bottomcenter',
        'leftright' => 'rightcenter'
    ];
    $pos = $aliases[$pos] ?? $pos;

    // Circle or Image generator with global offsets
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy, $STROKE_WIDTH, $STROKE_COLOR): string {
        // Apply offsets
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            // Place image centered at ($x, $y)
            $xPos = $x - $size / 2;
            $yPos = $y - $size / 2;
            return "<image href='{$spacer}' x='{$xPos}' y='{$yPos}' width='{$size}' height='{$size}' />";
        }

        // Default: draw circle
        return "<circle stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' cx='{$x}' cy='{$y}' r='{$r}' fill='none' />";
    };


    $out = '';

    switch ((int)$count) {
        case 1: {
                $map = [
                    'topleft'      => [$padding, $padding],
                    'topcenter'    => [$cx, $padding],
                    'topright'     => [$width - $padding, $padding],
                    'leftcenter'   => [$padding, $cy],
                    'rightcenter'  => [$width - $padding, $cy],
                    'bottomleft'   => [$padding, $height - $padding],
                    'bottomcenter' => [$cx, $height - $padding],
                    'bottomright'  => [$width - $padding, $height - $padding],
                ];
                [$x, $y] = $map[$pos] ?? $map['topcenter'];
                $out .= $make($x, $y);
                break;
            }

        case 2: {
                if ($dir === 'horizontal') {
                    // Choose Y band via $position: top|center|bottom
                    $yMap = [
                        'topcenter' => $padding,
                        'center'    => $cy,
                        'bottomcenter' => $height - $padding,
                    ];
                    $y = $yMap[$pos] ?? $cy;
                    $out .= $make($padding, $y);
                    $out .= $make($width - $padding, $y);
                } else { // vertical (default)
                    // Choose X band via $position: left|center|right
                    $xMap = [
                        'leftcenter'  => $padding,
                        'center'      => $cx,
                        'rightcenter' => $width - $padding,
                    ];
                    $x = $xMap[$pos] ?? $cx;
                    $out .= $make($x, $padding);
                    $out .= $make($x, $height - $padding);
                }
                break;
            }

        case 4: {
                $coords = [
                    [$padding, $padding],
                    [$width - $padding, $padding],
                    [$padding, $height - $padding],
                    [$width - $padding, $height - $padding],
                ];
                foreach ($coords as [$x, $y]) {
                    $out .= $make($x, $y);
                }
                break;
            }

        case 6: {
                // 4 corners
                $coords = [
                    [$padding, $padding],
                    [$width - $padding, $padding],
                    [$padding, $height - $padding],
                    [$width - $padding, $height - $padding],
                ];

                if ($dir === 'vertical') {
                    $coords[] = [$padding, $cy];
                    $coords[] = [$width - $padding, $cy];
                } else {
                    $coords[] = [$cx, $padding];
                    $coords[] = [$cx, $height - $padding];
                }

                foreach ($coords as [$x, $y]) {
                    $out .= $make($x, $y);
                }
                break;
            }

        default:
            // zero or unsupported count -> no holes
            break;
    }

    return $out;
}

// Get SVG
function get_svg($holes, $type = "")
{
    global $PDF_OUTLINE_GAP, $width, $height, $radius, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;
    global $FILL_PATH_GAP, $size, $padding, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;


    $is_pdf = $type === 'pdf';
    $is_png = $type === 'png';
    $gap = $is_pdf ? $PDF_OUTLINE_GAP : 0;
    $plate_x = $gap / 2;
    $plate_y = $gap / 2;
    $outline_w = $width + $gap;
    $outline_h = $height + $gap;
    $outline_radius = $radius > 1 ? ($radius + ($gap / 2)) : 0;
    $radius += $radius > 1 ? $gap / 2 : 0;

    $outline = <<<SVG
                <!-- Outline -->
                <rect 
                x="0" 
                y="0" 
                width="{$outline_w}" 
                height="{$outline_h}" 
                rx="{$outline_radius}" 
                ry="{$outline_radius}" 
                fill="none" 
                stroke="{$PDF_OUTLINE_COLOR}" 
                stroke-width="{$PDF_OUTLINE_WIDTH}" 
                />
            SVG;

    $outline = $is_pdf ? $outline : '';


    $r_w = $width - ($FILL_PATH_GAP + $size);
    $r_h = $height - ($FILL_PATH_GAP + $size);

    $r_x = $width / 2 - ($r_w / 2);
    $r_y = $height / 2 - ($r_h / 2);

    $fill = $is_pdf ? "none" : "#000";
    $stroke = $is_pdf ? "stroke='{$FILL_P_STROKE_COLOR}' stroke-width='{$FILL_P_STROKE_WIDTH}'" : "";

    $fill_r = $type == 'png' ? '' : "<rect rx='{$radius}' ry='{$radius}' x='{$r_x}' y='{$r_y}' width='{$r_w}' height='{$r_h}' {$stroke} fill='{$fill}' />";



    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$outline_w}" height="{$outline_h}" viewBox="0 0 {$outline_w} {$outline_h}">
            {$fill_r}
            {$outline}
            <!-- Plate -->
            <rect
            x="{$plate_x}"
            y="{$plate_y}"
            width="{$width}"
            height="{$height}"
            rx="{$radius}"
            ry="{$radius}"
            fill="none"
            stroke='{$STROKE_COLOR}'
            storke-width='{$STROKE_WIDTH}'
            />

            {$holes}
        </svg>
    BODY;

    return $svg;
}


$svg = download_svg(false); # Download SVG
$png = download_png(); # Download PNG
$pdf = download_pdf(__DIR__); # Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
