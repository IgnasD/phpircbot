<?php

function top($file = null) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
<title>Logai</title>
<style type=\"text/css\">
body {
    font-family: Monospace;
    font-size: 12px;
    color: #000000;
    background-color: #FFFFFF;
    text-align: ".($file ? "left" : "center").";
}
a {
    text-decoration: none;
    color: #000000;
}
a:hover {
    text-decoration: underline;
    color: #000000;
}
</style>
</head>
<body>\n";
}

function bottom() {
    echo "\n</body>
</html>";
}

$file = "logs/".$_GET["y"]."-".$_GET["m"]."-".$_GET["d"].".txt";
if (is_readable($file)) {
    top(true);
    echo str_replace("\r", "<br>", htmlspecialchars(trim(file_get_contents($file))));
}
else {
    top();
    $files = glob("logs/*-*-*.txt");
    foreach ($files as $id => $file) {
        if ($id > 0) {
            echo "<br>\n";
        }
        $date = str_replace(array("logs/", ".txt"), "", $file);
        $date2 = explode("-", $date);
        echo "<a href=\"?y=".$date2[0]."&amp;m=".$date2[1]."&amp;d=".$date2[2]."\">".$date."</a>";
    }
}
bottom();

?>
