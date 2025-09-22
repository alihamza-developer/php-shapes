<?php
require_once 'vendor/autoload.php';

define("PDF_OUTLINE_GAP", cm_to_px(0.4));
$PDF_OUTLINE_GAP = cm_to_px(0.4);
$PDF_OUTLINE_COLOR = "blue";
$PDF_OUTLINE_WIDTH = "0.33578";
$STROKE_WIDTH = 0.33578;
$STROKE_COLOR = "#2B2A29";
$FILL_PATH_GAP = mm_to_px(25);
$FILL_P_STROKE_COLOR = "red";
$FILL_P_STROKE_WIDTH = 1;

define("OUTPUT_PATH", "output/");
define("SPACERS_PATH", "./spacers");

@mkdir(OUTPUT_PATH);

define('INKSCAPE_PATH', '"C:\\Program Files\\Inkscape\\bin\\inkscape.exe"');

// CM to PX
function cm_to_px($cm)
{
    return $cm * (96 / 2.54);
}

// PX to CM
function px_to_cm($px)
{
    return $px / (96 / 2.54);
}

// MM to PX
function mm_to_px($mm)
{
    return $mm * (96 / 25.4);
}

// Merge url or paths
function merge_path(...$paths)
{
    $url = '';
    foreach ($paths as $path) {
        $path = trim($path);
        $path = trim($path, '/');
        if (strlen($path))
            $url .= "/$path";
    }
    $url = trim($url, '/');
    return $url;
}

// Compress SVG to single path
function compress_svg($svg)
{
    $xml = simplexml_load_string($svg);
    $width  = (float)$xml['width'];
    $height = (float)$xml['height'];
    $ns = $xml->getNamespaces(true);

    $pathData = "";

    // Rectangles
    foreach ($xml->rect as $rect) {
        $x  = (float)($rect['x'] ?? 0);
        $y  = (float)($rect['y'] ?? 0);
        $w  = (float)$rect['width'];
        $h  = (float)$rect['height'];
        $rx = (float)($rect['rx'] ?? 0);
        $ry = (float)($rect['ry'] ?? 0);

        if ($rx > 0 || $ry > 0) {
            $r = max($rx, $ry);
            $pathData .= "M {$x}," . ($y + $r) . "
              A{$r},{$r} 0 0 1 " . ($x + $r) . ",$y
              H " . ($x + $w - $r) . "
              A{$r},{$r} 0 0 1 " . ($x + $w) . "," . ($y + $r) . "
              V " . ($y + $h - $r) . "
              A{$r},{$r} 0 0 1 " . ($x + $w - $r) . "," . ($y + $h) . "
              H " . ($x + $r) . "
              A{$r},{$r} 0 0 1 {$x}," . ($y + $h - $r) . "
              Z ";
        } else {
            $pathData .= "M{$x},{$y} H" . ($x + $w) . " V" . ($y + $h) . " H{$x} Z ";
        }
    }

    // Circles
    foreach ($xml->circle as $circle) {
        $cx = (float)$circle['cx'];
        $cy = (float)$circle['cy'];
        $r  = (float)$circle['r'];
        $pathData .= "M " . ($cx - $r) . ",$cy
                      a{$r},{$r} 0 1,0 " . (2 * $r) . ",0
                      a{$r},{$r} 0 1,0 -" . (2 * $r) . ",0 Z ";
    }

    // Ellipses
    foreach ($xml->ellipse as $ellipse) {
        $cx = (float)$ellipse['cx'];
        $cy = (float)$ellipse['cy'];
        $rx = (float)$ellipse['rx'];
        $ry = (float)$ellipse['ry'];
        $pathData .= "M " . ($cx - $rx) . ",$cy
                      a{$rx},{$ry} 0 1,0 " . (2 * $rx) . ",0
                      a{$rx},{$ry} 0 1,0 -" . (2 * $rx) . ",0 Z ";
    }

    // Polygons
    foreach ($xml->polygon as $polygon) {
        $points = trim((string)$polygon['points']);
        if ($points) {
            $pathData .= "M $points Z ";
        }
    }

    // Polylines
    foreach ($xml->polyline as $polyline) {
        $points = trim((string)$polyline['points']);
        if ($points) {
            $pathData .= "M $points ";
        }
    }

    // Lines
    foreach ($xml->line as $line) {
        $x1 = (float)$line['x1'];
        $y1 = (float)$line['y1'];
        $x2 = (float)$line['x2'];
        $y2 = (float)$line['y2'];
        $pathData .= "M{$x1},{$y1} L{$x2},{$y2} ";
    }

    // Existing paths (just append)
    foreach ($xml->path as $path) {
        $d = trim((string)$path['d']);
        if ($d) $pathData .= $d . " ";
    }

    // Build final <svg>
    $newSvg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}'>
        <path d='{$pathData}' id='drawing-area' fill='#333' stroke='black' fill-rule='evenodd'/>
    </svg>";

    return $newSvg;
}

