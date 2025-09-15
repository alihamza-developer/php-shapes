<?php
require_once "includes/functions.php";
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




// Get SVG for Ellipse
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    global $width, $height;
    $is_pdf = $type === 'pdf';
    $gap    = $is_pdf ? $PDF_OUTLINE_GAP : 0;

    $cx = ($width + $gap) / 2;
    $cy = ($height + $gap) / 2;
    $rx = ($width / 2);
    $ry = ($height / 2);

    // Shrink to keep stroke inside
    $rx -= $is_pdf ? $PDF_OUTLINE_GAP + 2 : 2;
    $ry -= $is_pdf ? $PDF_OUTLINE_GAP + 2 : 2;

    $outline_rx = $rx + ($gap / 2);
    $outline_ry = $ry + ($gap / 2);

    // Outline only for PDF
    $outline = <<<SVG
        <!-- Outline -->
        <ellipse 
            cx="{$cx}" 
            cy="{$cy}" 
            rx="{$outline_rx}" 
            ry="{$outline_ry}" 
            fill="none" 
            stroke="{$PDF_OUTLINE_COLOR}"
            stroke-width="{$PDF_OUTLINE_WIDTH}"
        />
    SVG;
    $outline = $is_pdf ? $outline : '';

    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$outline}

            <!-- Ellipse -->
            <ellipse
                cx="{$cx}"
                cy="{$cy}"
                rx="{$rx}"
                ry="{$ry}"
                stroke="{$STROKE_COLOR}"
                stroke-width="{$STROKE_WIDTH}"
                fill="none"
            />
            
            {$holes}
        </svg>
    BODY;

    return $svg;
}

// Generate Holes For Ellipse
function generate($spacer = null, $gap = 0)
{
    global $width, $height, $padding, $count, $size, $position, $direction, $STROKE_COLOR, $STROKE_WIDTH;

    $cx = $width / 2;
    $cy = $height / 2;
    $rx = ($width / 2) - $padding;   // horizontal radius
    $ry = ($height / 2) - $padding;  // vertical radius
    $r  = $size / 2;

    $gx = $gap / 2;
    $gy = $gap / 2;

    // Normalize inputs
    $norm = static function ($s): string {
        $s = strtolower((string)$s);
        return str_replace(['-', '_', ' '], '', $s);
    };
    $dir = $norm($direction);
    if ($dir === 'h') $dir = 'horizontal';
    if ($dir === 'v') $dir = 'vertical';
    $pos = $norm($position ?? 'top');
    $aliases = [
        'top'    => 'topcenter',
        'bottom' => 'bottomcenter',
        'left'   => 'leftcenter',
        'right'  => 'rightcenter',
    ];
    $pos = $aliases[$pos] ?? $pos;

    // Hole generator
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy, $STROKE_COLOR, $STROKE_WIDTH): string {
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            $xPos = $x - $size / 2;
            $yPos = $y - $size / 2;
            return "<image href='{$spacer}' x='{$xPos}' y='{$yPos}' width='{$size}' height='{$size}' />";
        }

        return "<circle stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' cx='{$x}' cy='{$y}' r='{$r}' fill='none' />";
    };

    $out = '';

    switch ((int)$count) {
        case 1: {
                $map = [
                    'topcenter'    => [$cx, $cy - $ry],
                    'bottomcenter' => [$cx, $cy + $ry],
                    'leftcenter'   => [$cx - $rx, $cy],
                    'rightcenter'  => [$cx + $rx, $cy],
                    'center'       => [$cx, $cy],
                ];
                [$x, $y] = $map[$pos] ?? $map['topcenter'];
                $out .= $make($x, $y);
                break;
            }

        case 2: {
                if ($dir === 'horizontal') {
                    $out .= $make($cx - $rx, $cy);
                    $out .= $make($cx + $rx, $cy);
                } else { // vertical
                    $out .= $make($cx, $cy - $ry);
                    $out .= $make($cx, $cy + $ry);
                }
                break;
            }

        case 4: {
                $coords = [
                    [$cx, $cy - $ry], // top
                    [$cx + $rx, $cy], // right
                    [$cx, $cy + $ry], // bottom
                    [$cx - $rx, $cy], // left
                ];
                foreach ($coords as [$x, $y]) {
                    $out .= $make($x, $y);
                }
                break;
            }

        default:
            // unsupported count
            break;
    }

    return $out;
}


$svg = download_svg(); // Download SVG
$png = download_png(); // Download PNG
$pdf = download_pdf(__DIR__); // Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
