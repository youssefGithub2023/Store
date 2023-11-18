<?php
    include "connection.php";

    // Paths
    define("funcs", "includes/functions/");
    define("tpl", "includes/templates/");
    define("langs", "includes/langs/");
    define("imgs", "theme/imgs/");
    define("css", "theme/css/");
    define("js", "theme/js/");
    define("profileImgs", "../data/uploads/profileImgs/");
    define("itemImgs", "../data/uploads/itemImgs/main/"); // Items' main images folder
    define("itemSecondaryImgs", "../data/uploads/itemImgs/secondary/"); // Items' secondary images folder

    include langs . "en.php";
    include funcs . "functions.php";
    include tpl . "header.inc.php";

    if (! isset($noNavbar)) {
        include tpl . "navbar.inc.php";
    }

    $theCurrency = "$";
    
