<?php

// Get SVG
function get_svg($holes = "", $type = "")
{
    global $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH, $STROKE_WIDTH, $STROKE_COLOR;

    global $width, $height, $radius;

    $is_pdf = ($type === 'pdf');
    $is_png = ($type === 'png');
    $gap = $is_pdf ? $PDF_OUTLINE_GAP : 0;
    $cx = $width / 2;
    $cy = $height / 2;
    $inset = $is_pdf ? ($gap / 2) : 0;

    // Plate corners
    $corners = [
        [$cx, $inset],                // top
        [$width - $inset, $cy],       // right
        [$cx, $height - $inset],      // bottom
        [$inset, $cy],                // left
    ];

    // Outline corners
    $outline_corners = [
        [$cx, 0],
        [$width, $cy],
        [$cx, $height],
        [0, $cy],
    ];

    // Utility: build a rounded path string given corners + radius
    $make_path = function ($corners, $r) {

        // Edge lengths
        $edge_lengths = array();
        for ($i = 0; $i < 4; $i++) {
            $j = ($i + 1) % 4;
            $dx = $corners[$j][0] - $corners[$i][0];
            $dy = $corners[$j][1] - $corners[$i][1];
            $edge_lengths[$i] = sqrt($dx * $dx + $dy * $dy);
        }
        $min_edge = min($edge_lengths);
        $r = min($r, $min_edge / 2.0);

        if ($r <= 0.000001) {
            // Sharp polygon fallback
            $pts = array();
            foreach ($corners as $p) {
                $pts[] = $p[0] . ',' . $p[1];
            }
            return 'M ' . implode(' L ', $pts) . ' Z';
        }

        $p_in = array();
        $p_out = array();

        for ($i = 0; $i < 4; $i++) {
            $prev = ($i + 3) % 4;
            $next = ($i + 1) % 4;

            $xi = $corners[$i][0];
            $yi = $corners[$i][1];

            // vector to previous
            $vpx = $corners[$prev][0] - $xi;
            $vpy = $corners[$prev][1] - $yi;
            $lenp = sqrt($vpx * $vpx + $vpy * $vpy);
            $npx = ($lenp > 0) ? $vpx / $lenp : 0;
            $npy = ($lenp > 0) ? $vpy / $lenp : 0;
            $p_in[$i] = array($xi + $npx * $r, $yi + $npy * $r);

            // vector to next
            $vnx = $corners[$next][0] - $xi;
            $vny = $corners[$next][1] - $yi;
            $lenn = sqrt($vnx * $vnx + $vny * $vny);
            $nnx = ($lenn > 0) ? $vnx / $lenn : 0;
            $nny = ($lenn > 0) ? $vny / $lenn : 0;
            $p_out[$i] = array($xi + $nnx * $r, $yi + $nny * $r);
        }

        // Build path
        $start = $p_in[0];
        $d = 'M ' . $start[0] . ' ' . $start[1] . ' ';

        for ($i = 0; $i < 4; $i++) {
            $corner_x = $corners[$i][0];
            $corner_y = $corners[$i][1];
            $out_x = $p_out[$i][0];
            $out_y = $p_out[$i][1];

            $d .= 'Q ' . $corner_x . ' ' . $corner_y . ' ' . $out_x . ' ' . $out_y . ' ';

            $next = ($i + 1) % 4;
            $next_in_x = $p_in[$next][0];
            $next_in_y = $p_in[$next][1];
            $d .= 'L ' . $next_in_x . ' ' . $next_in_y . ' ';
        }

        $d .= 'Z';
        return $d;
    };

    // Main rhombus path
    $r_requested = $radius > 0 ? (float)$radius : 0.0;
    $plate_path = $make_path($corners, $r_requested);

    // Outline path
    $outline_path = $make_path($outline_corners, $r_requested);
    $outline_svg  = $is_pdf ? "<path d='{$outline_path}' fill='none' stroke='{$PDF_OUTLINE_COLOR}' stroke-width='{$PDF_OUTLINE_WIDTH}' />" : '';


    // Final SVG
    $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            {$outline_svg}
            <path d="{$plate_path}" stroke='{$STROKE_COLOR}' stroke-width='{$STROKE_WIDTH}' fill='none' />
            {$holes}
        </svg>
    SVG;

    return $svg;
}

