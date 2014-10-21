<?php

$priedai = array(
    'exec'   => array('fja' => 'execas',     'param' => 'min', 'kiek' => 1, 'teise' => 10),
    'dns'    => array('fja' => 'dns',        'param' => 'equ', 'kiek' => 1, 'teise' => 5),
    'port'   => array('fja' => 'port',       'param' => 'equ', 'kiek' => 2, 'teise' => 5),
    'domreg' => array('fja' => 'domaininfo', 'param' => 'equ', 'kiek' => 1, 'teise' => 5),
    'whois'  => array('fja' => 'ipinfo',     'param' => 'equ', 'kiek' => 1, 'teise' => 5),
    //'tv'     => array('fja' => 'tvprograma', 'param' => 'min', 'kiek' => 1, 'teise' => 5),//removed for github
    'nextep' => array('fja' => 'epizodas',   'param' => 'min', 'kiek' => 1, 'teise' => 5)//,
    //'oras'   => array('fja' => 'oras',       'param' => 'min', 'kiek' => 1, 'teise' => 5)//removed for github
);

function execas($exec, $gavejas) {
    if (function_exists('exec')) {
        exec($exec, $atsakymas);
    }
    elseif (function_exists('shell_exec')) {
        $atsakymas = explode("\n", shell_exec($exec));
    }
    else {
        zinute($gavejas, 'exec negalimas');
        return null;
    }
    if (!$atsakymas) {
        zinute($gavejas, 'Atsakymas tuščias');
        return null;
    }
    foreach ($atsakymas as $eilute) {
        if ($eilute != '') {
            zinute($gavejas, $eilute);
        }
    }
}

function dns($adresas, $gavejas) {
    if (function_exists('dns_get_record')) {
        // ipv4
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $adresas)) {
            $adresas = explode('.', $adresas);
            $adresas = array_reverse($adresas);
            $adresas = implode('.', $adresas);
            $adresas = $adresas.'.in-addr.arpa';
        }
        // ipv6
        elseif (strpos($adresas, ':') !== FALSE) {
            $adresas = explode(':', $adresas);
            // nudeginam ::
            if (($truksta = 8-count($adresas)) != 0) {
                foreach ($adresas as $narys) {
                    if ($narys == '') {
                        for ($i=0 ; $i <= $truksta ; $i++) {
                            $v6naujas[] = '0';
                        }
                    }
                    else {
                        $v6naujas[] = $narys;
                    }
                }
                $adresas = $v6naujas;
            }
            // užpildom '0' iki keturių
            foreach ($adresas as $key => $narys) {
                if (($truksta = 4-strlen($narys)) != 0) {
                    $adresas[$key] = str_repeat('0', $truksta).$narys;
                }
            }
            // sujungiam sutvarkytą be :
            $adresas = implode($adresas);
            // arpinam
            $arpa = 'ip6.arpa';
            for ($i=0 ; $i < 32 ; $i++) {
                $arpa = substr($adresas, $i, 1).'.'.$arpa;
            }
            $adresas = $arpa;
        }
        
        // resolve
        
        $dns = dns_get_record($adresas, DNS_ALL);
        if (!$dns) {
            zinute($gavejas, 'Nėra informacijos');
            return null;
        }
        foreach ($dns as $skiltis) {
            $zinute = '';
            foreach ($skiltis as $pavadinimas => $reiksme) {
                $zinute .= '['.$pavadinimas.': '.$reiksme.'] ';
            }
            zinute($gavejas, $zinute);
        }
    }
    else {
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $adresas)) { // ||
            //(strpos($adresas, ':') !== FALSE)) {
            $dns = gethostbyaddr($adresas); // ant seno PHP nėra v6 ir meta warning, jei malformed
        }
        else {
            $dns = gethostbyname($adresas);
        }
        
        if (($dns === FALSE) || ($dns == $adresas)) {
            zinute($gavejas, 'Nėra informacijos');
        }
        else {
            zinute($gavejas, $adresas.' => '.$dns);
        }
    }
}

function port($adrpor, $gavejas) {
    $adrpor = explode(' ', $adrpor);
    $port = @fsockopen($adrpor[0], $adrpor[1], $errno, $errstr, 3);
    if ($port) {
        fclose($port);
        zinute($gavejas, 'Portas atidarytas');
    }
    else {
        zinute($gavejas, 'Portas uždarytas. '.$errstr);
    }
}

