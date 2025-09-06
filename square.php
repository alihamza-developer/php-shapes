<?php

require_once "includes/functions.php";

define("PDF_OUTLINE_GAP", cm_to_px(0.4));
define("OUTPUT_PATH", "output/");
define("SPACERS_PATH", "./spacers");
@mkdir(OUTPUT_PATH);

// For Plate
$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$radius = $_GET['radius'] ?? 25; // px
$padding = $_GET['padding'] ?? 30; // px

// For Holes
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)
$size = mm_to_px($_GET['size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";

// Generate Holes
function generate($spacer = null, $gap = 0): string
{
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
    $make = static function ($x, $y) use ($r, $size, $spacer, $gx, $gy): string {
        // Apply offsets
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            // Place image centered at ($x, $y)
            $xPos = $x - $size / 2;
            $yPos = $y - $size / 2;
            return "<image href=\"{$spacer}\" x=\"{$xPos}\" y=\"{$yPos}\" width=\"{$size}\" height=\"{$size}\" />";
        }

        // Default: draw circle
        return "<circle stroke=\"#000\" stroke-width=\"1\" cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"#fff\" />";
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
function get_svg($content, $type = "")
{
    global $width, $height, $radius;

    $is_pdf = $type === 'pdf';
    $is_png = $type === 'png';
    $gap = $is_pdf ? PDF_OUTLINE_GAP : 0;
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
                stroke="blue" 
                stroke-width="1" 
                />
            SVG;

    $outline = $is_pdf ? $outline : '';

    $props = <<<PROPS
            stroke="#000"
            storke-width="1"
    PROPS;

    $props = $is_png ? "" : $props;


    $svg = <<<BODY
        <svg xmlns="http://www.w3.org/2000/svg" width="{$outline_w}" height="{$outline_h}" viewBox="0 0 {$outline_w} {$outline_h}">
            
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
            {$props}
            />

            {$content}
        </svg>
    BODY;

    return $svg;
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
    $spacers = generate(merge_path(SPACERS_PATH, $spacer));
    $svg = get_svg($spacers, 'png');

    $name = generate_file_name("png", OUTPUT_PATH, false);
    $path = merge_path(OUTPUT_PATH, $name);
    svg_to_png($svg, $path); // PNG Path
    return $name;
}

// Download PDF
function download_pdf($svg)
{
    global $width, $height;

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
    $dim_width = px_to_cm($width + PDF_OUTLINE_GAP);
    $dim_height = px_to_cm($height + PDF_OUTLINE_GAP);
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
    $path = merge_path(__DIR__, OUTPUT_PATH, $filename);
    $pdf->Output($path, "F");
    return $filename;
}

$svg = download_svg(); // Download SVG
$png = download_png(); // Download PNG

// Download PDF
$temp_svg = generate_file_name("svg");
file_put_contents($temp_svg, get_svg(generate(null, PDF_OUTLINE_GAP), 'pdf'));
$pdf = download_pdf($temp_svg); // Download PDF
@unlink($temp_svg);

// Print Output Files
echo json_encode([
    'svg' => $svg,
    'png' => $png,
    'pdf' => $pdf
]);
