<?php
$CURVE = 70;

// Get SVG
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_COLOR, $PDF_OUTLINE_GAP, $PDF_OUTLINE_WIDTH, $CURVE;
    global $width, $height, $STROKE_COLOR, $STROKE_WIDTH;

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

    $top -= $gap;
    $right += $gap;
    $bottom += $gap;
    $b += $gap;

    // Outline (blue border) increase the cords to make it like a gap between main path and outine
    $outline = <<<PATH
        M 0 {$top}
        Q {$midX} 0 {$right} {$top}
        L {$right} {$bottom}
        Q {$midX} {$b} 0 {$bottom}
        Z
    PATH;
    $outline = $is_pdf ? $outline : "";
    // Main SVG
    $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
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
