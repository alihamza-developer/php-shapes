<?php


// For Circle
$width = cm_to_px($_GET['width']); // 30 cm
$height = cm_to_px($_GET['height']); // 20 cm
$padding = $_GET['padding'] ?? 30; // px
$count = intval($_GET['holes'] ?? 0); // (1,2,4,6)