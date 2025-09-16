<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm

$start_x = get_per($width, 12);
$start_y = get_per($height, 10);
$per_25 = get_per($width, 25);


$path = <<<PATH
    M{$start_x},{$start_y}
    L{$per_25},{$start_y}


PATH;







$svg = <<<SVG
        <svg width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <g class="grid"></g>
            <path d="{$path}" stroke="red" stroke-width="2" />
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
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
        }

        .container {
            width: <?= $width ?>px;
            height: <?= $height ?>px;
            background: #eee;
            border: 1px solid #ddd;
        }

        .container svg {
            border: 1px solid blue;
        }
    </style>
</head>

<body>
    <div class="container">

        <?= $svg ?>

        <svg width="<?= $width ?>" height="<?= $height ?>" viewBox="0 0 <?php $width ?> <?php $height ?>">
            <path d="M264 793l-132 0c-11,0 -23,-4 -31,-13 -8,-8 -13,-19 -13,-31l0 -88c-117,-123 -117,-317 0,-440l0 -89c0,-11 5,-23 13,-31 8,-8 20,-13 31,-13l132 0c252,-117 542,-117 794,0l132 0c11,0 23,5 31,13 8,8 13,20 13,31l0 89c117,123 117,317 0,440l0 88c0,12 -5,23 -13,31 -8,9 -20,13 -31,13l-132 0c-252,118 -542,118 -794,0z" stroke="#333" stroke-width="1"></path>
        </svg>
    </div>

</body>

</html>