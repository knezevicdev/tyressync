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

    function __construct($code, $program, $width, $height, $radius, $speedIndex, $loadIndex, $brand, $season, $xl, $rof, $price, $title, $source, $weight, $image = null)
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
    }

    function set_image($image)
    {
        $this->image = $image;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function get_brand()
    {
        return $this->brand;
    }
}