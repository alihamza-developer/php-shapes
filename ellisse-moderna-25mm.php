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
    global $FILL_PATH_GAP, $size, $padding, $FILL_P_STROKE_COLOR, $FILL_P_STROKE_WIDTH;

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



    # Outline Path Start
    $top -= $gap;
    $right += $gap;
    $bottom += $gap;
    $b += $gap;

    $outline = <<<PATH
        M 0 {$top}
        Q {$midX} 0 {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} 0 {$bottom}
        Z
    PATH;
    $outline = $is_pdf ? $outline : "";
    # Outline Path End



    # Fill Path Start
    $f_width = $width - ($FILL_PATH_GAP + $size + $padding + ($is_pdf ? $PDF_OUTLINE_GAP : 0) + 10);
    $f_height = $height - ($FILL_PATH_GAP + $size + $padding + ($is_pdf ? $PDF_OUTLINE_GAP : 0) + 10);

    $midX   = $f_width / 2;
    $top    = $CURVE + $gap;
    $left   = $gap;
    $right  = ($f_width - $gap) - $STROKE_WIDTH;
    $bottom = $f_height - $CURVE - $gap;
    $b = $bottom + $CURVE;

    // Path for both outline + main stroke
    $path_fill = <<<PATH
        M {$left} {$top}
        Q {$midX} {$gap} {$right} {$top}
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
            <!-- Outline Path -->
            <path d="{$outline}" stroke="{$PDF_OUTLINE_COLOR}" stroke-width="{$PDF_OUTLINE_WIDTH}" fill="none" />

            <!-- Main Path -->
            <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />

            {$holes}
        </svg>
    SVG;

    return $svg;
}

// Generate Holes for the curved-top/bottom plate (supports 2 and 4 holes)
function generate($spacer = null, $gap = 0): string
{
    global $width, $height, $padding, $count, $size, $position, $direction;
    global $STROKE_COLOR, $STROKE_WIDTH, $CURVE;

    $r = $size / 2;
    $gx = $gap / 2;
    $gy = $gap / 2;


    // Normalizer & aliases (same style as square generator)
    $norm = static function ($s): string {
        $s = strtolower((string)$s);
        return str_replace(['-', '_', ' '], '', $s);
    };

    $dir = $norm($direction);
    if ($dir === 'h') $dir = 'horizontal';
    if ($dir === 'v') $dir = 'vertical';

    // Hole maker (image or circle). apply gap offsets inside.
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy, $STROKE_COLOR, $STROKE_WIDTH): string {

        if (!empty($spacer)) {
            $xPos = $x - $size / 2;
            $yPos = $y - $size / 2;
            return "<image href=\"{$spacer}\" x=\"{$xPos}\" y=\"{$yPos}\" width=\"{$size}\" height=\"{$size}\" preserveAspectRatio=\"xMidYMid meet\" />";
        }

        return "<circle stroke=\"{$STROKE_COLOR}\" stroke-width=\"{$STROKE_WIDTH}\" cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"none\" />";
    };

    $out = '';

    switch ((int)$count) {
        // Two holes
        case 2:

            if ($dir === 'horizontal') {
                $out .= $make($padding, ($height / 2));
                $out .= $make(($width - $padding), ($height / 2));
            } else {
                $out .= $make(($width / 2) - $r, (($CURVE / 2) + $padding));
                $out .= $make(($width / 2) - $r, (($height - $padding) - $CURVE / 2) - $gx);
            }
            break;

        case 4:
            $out .= $make($padding, $padding + $CURVE); // Top Left
            $out .= $make(($width - $padding) - $gx, ($padding + $CURVE)); // Top Right
            $out .= $make(($width - $padding) - $gx, ($height - $padding) - $CURVE); // Bottom Right
            $out .= $make($padding, $height - ($padding + $CURVE)); // Bottom Left

        default:
            // unsupported count -> no holes
            break;
    }

    return $out;
}


$svg = download_svg(false); // Download SVG
$png = download_png(); // Download PNG
$pdf = download_pdf(__DIR__); // Download PDF

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
