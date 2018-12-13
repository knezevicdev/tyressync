<?php

namespace App;

class Product {
    protected $code;
    protected $program;
    protected $width;
    protected $height;
    protected $radius;
    protected $speedIndex;
    protected $loadIndex;
    protected $brand;
    protected $season;
    protected $xl;
    protected $rof;
    protected $price;
    protected $title;
    protected $source;
    protected $weight;
    protected $image;
    protected $wooId;
    protected $updateFields;

    function __construct($code, $program, $width, $height, $radius, $speedIndex, $loadIndex, $brand, $season, $xl, $rof, $price, $title, $source, $weight, $image = null, $wooId = null)
    {
        $this->code = $code;
        $this->program = $program;
        $this->width = $width;
        $this->height = $height;
        $this->radius = $radius;
        $this->speedIndex = $speedIndex;
        $this->loadIndex = $loadIndex;
        $this->brand = $brand;
        $this->season = $season;
        $this->xl = $xl;
        $this->rof = $rof;
        $this->price = $price;
        $this->title = $title;
        $this->source = $source;
        $this->weight = $weight;
        $this->image = $image;
        $this->wooId = $wooId;
    }

    function set_image($image)
    {
        $this->image = $image;
    }
    
    public function set_woo_id($wooId)
    {
        $this->$wooId = $wooId;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function get_brand()
    {
        return $this->brand;
    }

    public function __get($property)
    {
        return $this->{$property};
    }

    public function same_as(Product $product)
    {
        return $this->source === $product->source && $this->code === $product->code;
    }

    public function set_update_fields($updateFields)
    {
        $this->updateFields = $updateFields;
    }

    public function get_differences(Product $product)
    {
        $differences = [];

        if($this->code !== $product->code) $differences["code"] = [
            'new_product' => $this->code,
            'current_product' => $product->code
        ];
        if($this->program !== $product->program) $differences["program"] = [
            'new_product' => $this->program,
            'current_product' => $product->program
        ];
        if($this->width !== $product->width) $differences["width"] = [
            'new_product' => $this->width,
            'current_product' => $product->width
        ];
        if($this->height !== $product->height) $differences["height"] = [
            'new_product' => $this->height,
            'current_product' => $product->height
        ];
        if($this->radius !== $product->radius) $differences["radius"] = [
            'new_product' => $this->radius,
            'current_product' => $product->radius
        ];
        if($this->speedIndex !== $product->speedIndex) $differences["speedIndex"] = [
            'new_product' => $this->speedIndex,
            'current_product' => $product->speedIndex
        ];
        if($this->loadIndex !== $product->loadIndex) $differences["loadIndex"] = [
            'new_product' => $this->loadIndex,
            'current_product' => $product->loadIndex
        ];
        if($this->brand !== $product->brand) $differences["brand"] = [
            'new_product' => $this->brand,
            'current_product' => $product->brand
        ];
        if($this->season !== $product->season) $differences["test"] = [
            'new_product' => $this->code,
            'current_product' => $product->code
        ];
        if($this->xl !== $product->xl) $differences["xl"] = [
            'new_product' => $this->xl,
            'current_product' => $product->xl
        ];
        if($this->rof !== $product->rof) $differences["rof"] = [
            'new_product' => $this->rof,
            'current_product' => $product->rof
        ];
        if($this->price !== $product->price) $differences["price"] = [
            'new_product' => $this->price,
            'current_product' => $product->price
        ];
        if($this->title !== $product->title) $differences["title"] = [
            'new_product' => $this->title,
            'current_product' => $product->title
        ];
        if($this->source !== $product->source) $differences["source"] = [
            'new_product' => $this->source,
            'current_product' => $product->source
        ];
        if($this->weight !== $product->weight) $differences["weight"] = [
            'new_product' => $this->weight,
            'current_product' => $product->weight
        ];
        if(basename($this->image, '.jpg') !== basename($product->image, '.jpg') && str_replace(basename($this->image, '.jpg'), '', basename($product->image, '.jpg')) != '-1') $differences["image"] = [
            'new_product' => $this->image,
            'current_product' => $product->image
        ];

        return $differences;
    }

    public static function category_id($category_name)
    {
        $category_id = 2216;

        switch($category_name) {
            case "SUV (4×4) program":
                $category_id = 2218;
                break;
            case "Poluteretni program":
                $category_id = 2217;
                break;
            case "Moto program":
                $category_id = 2219;
                break;
        }

        return $category_id;
    }
}