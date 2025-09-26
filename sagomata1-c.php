<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm
$frame = (bool) ($_GET['frame'] ?? null);


# Path Info (don't moidfy)
$BASE_PATH = "M56 3c-14,5 -25,6 -40,9 -11,2 -18,3 -15,18l10 44c2,9 10,16 20,15l40 0 40 0c9,1 18,-6 20,-15l10 -44c3,-15 -4,-16 -15,-18 -15,-3 -27,-4 -41,-9 -6,-2 -10,-3 -14,-3 -4,0 -9,1 -15,3z";
$PATH_WIDTH = 141;
$PATH_HEIGHT = 89;



// Get SVG
function get_svg($holes = "")
{
    global $STROKE_COLOR, $STROKE_WIDTH, $FRAME_WIDTH, $FRAME_GAP, $FRAME_WIDTH;
    global $width, $height, $frame;

    $path = get_resized_path($width, $height);

    // Frame Width,Height
    $f_w = $width - $FRAME_GAP - $FRAME_WIDTH;
    $f_h = $height - $FRAME_GAP - $FRAME_WIDTH;
    $path_frame = get_resized_path($f_w, $f_h);
    $f_x = $width / 2 - ($f_w / 2);
    $f_y = $height / 2 - ($f_h / 2);
    $path_frame = $frame ? "<g transform='translate($f_x,$f_y)'><path d='{$path_frame}' fill='none' stroke='black' stroke-width='{$FRAME_WIDTH}' /></g>" : "";

    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        {$holes}

        {$path_frame}
        <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
    </svg>
    SVG;

    return $svg;
}



function generate()
{
    return "";
}

# Start Downloader
start_downloader([
    'dir' => __DIR__,
    'compress' => false
]);
