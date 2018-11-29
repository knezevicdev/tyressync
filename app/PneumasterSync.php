<?php

namespace App;

use GuzzleHttp\Client;
use App\ProductsSync;
use App\Product;

class PneumasterSync extends ProductsSync {

    private const NOT_APPROVED = [
        "FIRESTONE", "ROSAVA TER", "KETER", "ZETA", "TOLEDO", "DAMPAGRO", "UNUTRA&#x160;NJE", "FELNE", "OPR.SNEG", "AUTDEL", "ULJA", "AKUM", "REPRO", "RAZNROBA", "MAŠINE", "USLUGE", "NAMA", "ROSAVA AGR", "POWERTRUCK", "KORMORAN", "ALTAI", "KUMHO TER.", "RAZNE TER", "PIRELLI T", "LONG M", "BRIDG.TER.", "HAOHUA", "MICH TER", "NOKIANTER"
    ];
    private const MOTO = [
        "MOTORAZNO", "MOTO METZ", "MOTO PIR"
    ];
    private const SUMMER = [
        "PIRELLI L", "FORMULA L", "NOKIAN L", "KUMHO L", "MICHELIN L", "TIGAR L", "KLEBER L", "BFGOODR L", "GOODYEAR L", "DUNLOP L", "SAVA L", "MATADOR L", "TAURUS L", "STARMAXX L", "PETLAS L", "BRIDGEST.L", "YOKOHAMA L", "RAZPUTN L"
    ];
    private const WINTER = [
        "PIRELLI Z", "WPUTNST", "FORMULA Z", "NOKIAN Z", "NORDMAN Z", "KUMHO Z", "MICHELIN Z", "TIGAR Z", "KLEBER Z", "BFGOODR Z", "GOODYEAR Z", "DUNLOP Z", "SAVA Z", "MATADOR Z", "TAUR Z", "RADAR Z", "PACE Z", "STARMAXX Z", "BRIDGEST.Z", "YOKOHAMA Z", "RAZNPUT Z"
    ];
    private const BRANDS = [
        'BF Goodrich', 'Bridgestone', 'Firestone', 'Kama', 'Matador', 'Metzeler', 'Michelin', 'Nokian', 'Nordman', 'Pace', 'Petlas', 'Radar', 'Rossava', 'Starmaxx', 'Taurus', 'Yokohama'
    ];

    private const FIVE_HOURS = 18000;
    private const BASE_URL = "https://portal.wings.rs";
    private $username;
    private $password;
    private $sessionId;
    private $lastSessionFetch = 0;
    private $clientId;
    private $client;
    private $products;