function domaininfo($domenas, $gavejas) {
    $domenas = strtolower($domenas);
    if (preg_match('/\.([a-z]{2,})$/', $domenas, $rezultatai)) {
        // išimtys
        if (($rezultatai[1] == 'com') || ($rezultatai[1] == 'net')) {
            $domenas = 'domain '.$domenas;
        }
        
        $socketas = @fsockopen($rezultatai[1].'.whois-servers.net', 43, $errno, $errstr, 3);
        if ($socketas) {
            fwrite($socketas, $domenas."\r\n");
            $atsakymas = '';
            while (!feof($socketas)) {
                $atsakymas .= fread($socketas, 4096);
            }
            fclose($socketas);
            
            $turiminfo = false;
            $parsingas = array('/(domain:.+)/i',
                               '/(domain name:.+)/i',
                               '/(query:.+)/i',
                               '/(status:.+)/i',
                               '/(state:.+)/i',
                               '/(registered:.+)/i',
                               '/(creation date:.+)/i',
                               '/(created:.+)/i',
                               '/(created on:.+)/i',
                               '/(updated date:.+)/i',
                               '/(modified:.+)/i',
                               '/(last updated on:.+)/i',
                               '/(expiration date:.+)/i',
                               '/(expires:.+)/i',
                               '/(expire:.+)/i',
                               '/(paid-till:.+)/i',
                               '/(free-date:.+)/i',
                               '/(registrar:.+)/i',
                               '/(registrar name:.+)/i');
            foreach ($parsingas as $patternas) {
                if (preg_match($patternas, $atsakymas, $rezultatai)) {
                    zinute($gavejas, $rezultatai[1]);
                    $turiminfo = true;
                }
            }
            if (!$turiminfo) {
                zinute($gavejas, 'Nėra informacijos');
            }
        }
        else {
            zinute($gavejas, 'Nežinomas TLD');
        }
    }
    else {
        zinute($gavejas, 'Nežinomas TLD');
    }
}

function ipinfo($ip, $gavejas) {
    $socketas = @fsockopen('whois.lacnic.net', 43, $errno, $errstr, 3);
    if ($socketas) {
        fwrite($socketas, gethostbyname($ip)."\r\n");
        $atsakymas = '';
        while (!feof($socketas)) {
            $atsakymas .= fread($socketas, 4096);
        }
        fclose($socketas);
        
        // klaidos
        if (preg_match('/^% (Invalid IP or CIDR block.+)$/m', $atsakymas, $rezultatai)) {
            zinute($gavejas, 'Klaida. '.$rezultatai[1]);
            return null;
        }
        if (preg_match('/^% (No match for.+)$/m', $atsakymas, $rezultatai)) {
            zinute($gavejas, 'Klaida. '.$rezultatai[1]);
            return null;
        }
        // end of klaidos
        
        $parsingas = array('/(inetnum:.+)/i',
                           '/(netrange:.+)/i',
                           '/(netname:.+)/i',
                           '/(orgname:.+)/i',
                           '/(descr:.+)/i',
                           '/(country:.+)/i',
                           '/(stateprov:.+)/i',
                           '/(city:.+)/i');
        foreach ($parsingas as $patternas) {
            if (preg_match($patternas, $atsakymas, $rezultatai)) {
                zinute($gavejas, $rezultatai[1]);
            }
        }
    }
    else {
        zinute($gavejas, 'IP whois serveris nepasiekiamas');
    }
}

function epizodas($serialas, $gavejas) {
    $socketas = @fsockopen('services.tvrage.com', 80, $errno, $errstr, 5);
    if ($socketas) {
        fwrite($socketas, "GET /tools/quickinfo.php?show=".urlencode($serialas)." HTTP/1.1\r\nHost: services.tvrage.com\r\nConnection: Close\r\n\r\n");
        $atsakymas = '';
        while (!feof($socketas)) {
            $atsakymas .= fread($socketas, 4096);
        }
        fclose($socketas);
        if (preg_match('/^Show Name@(.+)$/m', $atsakymas, $rezultatai)) {
            $serialas = $rezultatai[1];
            if (preg_match('/^Ended@(.+)$/m', $atsakymas, $rezultatai)) {
                zinute($gavejas, $serialas.' neberodomas');
            }
            else if (preg_match('/^Next Episode@(\d{2})x(\d{2})\^(.+)\^.+$\n^RFC3339@(.+)$.+^Network@(.+)$/msU', $atsakymas, $rezultatai)) {
                zinute($gavejas, $serialas);
                zinute($gavejas, 'S'.$rezultatai[1].'E'.$rezultatai[2].': '.$rezultatai[3]);
                zinute($gavejas, $rezultatai[5].': '.date('Y-m-d H:i', strtotime($rezultatai[4])));
            }
            else {
                zinute($gavejas, 'Sekančio '.$serialas.' epizodo data nepaskelbta');
            }
        }
        else {
            zinute($gavejas, 'Nėra informacijos');
        }
    }
    else {
        zinute($gavejas, 'TV serialų informacijos serveris nepasiekiamas');
    }
}

?>