// Generate Rhombus Holes (same signature as other shapes)
function generate($spacer = null, $gap = 0)
{
    global $STROKE_WIDTH, $STROKE_COLOR;
    global $width, $height, $padding, $count, $size, $position, $direction;

    $cx = $width / 2;
    $cy = $height / 2;

    // global offsets
    $gx = $gap / 2;
    $gy = $gap / 2;

    $r = $size / 2;

    $norm = static function ($s): string {
        $s = strtolower((string)$s);
        return str_replace(['-', '_', ' '], '', $s);
    };

    $dir = $norm($direction);
    if ($dir === 'h') $dir = 'horizontal';
    if ($dir === 'v') $dir = 'vertical';

    $pos = $norm($position ?? 'top');

    $aliases = [
        'tc' => 'top',
        'topcenter' => 'top',
        'top' => 'top',
        'bc' => 'bottom',
        'bottomcenter' => 'bottom',
        'bottom' => 'bottom',
        'lc' => 'left',
        'leftcenter' => 'left',
        'left' => 'left',
        'rc' => 'right',
        'rightcenter' => 'right',
        'right' => 'right',
        'c' => 'center',
        'center' => 'center',
        'mid' => 'center',
        'middle' => 'center'
    ];
    $pos = $aliases[$pos] ?? $pos;

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

    $inset_x = $gx;
    $inset_y = $gy;

    $top_y    = $inset_y + ($padding ?? 0);
    $bottom_y = $height - $inset_y - ($padding ?? 0);
    $left_x   = $inset_x + ($padding ?? 0);
    $right_x  = $width - $inset_x - ($padding ?? 0);

    $map = [
        'top'    => [$cx, $top_y],
        'bottom' => [$cx, $bottom_y],
        'left'   => [$left_x, $cy],
        'right'  => [$right_x, $cy],
        'center' => [$cx, $cy],
    ];

    $out = '';

    switch ((int)$count) {
        case 1:
            [$x, $y] = $map[$pos] ?? $map['top'];
            $out .= $make($x, $y);
            break;

        case 2:
            if ($dir === 'horizontal') {
                // left + right
                $out .= $make(...$map['left']);
                $out .= $make(...$map['right']);
            } else { // vertical (default)
                // top + bottom
                $out .= $make(...$map['top']);
                $out .= $make(...$map['bottom']);
            }
            break;

        case 4:
            foreach (['top', 'right', 'bottom', 'left'] as $k) {
                $out .= $make(...$map[$k]);
            }
            break;

        default:
            // unsupported count -> no holes
            break;
    }

    return $out;
}

// Download Cliping Mask
function download_svg()
{
    $holes = generate();
    $svg = get_svg($holes);
    $svg = compress_svg($svg, "svg");

    $name = generate_file_name("svg", OUTPUT_PATH, false);
    $path = merge_path(OUTPUT_PATH, $name);
    file_put_contents($path, $svg); // Svg Path
    return $name;
}

// Download PNG 
function download_png()
{
    global $spacer;
    $spacer_path = merge_path(SPACERS_PATH, $spacer);
    if (!$spacer || !file_exists($spacer_path)) return false;
    $svg = get_svg(generate($spacer_path), 'png');
    $name = generate_file_name("png", OUTPUT_PATH, false);
    $path = merge_path(OUTPUT_PATH, $name);
    svg_to_png($svg, $path); // PNG Path
    return $name;
}

// Download PDF
function download_pdf($svg, $dir)
{
    global $width, $height, $PDF_OUTLINE_GAP;

    $pdf = new TCPDF(
        (($width > $height) ? 'L' : 'P'), // Orientation
        'pt',                             // Unit
        [$width, $height]
    );

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $pdf->ImageSVG($svg, 0, 0, $width, $height); // Placing SVG

    // Placing (Targhe Insegne) Logo
    $pdf->ImageSVG("assets/logo.svg", ($width / 2) - (368 / 2), ($height / 2) - 100, 368, 128, '', 'C', 'C', 0, false);

    // Placing (Dimension Text)
    $dim_width = px_to_cm($width + $PDF_OUTLINE_GAP);
    $dim_height = px_to_cm($height + $PDF_OUTLINE_GAP);
    $text = "Dimensioni File: {$dim_width}cm X {$dim_height}cm";
    $pdf->SetFont("arial", "B", 30);

    $props = [0, 0, $text, 0, false, true, 0, 0, 'C', false, '', 0, false, 'T', 'C'];
    $props[1] = ($height / 2) + 50;
    $pdf->Text(...$props);
    $props[1] += 40;
    $props[2] = "Dimensioni Selezionate: " . px_to_cm($width) . "cm X " . px_to_cm($height) . "cm";
    $pdf->Text(...$props);

    // Output PDF
    $filename = generate_file_name("pdf", OUTPUT_PATH, false);
    $path = merge_path($dir, OUTPUT_PATH, $filename);
    $pdf->Output($path, "F");
    return $filename;
}
