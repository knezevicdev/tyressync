<?php

namespace App;

use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;
use App\ProductsSync;
use App\Product;

class BakiSync extends ProductsSync {

    private const NOT_APPROVED = [
        "Teretna", "Agro/Industrijska", "Tigar Obuća", "Kart", "Proizvodi"
    ];
    private const MOTO = [
        "Moto/Scooter"
    ];
    private const BRANDS = [
        'Barum', 'Continental', 'Debica', 'Dunlop', 'General Tire', 'Goodyear', 'Kelly', 'Mitas', 'Pirelli', 'Sava', 'Sportiva', 'Tigar', 'Uniroyal', 'Viking', 'Vredestein'
    ];

    private const BASE_URL = "http://www.bakidoo.com/webservis/externalsite/baki/";
    private $secret_key;
    private $username;
    private $password;
    private $endpoint;
    private $client;
    private $products;

    function __construct($secret_key, $username, $password, $endpoint)
    {
        $this->secret_key = $secret_key;
        $this->username = $username;
        $this->password = $password;
        $this->endpoint = $endpoint;
    
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout'  => 360.0
        ]);
    }

    public function process_products()
    {
        $approvedTires = array_filter($this->products, function($product) { 
            return  !in_array($product["Kategorija"], self::NOT_APPROVED) 
                && $product["Sezona"] != "ROF" 
                && !in_array($product["Proizvodjac"], self::NOT_APPROVED_BRANDS) 
                && in_array($product["Proizvodjac"], self::BRANDS); 
        });

        $products = array_map(function($product) {
            preg_match("/\d?\d?\d?[\.]?([0-9]{2,3})?[\/]?\d?\d?\d?[\.]?[0-9]{2,3}[Z]?[Rr]?[\-]?[Bb]?[0-9]{2,3}[Mm]?[\/]?[Cc]?[Tt]?[Ll]?/", $product["Naziv"], $matches);
            $properties = "";

            if(count($matches)) {
                $properties = $matches[0];    
                $title = str_replace($properties, "", $product["Naziv"]);
                $properties = strtolower($matches[0]);
            } else {
                $title = $product["Naziv"];
            }

            preg_match("/([0-9]{2,3})?[\/]?[0-9]{2,3}[\w]/", $title, $matches2);
            $speedIndex = "nema";
            $loadIndex = "nema";
            if(count($matches2)>0) {
                $speedPart = strtolower(preg_replace('/[0-9]+/', '', $matches2[0]));
                $loadPart = preg_replace('/[A-Za-z]+/', '', $matches2[0]);
                if(array_key_exists($speedPart, self::SPEED_INDEX)) {
                    $speedIndex = self::SPEED_INDEX[$speedPart];
                }
        
                if(array_key_exists($loadPart, self::LOAD_INDEX)) {
                    $speedIndex = self::LOAD_INDEX[$loadPart];
                }
            }

            $season = "sve sezone";
            if($product["Sezona"] === "Letnja") $season = "letnja";
            if($product["Sezona"] === "Zimska") $season = "zimska";
            $program = "Putnički program";
            $suv = stripos($product["Naziv"], "suv");
            $x4 = stripos($product["Naziv"], "4x4");
            $poluteretni = stripos($properties, "c");

            if($suv !== false) {
                $program = "SUV (4×4) program";
            }
        
            if($x4 !== false || $product["Kategorija"] === "4x4") {
                $program = "SUV (4×4) program";
            }
        
            if($poluteretni !== false || $product["Kategorija"] === "Poluteretna") {
                $program = "Poluteretni program";
            }
        
            if(in_array($product["Kategorija"], self::MOTO)) {
                $program = "Moto program";
                $season = "letnja";
            }

            $price = floatval(str_replace(",", "", $product["Cena"]))/12*10;
            $price = $price * 0.75;
            $price = $price * 1.15;
            $price = $price * 1.20;

            $height = gettype($product["Visina"])==="string"?$product["Visina"]:"";
            $width = $product["Sirina"];
            $radius = $product["Precnik"];

            $brand = $product["Proizvodjac"];

            if($brand=="Good year") {
                $brand = "Goodyear";
            }

            $xl = (strpos(strtolower($product["Naziv"]), 'xl') !== false)?"da":"";
            $rof = (strpos(strtolower($product["Naziv"]), 'rof') !== false)?"da":"";
            $weight = floatval($product["Precnik"])<17?9:14;

            return new Product($product["ID"], $program, $width, $height, $radius, $speedIndex, $loadIndex, $brand, $season, $xl, $rof, $price, $product["Naziv"], "BAKI", $weight);

        }, $this->products);

        $this->products = $products;
    }

    public function fetch_products()
    {
        $response = $this->client->request('GET', $this->endpoint, [
            'query' => [
                'seckey'    => $this->secret_key,
                'user'      => $this->username,
                'pass'      => $this->password
            ]
        ]);

        $response = $response->getBody()->getContents();
        $response = str_replace("<![CDATA[", "", $response);
        $response = str_replace("]]>", "", $response);
        $response = simplexml_load_string($response);
        
        $products = [];
        foreach(((array)$response)["item"] as $product) $products[] = (array)$product;
        $this->products = $products;

        $this->process_products();
    }

    public function get_products()
    {
        return $this->products;
    }

    public function get_sync_name() {
        return 'BAKI';
    }
}