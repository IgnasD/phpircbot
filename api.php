<?php

date_default_timezone_set('Europe/Vilnius');

// workaround
function zinute($gavejas, $zinute) {
    echo $zinute."\n";
}

// php boto fjos
require('addons.php');

//pritaikom
if (isset($_SERVER['PATH_INFO'])) {
    $duomenys = explode(' ', substr($_SERVER['PATH_INFO'],1),2);
    if (preg_match('/^[a-z]+$/', $duomenys[0])) {
        if (isset($priedai[$duomenys[0]])) {
            $priedas = $priedai[$duomenys[0]];
            $duomenys[1] = trim($duomenys[1]);
            if (!empty($duomenys[1])) {
                $pkiekis = count(explode(' ', $duomenys[1]));
            }
            else {
                $pkiekis = 0;
            }
            if ((($priedas['param'] == 'min' && $priedas['kiek'] <= $pkiekis) ||
                 ($priedas['param'] == 'equ' && $priedas['kiek'] == $pkiekis)) &&
                 ($priedas['teise'] <= 5)) //hardcode, 10 = admin, 5 = eilinis
            {
                call_user_func($priedas['fja'], $duomenys[1], null);
            }
            else {
                echo "Blogas parametrų skaičius\n";
            }
        }
    }
}

?>