    function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout'  => 360.0
        ]);
    }

    private function authenticate($force = false)
    {
        $currentTime = time();
        if($currentTime - $this->lastSessionFetch > self::FIVE_HOURS || $force) {
            $this->lastSessionFetch = $currentTime;

            $this->get_session_id();
            $this->login();
        }
    }

    private function get_session_id()
    {
        $response = $this->client->request('GET', '/pneumaster');

        if ($response->hasHeader('Set-Cookie')) {
            $cookies = $response->getHeader('Set-Cookie')[0];
            $start = strpos($cookies, "PHPSESSID=") + 10;
            $sessionId = substr($cookies, $start, strpos($cookies, ";", $start) - $start);
            $this->sessionId = $sessionId;
        }
    }

    private function login()
    {
        $response = $this->client->request('POST', '/process.php', [
            'form_params' => [
                'aUn'       => $this->username,
                'aUp'       => $this->password,
                'output'    => 'xml',
                'command'   => 'system.user.log',
                'aAuto'     => 1
            ],
            'headers' => $this->get_headers()
        ]);

        $response = $response->getBody()->getContents();
        $response = simplexml_load_string($response);

        $this->clientId = $response->userID;
    }

    private function get_headers()
    {
        return [
            'Cookie'            => "PHPSESSID={$this->sessionId}",
            'Origin'            => self::BASE_URL,
            'X-Requested-With'  => "XMLHttpRequest",
            'Referer'           => self::BASE_URL . "/pneumaster"
        ];
    }

    private function process_products()
    {
        $approvedProducts = array_filter($this->products, function($product) {
            return  !in_array($product[14], self::NOT_APPROVED) 
                && $product[4] !== "-" && $product[4] !== "" 
                && !in_array($product[4], self::NOT_APPROVED_BRANDS) 
                && in_array($product[4], self::BRANDS);
        });

        $products = array_map(function ($product) {
            preg_match("/\d?\d?\d?[\.]?([0-9]{2,3})?[\/]?\d?\d?\d?[\.]?[0-9]{2,3}[Z]?[Rr]?[\-]?[Bb]?[0-9]{2,3}[Mm]?[\/]?[Cc]?[Tt]?[Ll]?/", $product[3], $matches);
            $properties = count($matches) > 0 ? $matches[0] : "";    
            $title = str_replace($properties, "", $product[3]);
            $properties = strtolower($properties);
            $properties = str_replace("-", "r", $properties);
            $properties = str_replace("b", "r", $properties);

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
                    $loadIndex = self::LOAD_INDEX[$loadPart];
                }
            }

            $season = "sve sezone";
            if(in_array($product[14], self::SUMMER)) $season = "letnja";
            if(in_array($product[14], self::WINTER)) $season = "zimska";

            $program = "Putnički program";
            $suv = stripos($product[3], "suv");
            $x4 = stripos($product[3], "4x4");
            $poluteretni = stripos($properties, "c");

            if($suv !== false) {
                $program = "SUV (4×4) program";
            }

            if($x4 !== false) {
                $program = "SUV (4×4) program";
            }

            if($poluteretni !== false) {
                $program = "Poluteretni program";
            }

            if(in_array($product[14], self::MOTO)) {
                $program = "Moto program";
                $season = "letnja";
            }

            $dimensionProps = explode("r", $properties);

            $radius = preg_replace('/[A-Za-z\/]+/', '', count($dimensionProps) > 1 ? $dimensionProps[1] : '');
            $dimensions = preg_replace('/[A-Za-z]+/', '', count($dimensionProps) > 0 ? $dimensionProps[0] : '');
            $dimensions = explode("/", $dimensions);

            $width = $dimensions[0];
            $height = "nema";

            if(count($dimensions) > 1) {
                $height = $dimensions[1];
            } 

            $price = floatval(str_replace(",", "", $product[8])) * 0.7;
            $price = $price * 1.15;
            $price = $price * 1.20;

            $brand = $product[4];

            if($brand=="Good year") {
                $brand = "Goodyear";
            }
            
            $xl = (strpos(strtolower($product[3]), 'xl') !== false)?"da":"";
            $rof = (strpos(strtolower($product[3]), 'rof') !== false)?"da":"";
            $weight = floatval($radius)<17?9:14;

            return new Product($product[1], $program, $width, $height, $radius, $speedIndex, $loadIndex, $brand, $season, $xl, $rof, $price, $product[3], "Pneumaster", $weight);
        }, $approvedProducts);

        $this->products = $products;
    }

    public function fetch_products()
    {
        $this->authenticate();

        $response = $this->client->request('POST', '/process.php', [
            'form_params' => [
                'command'   => "local.cache.artikal",
                'output'    => "json",
                'params'    => '[{"partnerId":'. $this->clientId .',"magacin":"","dStart":0,"dLength":100,"dSearch":"","dVrste":"","dAtributi":"","modul":"ART"}]'
            ],
            'headers' => $this->get_headers()
        ]);

        $response = $response->getBody()->getContents();
        $response = simplexml_load_string($response);

        $this->products = json_decode($response->command)[1];
        $this->process_products();
    }


    public function get_products()
    {
        return $this->products;
    }

    public function get_sync_name() {
        return 'PNEUMASTER';
    }
}