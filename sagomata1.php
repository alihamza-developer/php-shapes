<?php
require_once "includes/functions.php";

// For Plate
$width = cm_to_px($_GET['width']); // cm
$height = cm_to_px($_GET['height']); // cm



# Path Info (don't moidfy)
$BASE_PATH = "M56 3c-14,5 -25,6 -40,9 -11,2 -18,3 -15,18l10 44c2,9 10,16 20,15l40 0 40 0c9,1 18,-6 20,-15l10 -44c3,-15 -4,-16 -15,-18 -15,-3 -27,-4 -41,-9 -6,-2 -10,-3 -14,-3 -4,0 -9,1 -15,3z";
$PATH_WIDTH = 141;
$PATH_HEIGHT = 89;


// Get SVG
function get_svg($holes = "", $type = "")
{
    global $width, $height, $STROKE_COLOR, $STROKE_WIDTH, $PDF_OUTLINE_GAP, $PDF_OUTLINE_COLOR, $PDF_OUTLINE_WIDTH;

    $is_pdf = ($type === 'pdf');
    $path = get_resized_path($width, $height);



    // Padding transform for PDF
    $padding = $is_pdf ? $PDF_OUTLINE_GAP - 5 : 0;
    $scale_x = ($width - 2 * $padding) / $width;
    $scale_y = ($height - 2 * $padding) / $height;
    $translate = $padding;

    // Outline
    $outline = $is_pdf ? "<path d='{$path}' stroke='{$PDF_OUTLINE_COLOR}' stroke-width='{$PDF_OUTLINE_WIDTH}' fill='none' />" : "";

    $group_start = $is_pdf ? "<g transform='translate({$translate},{$translate}) scale({$scale_x},{$scale_y})'>" : '';
    $group_end = $is_pdf ? "</g>" : "";


    // Final SVG
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    {$outline}
    {$group_start}
        <path d="{$path}" stroke="{$STROKE_COLOR}" stroke-width="{$STROKE_WIDTH}" fill="none" />
    {$group_end}
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
]);
