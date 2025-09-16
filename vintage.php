<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm

$half_x = $width / 2;
$half_y = $height / 2;

$start_x = get_per($width, 6.8);
$start_y = get_per($height, 10);


$per_x = get_per($width, 20); # X
$per_y = get_per($width, 16.7); # Y


$path = "M{$start_x},{$start_y}"; # Start Pos

# Top Portion
$path .= "H{$per_x}"; # Line Top Left
$path .= "Q {$half_x},-{$start_y} " . $width - $per_x . ",{$start_y}"; # Top Curve
$path .= "H" . ($width - $start_y); # Line Top Right


// Right Portion
$path .= "V {$per_y}"; # Line
$path .= "Q " . ($width + $start_x) . ",{$half_y} " . ($width - $start_x) . "," . $height - $per_y; # Curve
$path .= "V " . $height - $start_y; # Line


// Bottom Position
$path .= "H" . $width - $per_x; # Line
$path .= "Q {$half_x}," . ($start_y + $height) . " " .  $per_x . "," . $height - $start_y; # Top Curve
$path .= "H{$start_x}"; # Line


// Left Portion
$path .= "V" . $height - $per_y; # Line
$path .= "Q -{$start_x},{$half_y} {$start_x}, {$per_y}"; # Curve
$path .= "z"; # Line


// SVG 
$svg = <<<SVG
        <svg width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <g class="grid"></g>
            <path d="{$path}" fill="none" stroke="red" stroke-width="2" />
        </svg>
SVG;

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style>
        /* body {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
        } */

        .container {
            width: <?= $width ?>px;
            height: <?= $height ?>px;
            background: #eee;
            border: 1px solid #ddd;
            position: relative;

        }

        .container svg:first-child {
            border: 1px solid blue;
        }

        .container .target-shape {
            position: absolute;
            top: 0;
            left: 0px;
        }
    </style>
</head>

<body>
    <?= $svg ?>

</body>

</html>