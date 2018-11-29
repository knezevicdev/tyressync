<?php

namespace App;

use App\Product;

class ImageMatch {

    private const REMOVE = ["R-F", "flat run", "XL*", "RUN FLAT", "FLAT RUN", "4X4", "FR", "!!", "IO", "GRNX", "#", "(", ")", "YOKOHAMA", "YOK"];

    private $images;

    function __construct($images_url)
    {
        $files = json_decode(file_get_contents($images_url));
        $images = array_filter($files, function($file) { 
            return strpos($file, ".jpg") !== false || strpos($file, ".png") !== false; 
        });

        $this->images = $images;
    }

    public function get_image(Product $product)
    {
        $title = preg_replace("/\d?\d?\d?[\.]?([0-9]{2,3})?[\/]?\d?\d?\d?[\.]?[0-9]{2,3}[Z]?[Rr][0-9]{2,3}[Cc]?/", "", $product->get_title(), 1);
        $title = preg_replace("/([0-9]{2,3})?[\/]?[0-9]{2,3}[\w]/", "", $title, 1);

        foreach(self::REMOVE as $toRemove) {
            $title = str_ireplace($toRemove, "", $title);
        }

        $title = trim($title);
        $title = str_replace("/", "", $title);
        $title = str_replace(".", " ", $title);

        if($product->get_brand()=="Continental") {
            $title = str_replace("SSR", "", $title);
            $title = str_replace("MO", "", $title);
            $title = str_replace("VOL", "", $title);
            $title = str_replace("LXS", "", $title);
            $title = str_replace("*", "", $title);

            if(strpos($title, "TS") !== false  && strpos($title, "SUV") === false) {
                $tsPosition = strpos($title, "TS");
                $title = substr($title, 0, $tsPosition+2);
            }

            if(strpos($title, "ContiWin") !== false && strpos($title, "TS") !== false && strpos($title, "SUV") !== false) {
                $title = "ContiWin TS SUV";
            }

            if(strpos($title, "TS") === false && strpos($title, "ContiWin") !== false) {
                if(strpos($title, "XL") !== false) {
                    $title = "ContiWin XL";
                } else {
                    $title = "ContiWin";
                }
            }
        }

        $title = str_replace(" ", "", $title);
        $title = strtoupper($title);
        $bestIndex = -1;
        $bestImage = "";
        $brand = strtoupper(str_replace(" ", "", $product->get_brand()));

        $brandImages = array_filter($this->images, function($image) use($brand) {
            $tempImage = str_replace("-", "", $image);
            return strpos(strtoupper($tempImage), $brand) !== false; 
        });

        foreach($brandImages as $image) {
            //TOLERO ST330
            $tempImage = str_replace("-", "", $image);
            $tempImage = str_replace(".jpg", "", $tempImage);
            $tempImage = str_replace(".png", "", $tempImage);
            $similarity = similar_text($title, strtoupper($tempImage), $perc);
            if($perc > $bestIndex) {
                $bestIndex = $perc;
                $bestImage = $image;
            }
        }

        $imageUrl = $bestIndex!==-1?'https://autogumesrdanov.rs/product_images/'.$bestImage:"";

        return $imageUrl;
    }
}