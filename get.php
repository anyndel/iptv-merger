<?php

function RefreshDDNS(){

    $ddns = "imieipoderi.mooo.info.";

    $addr = dns_get_record($ddns,DNS_A);

    file_put_contents('./whitelisted/home.ip',$addr[0]['ip']);

    return $addr[0]['ip'];
}

if (!isset($_GET["r"]))
    return;

$ip = $_SERVER["REMOTE_ADDR"];

$whitelist = array_filter(scandir('./whitelisted'),function($v){ 
    return (strlen($v) > 3 && substr($v, -3) === '.ip');    
});

$pass = false;

foreach ($whitelist as $addressFile){
    $toMatch = file_get_contents("./whitelisted/".$addressFile);

    if (strcmp($ip, $toMatch) === 0)
    {
        $pass = true;
        break;
    }

}

if ($pass == FALSE){
    //try home ddns
    $freshHomeIP = RefreshDDNS();    

    if (strcmp($ip, $freshHomeIP) !== 0)
        return;
}

$keys = [];

foreach (scandir('./keys') as $key){
    if (strlen($key) < 6) continue;


    $keyName = substr($key, 0, strlen($key) - 3);

    $keys[$keyName] = file_get_contents('./keys/'.$key);
}

$res = $_GET["r"];
$foundKey = [];

preg_match("/\|(KEY\d+)\|/", $res, $foundKey);

$res = preg_replace("/\|KEY\d+\|/",$keys[$foundKey[1]],$res);

header('Location: '.$res);

?>