<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$radius = $_GET['radius'] ?? 25; // px
$frame = (bool) ($_GET['frame'] ?? false);
$padding =  $FRAME_HOLES_GAP; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";

// Get SVG
function get_svg($holes)
{
    global $frame, $FRAME_GAP, $FRAME_WIDTH, $width, $height, $radius, $STROKE_WIDTH, $STROKE_COLOR;

    $gap_h = $FRAME_GAP / 2;
    $frame_w = $width - $FRAME_GAP - $FRAME_WIDTH;
    $frame_h = $height - $FRAME_GAP - $FRAME_WIDTH;
    $outline_radius = $radius > 1 ? ($radius + $gap_h) : 0;

    $radius += $radius > 1 ? $gap_h : 0;
    $f_x = ($width - $frame_w)  / 2;
    $f_y = ($height - $frame_h)  / 2;

    $frame_plate  = $frame ? <<<SVG
                <!-- Outline -->
                <rect 
                x="$f_x" 
                y="$f_y" 
                width="{$frame_w}" 
                height="{$frame_h}" 
                rx="{$outline_radius}" 
                ry="{$outline_radius}" 
                fill="none" 
                stroke="black" 
                stroke-width="{$FRAME_WIDTH}" 
                />
            SVG : "";


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            
            {$frame_plate}
            <!-- Plate -->
            <rect
            x="0"
            y="0"
            width="{$width}"
            height="{$height}"
            rx="{$radius}"
            ry="{$radius}"
            fill="none"
            stroke="{$STROKE_COLOR}"
            stroke-width="{$STROKE_WIDTH}"
            />

            {$holes}
        </svg>
    BODY;
    return $svg;
}

// Generate Holes
function generate($spacer = null, $gap = 0): string
{
    global $STROKE_WIDTH, $STROKE_COLOR, $FRAME_WIDTH, $FRAME_GAP;
    global $width, $height, $padding, $count, $position, $size, $direction, $frame;

    if ($count < 2) return "";
    else {
        $direction = "horizontal";
        $position = "center";
        $count = 2;
        if ($frame) $padding += $FRAME_WIDTH + $FRAME_GAP;
    }

    $gap  = 0;

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

# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false,
]);
