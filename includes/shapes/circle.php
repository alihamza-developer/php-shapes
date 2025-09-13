<?php

// Get SVG
function get_svg($holes, $type = "")
{
    global $size, $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    $is_pdf = $type === 'pdf';
    $is_png = $type === 'png';
    $gap    = $is_pdf ? $PDF_OUTLINE_GAP : 0;

    $cx = ($size + $gap) / 2;
    $cy = ($size + $gap) / 2;
    $r  = ($size / 2);
    $r -= $is_pdf ? $PDF_OUTLINE_GAP + 2 : 2;
    $outline_r = $r + ($gap / 2);


    // Outline only for PDF
    $outline = <<<SVG
        <!-- Outline -->
        <circle 
            cx="{$cx}" 
            cy="{$cy}" 
            r="{$outline_r}" 
            fill="none" 
            stroke="{$PDF_OUTLINE_COLOR}" 
            stroke-width="{$PDF_OUTLINE_WIDTH}"
        />
    SVG;

    $outline = $is_pdf ? $outline : '';


    // Plate circle props
    $props = <<<PROPS
        stroke="{$STROKE_COLOR}"
        stroke-width="{$STROKE_WIDTH}"
    PROPS;
    $props = $is_png ? "" : $props;


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
            {$outline}

            <!-- Circle -->
            <circle
                cx="{$cx}"
                cy="{$cy}"
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

// Generate Holes For Circle
function generate($spacer = null, $gap = 0)
{
    global $STROKE_WIDTH, $STROKE_COLOR;
    global $size, $padding, $count, $hole_size, $position, $direction;

    $cx = $size / 2;
    $cy = $size / 2;
    $radius = ($size / 2) - $padding;
    $r = $hole_size / 2;

    $gx = $gap / 2;
    $gy = $gap / 2;

    // Normalizer
    $norm = static function ($s): string {
        $s = strtolower((string)$s);
        return str_replace(['-', '_', ' '], '', $s);
    };

    $dir = $norm($direction);
    if ($dir === 'h') $dir = 'horizontal';
    if ($dir === 'v') $dir = 'vertical';

    $pos = $norm($position ?? 'top');

    // Aliases
    $aliases = [
        'top'    => 'topcenter',
        'bottom' => 'bottomcenter',
        'left'   => 'leftcenter',
        'right'  => 'rightcenter',
    ];
    $pos = $aliases[$pos] ?? $pos;

    // Helper to generate SVG for a hole
    $make = static function ($x, $y) use ($r, $hole_size, $spacer, $gx, $gy, $STROKE_COLOR, $STROKE_WIDTH): string {
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            $xPos = $x - $hole_size / 2;
            $yPos = $y - $hole_size / 2;
            return "<image href='{$spacer}' x='{$xPos}' y='{$yPos}' width='{$hole_size}' height='{$hole_size}' />";
        }

        return "<circle stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' cx='{$x}' cy='{$y}' r='{$r}' fill='none' />";
    };

    $out = '';

    switch ((int)$count) {

        case 1: {
                $offset = $radius - $padding;

                $map = [
                    'topcenter'    => [$cx, $cy - $offset],
                    'bottomcenter' => [$cx, $cy + $offset],
                    'leftcenter'   => [$cx - $offset, $cy],
                    'rightcenter'  => [$cx + $offset, $cy],
                ];

                [$x, $y] = $map[$pos] ?? $map['topcenter'];
                $out .= $make($x, $y);
                break;
            }

        case 2: {
                $offset = $radius - $padding; // pull holes inward

                if ($dir === 'horizontal') {
                    $out .= $make($cx - $offset, $cy);
                    $out .= $make($cx + $offset, $cy);
                } else { // vertical
                    $out .= $make($cx, $cy - $offset);
                    $out .= $make($cx, $cy + $offset);
                }
                break;
            }


        case 4: {
                $offset = $radius - $padding;

                $coords = [
                    [$cx, $cy - $offset], // top
                    [$cx + $offset, $cy], // right
                    [$cx, $cy + $offset], // bottom
                    [$cx - $offset, $cy], // left
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
