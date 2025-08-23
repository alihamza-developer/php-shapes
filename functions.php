<?php
require_once __DIR__ . '/vendor/autoload.php';
define('INKSCAPE_PATH', '"C:\\Program Files\\Inkscape\\bin\\inkscape.exe"');
// server path here when you setup ok

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
        <path d='{$pathData}' fill='#333' stroke='black' fill-rule='evenodd'/>
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
