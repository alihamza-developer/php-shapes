<?php
require_once "includes/functions.php";
// For Circle
$size = cm_to_px($_GET['size']);
$padding = $_GET['padding'] ?? 25; // px

// Holes Info
$count = intval($_GET['holes'] ?? 0); // (1,2,4)
$hole_size = mm_to_px($_GET['hole_size'] ?? 10); // mm
$spacer = $_GET['spacer'] ?? null; // Spacer
$position = $_GET['position'] ?? "";
$direction = $_GET['direction'] ?? "vertical";



// Get SVG
function get_svg($holes, $type = "")
{
    global $size;

    $is_pdf = $type === 'pdf';
    $is_png = $type === 'png';
    $gap    = $is_pdf ? PDF_OUTLINE_GAP : 0;

    $cx = ($size + $gap) / 2;
    $cy = ($size + $gap) / 2;
    $r  = ($size / 2);
    $r -= $is_pdf ? PDF_OUTLINE_GAP + 2 : 2;
    $outline_r = $r + ($gap / 2);


    // Outline only for PDF
    $outline = <<<SVG
        <!-- Outline -->
        <circle 
            cx="{$cx}" 
            cy="{$cy}" 
            r="{$outline_r}" 
            fill="none" 
            stroke="blue" 
            stroke-width="1"
        />
    SVG;
    $outline = $is_pdf ? $outline : '';


    // Plate circle props
    $props = <<<PROPS
        stroke="#000"
        stroke-width="1"
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
                stroke="#333"
                stroke-width="1"
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
    $make = static function ($x, $y) use ($r, $hole_size, $spacer, $gx, $gy): string {
        $x += $gx ?? 0;
        $y += $gy ?? 0;

        if (!empty($spacer)) {
            $xPos = $x - $hole_size / 2;
            $yPos = $y - $hole_size / 2;
            return "<image href=\"{$spacer}\" x=\"{$xPos}\" y=\"{$yPos}\" width=\"{$hole_size}\" height=\"{$hole_size}\" />";
        }

        return "<circle stroke=\"#000\" stroke-width=\"1\" cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"#fff\" />";
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
    global $size;
    $width = $size;
    $height = $size;

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
