<?php
class Watermark {
    const WATERMARK = 'watermark.png';
    
    static function add($file,$wpath) {
        if(file_exists($file)) {
            $new_h = 1200; // new height for resize image
            $new_w = 1200; // new width for resize image
            $image = imagecreatefromjpeg($file);
            // Load the logo image
            $logoImage = imagecreatefrompng(self::getWebDir() . self::WATERMARK);

            // Get dimensions
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);

            $logoWidth = imagesx($logoImage);
            $logoHeight = imagesy($logoImage);

           // Paste the lo$filego
            imagecopy(
                    // destination
                    $image,
                    // source
                    $logoImage,
                    // destination x and y 
                    ($imageWidth-$logoWidth)/2, ($imageHeight-$logoHeight)/2,
                    // source x and y
                    0, 0,
                    // width and height of the area of the source to copy
                    $logoWidth, $logoHeight
                    );
            // Paste the logo
            $new_image = imagecreatetruecolor ($new_w, $new_h);

            imagecopyresampled($new_image,$image, 0, 0, 0, 0, $new_w, $new_h, $imageWidth, $imageHeight);

            $mime = self::getMimeType($file);
            $name = self::getName($file);
  
            if($mime == 'image/png') imagejpeg($new_image, $wpath, 100);
            elseif($mime == 'image/jpeg') imagejpeg($new_image, $wpath, 100);
            elseif($mime == 'image/gif') imagejpeg($new_image, $wpath, 100);
            
            // Release memory
            imageDestroy($image);
            imageDestroy($new_image);
            imageDestroy($logoImage);
        }
    }
    
    static function getMimeType($url) {
        $info = getimagesize($url);
        return $info['mime'];
    }
    
    static function getWebDir() {
        return __DIR__.'/';
    }

    static function getName($url) {
        $info = pathinfo($url);
        return $info['filename'];
    }
}
?>
