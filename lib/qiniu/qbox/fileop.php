<?php

/**
 * func ImagePreviewURL(url string, thumbType int) => (urlImagePreview string)
 */
function QBox_FileOp_ImagePreviewURL($url, $thumbType) {
	return $url . '/imagePreview/' . $thumbType;
}

/**
 * func ImageInfoURL(url string) => (urlImageInfo string)
 */
function QBox_FileOp_ImageInfoURL($url) {
	return $url . '/imageInfo';
}

/*
 * 图像处理接口，生成最终的缩略图预览URL
 */
function QBox_FileOp_ImageMogrifyPreviewURL($src_img_url, $opts){
	return $src_img_url . '?' . QBox_FileOp_mkImageMogrifyParams($opts);
}

/*
 * 图像处理接口，生成图像处理的参数
 * opts = {
 *   "thumbnail": <ImageSizeGeometry>,
 *   "gravity": <GravityType>, =NorthWest, North, NorthEast, West, Center, East, SouthWest, South, SouthEast
 *   "crop": <ImageSizeAndOffsetGeometry>,
 *   "quality": <ImageQuality>,
 *   "rotate": <RotateDegree>,
 *   "format": <DestinationImageFormat>, =jpg, gif, png, tif, etc.
 *   "auto_orient": <TrueOrFalse>
 * }
 */
function QBox_FileOp_mkImageMogrifyParams($opts){
    $keys = array("thumbnail", "gravity", "crop", "quality", "rotate", "format");
    $params_string = "";
    foreach($keys as $key){
        if (isset($opts[$key]) && !empty($opts[$key])) {
            $params_string .= '/' . $key . '/' . $opts[$key];
        }
    }
    if(isset($opts["auto_orient"]) && $opts["auto_orient"] === true){
        $params_string .= "/auto-orient";
    }
    return 'imageMogr' . $params_string;
}
