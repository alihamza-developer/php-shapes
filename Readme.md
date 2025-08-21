1. Run composer install command 
2. Run "php vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php -i "C:/Windows/Fonts/arial.ttf" command to add arial font 

3. Install Inkscape in your windows then goto functions.php and EDIT the INKSCAPE_PATH Variable 
4. Here is a sample shape generation URL
    square.php?width=30&height=20&holes=true&type=rounded&hole_size=10

# Total Params That we Get from URL 
width = Required (cm)
height  = Required (cm)
type = (cornor,rounded)  Default cornor

holes = (true,false) Required 
hole_size = Default 37 (mm)
hole_margin = Default 30 (px)