// Genere Random Name
function getRandom($length = 5)
{
    $random_str = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet .= "0123456789";
    $max = strlen($codeAlphabet);

    for ($i = 0; $i < $length; $i++) {
        $random_str .= $codeAlphabet[random_int(0, $max - 1)];
    }

    return $random_str;
}
// Generate file name
function generate_file_name($ext, $folder_dir = '', $return_full_path = false, $length = 5)
{
    $file_name = getRandom($length);
    $file_name .= '.' . $ext;
    if ($folder_dir === '') return $file_name;
    $file_location = $folder_dir . $file_name;
    if (file_exists($file_location)) {
        return generate_file_name($ext, $folder_dir);
    } else {
        if ($return_full_path) return $file_location;
        return $file_name;
    }
}

// SVG to PNG
function svg_to_png($svg, $output)
{
    $temp = generate_file_name("svg", "", true);
    file_put_contents($temp, $svg);

    $cmd = INKSCAPE_PATH . " --export-type=png --export-filename="
        . escapeshellarg($output) . " "
        . escapeshellarg($temp);
    exec($cmd, $output, $result);
    @unlink($temp);
}

function clear_output_dir($dir = "output")
{
    if (!is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/*'); // all files in dir

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // delete file
        } elseif (is_dir($file)) {
            // optional: delete subdirs too
            array_map('unlink', glob("$file/*.*"));
            rmdir($file);
        }
    }
}


// Download PDF
function download_pdf($dir)
{
    global $width, $height, $PDF_OUTLINE_GAP;
    $svg = generate_file_name("svg");
    $holes = generate(null, $PDF_OUTLINE_GAP);
    file_put_contents($svg, get_svg($holes, 'pdf'));

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
    @unlink($svg);
    return $filename;
}

// Download Cliping Mask
function download_svg($compress = true)
{
    $holes = get_spacer() ? "" : generate();
    $svg = get_svg($holes);
    if ($compress) $svg = compress_svg($svg, "svg");

    $name = generate_file_name("svg", OUTPUT_PATH, false);
    $path = merge_path(OUTPUT_PATH, $name);
    file_put_contents($path, $svg); // Svg Path
    return $name;
}

// Download PNG 
function download_png()
{
    $spacer = get_spacer();
    if (!$spacer) return false;
    $svg = get_svg(generate($spacer), 'png');
    $name = generate_file_name("png", OUTPUT_PATH, false);
    $path = merge_path(OUTPUT_PATH, $name);
    svg_to_png($svg, $path); // PNG Path
    return $name;
}
// Get Spacer
function get_spacer()
{
    global $spacer;
    $spacer_path = merge_path(SPACERS_PATH, $spacer);
    if (!$spacer || !file_exists($spacer_path)) return false;
    return $spacer_path;
}

function get_per($value, $percent)
{
    return ($value * $percent) / 100;
}
