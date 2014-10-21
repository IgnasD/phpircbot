<?php

set_time_limit(0);
date_default_timezone_set('Europe/Vilnius');

define('LOGDIR', 'logs');
define('CONFFILE', 'conf.php');

function logas($tekstas) {
    global $loghandle, $date;
    if ($date != date('Y-m-d')) {
        if ($loghandle) {
            fclose($loghandle);
        }
        $loghandle = @fopen(LOGDIR.'/'.date('Y-m-d').'.txt', 'a') or exit("Loginimas negalimas, veikla sustabdyta\n");
        $date = date('Y-m-d');
    }
    if ($tekstas != '') {
        fwrite($loghandle, '['.date('Y-m-d H:i:s O\G\M\T').'] '.$tekstas."\r\n");
    }
}

function pradeti() {
    logas('###############');
    logas('# PHP IRC BOT #');
    logas('###############');
    logas('$ Veikla pradėta');
    if (!is_readable(CONFFILE)) {
        logas('$ Nustatymų failas nerastas arba neparuoštas');
        stabdyti();
    }
}

function stabdyti() {
    global $loghandle;
    logas('$ Veikla nutraukta');
    fclose($loghandle);
    exit;
}

function nustatymai($nustatymas, $multi = null) {
    $nustatymai = file_get_contents(CONFFILE);
    if (!$multi) {
        $patternas = '/^'.$nustatymas.'=(.+)$/m';
    }
    else {
        $patternas = '/\['.$nustatymas."\]\n(.+)\n\[\/".$nustatymas.'\]/s';
    }
    if (preg_match($patternas, $nustatymai, $rezultatai)) {
        return $rezultatai[1];
    }
}

function prisijungti() {
    global $jungtis;
    
    $adresas = nustatymai('adresas');
    $portas = nustatymai('portas');
    
    logas('$ Jungiamasi prie serverio ('.$adresas.':'.$portas.')');
    $jungtis = @fsockopen($adresas, $portas, $errno, $errstr, 10);
    if ($jungtis) {
        logas('$ Prisijungta prie serverio ('.$adresas.':'.$portas.')');
        prisiregistravimas();
        skaitymas();
    }
    else {
        logas('$ Negalima prisijungti prie serverio ('.$adresas.':'.$portas.'). Sugeneruotas klaidos tekstas: '.$errstr);
        stabdyti();
    }
}

function atsijungti() {
    global $jungtis;
    siusti('QUIT');
    fclose($jungtis);
    logas('$ Atsijungta nuo serverio');
}

function siusti($siusti) {
    global $jungtis;
    fwrite($jungtis, $siusti."\r\n");
    logas('$ Nusiųsta: '.$siusti);
}

function prisiregistravimas() {
    siusti('NICK '.nustatymai('nickas'));
    siusti('USER '.nustatymai('identas').' localhost '.str_replace(array('ssl://', '[', ']'), '', nustatymai('adresas')).' :'.nustatymai('vardas'));
}

function identifikacija() {
    global $jungtis;
    $passwordas = nustatymai('passwordas');
    if (!empty($passwordas)) {
        fwrite($jungtis, 'IDENTIFY '.$passwordas."\r\n");
        logas('$ Nusiųsta: IDENTIFY <slaptažodis>');
    }
}

function komandos() {
    $komandos = explode("\n", nustatymai('komandos', true));
    foreach ($komandos as $komanda) {
        siusti($komanda);
    }
}

function teise($hostas, $lygis) {
    $teises = explode("\n", nustatymai('teises', true));
    foreach ($teises as $teise) {
        $dalys = explode(' ', $teise);
        if (preg_match($dalys[0], $hostas) && $dalys[1] >= $lygis) {
            return true;
        }
    }
    return false;
}

function zinute($gavejas, $zinute) {
    siusti('PRIVMSG '.$gavejas.' :'.$zinute);
}

function skaitymas() {
    global $jungtis;
    while (!feof($jungtis)) {
        $gauta = fgets($jungtis);
        $eilute = str_replace(array("\r", "\n"), '', $gauta);
        logas($eilute);
        apdoroti($eilute);
    }
    logas('$ Prarastas ryšys su serveriu');
    prisijungti();
}

function apdoroti($eilute) {
    global $priedai;
    
    $dal = explode(' ', $eilute);
    
    if ($dal[0] == 'PING') {
        siusti(substr_replace($eilute, 'PONG', 0, 4));
    }
    if ($dal[1] == '001') {
        identifikacija();
        komandos();
    }
    if ($dal[1] == 'PRIVMSG' && substr($dal[3], 1, 1) == '!') {
        $hostas = substr($dal[0], 1);
        
        $kom = substr($dal[3], 2);
        
        $params = explode(' ', $eilute, 5)[4];

        if ($kom == 'quit' || $kom == 'reconnect') {
            if (!$dal[4] && teise($hostas, 10)) {
                atsijungti();
                if ($kom == 'quit') stabdyti();
                else prisijungti();
            }
        }
        elseif ($kom == 'raw') {
            if ($dal[4] && teise($hostas, 10)) {
                siusti($params);
            }
        }
        elseif (isset($priedai[$kom])) {
            $priedas = $priedai[$kom];
            $pkiekis = count($dal)-4;
            if ((($priedas['param'] == 'min' && $priedas['kiek'] <= $pkiekis) ||
                 ($priedas['param'] == 'equ' && $priedas['kiek'] == $pkiekis)) &&
                teise($hostas, $priedas['teise'])) {
            
                if (preg_match('/^#/', $dal[2])) {
                    $gavejas = $dal[2];
                }
                elseif (preg_match('/^:(.+)!/', $dal[0], $rez)) {
                    $gavejas = $rez[1];
                }
                call_user_func($priedas['fja'], $params, $gavejas);
            }
        }
    }
}

include_once('addons.php');

pradeti();
prisijungti();

?>
