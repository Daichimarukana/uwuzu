<?php
//----------EXIF_Delete----------
//EXIFを削除するやつです。
function rotate($image, $exif){
    $orientation = $exif['Orientation'] ?? 1;

    switch ($orientation) {
        case 1: //no rotate
            break;
        case 2: //FLIP_HORIZONTAL
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 3: //ROTATE 180
            $image = imagerotate($image, 180, 0);
            break;
        case 4: //FLIP_VERTICAL
            imageflip($image, IMG_FLIP_VERTICAL);
            break;
        case 5: //ROTATE 270 FLIP_HORIZONTAL
            $image = imagerotate($image, 270, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 6: //ROTATE 90
            $image = imagerotate($image, 270, 0);
            break;
        case 7: //ROTATE 90 FLIP_HORIZONTAL
            $image = imagerotate($image, 90, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 8: //ROTATE 270
            $image = imagerotate($image, 90, 0);
            break;
    }
    return $image;
}
function delete_exif($extension, $path){
    $exifimgext = array(
		"jpg",
		"jpeg",
		"jfif",
		"pjpeg",
		"pjp",
		"hdp",
		"wdp",
		"jxr",
		"tiff",
		"tif",
		"png"
	);
	if(in_array($extension,$exifimgext)){
		$gd = imagecreatefromjpeg($path);
		$w = imagesx($gd);
		$h = imagesy($gd);
		$gd_out = imagecreatetruecolor($w,$h);
		imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
		$exif = exif_read_data($path); 
		$gd_out = rotate($gd_out, $exif);
		imagejpeg($gd_out, $path);
		imagedestroy($gd_out);
	}
}
//----------EXIF_Delete----------

?>