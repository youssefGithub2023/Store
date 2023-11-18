<?php

    function getAllRecords($fields, $table, $where, $orderBy) {
        global $con;

        $getAll = $con->prepare("SELECT $fields FROM $table WHERE $where ORDER BY $orderBy");
        $getAll->execute();

        return $getAll;
    }

    function printTitle() {
        global $titleKey;
        return isset($titleKey) ? lang($titleKey) : "Default";
    }

    function redirect($msg, $url = null, $timeout = 5) {
        echo $msg;

        if ($url == null) {
            header("refresh: $timeout; URL=index.php");
            exit();
        } elseif ($url == "back") {
            $link = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "index.php";
            header("refresh: $timeout; URL=$link");
            exit();
        } else {
            header("refresh: $timeout; URL=$url");
            exit();
        }
    }

    function isAlreadyInUse($table, $col, $val) {
        global $con;
        $funcStmt = $con->prepare("SELECT $col FROM $table WHERE $col = :val");
        $funcStmt->bindParam("val", $val);
        $funcStmt->execute();

        return $funcStmt->rowCount() > 0;
    }

    function totalRecords($table, $col, $val = null) {
        global $con;

        if ($table == "users") {
            if ($val == null) {
                $condition = "WHERE type = 0";
            } else {
                $condition = "WHERE $col = $val AND type = 0";
            }
        } else {
            if ($val == null) {
                $condition = "";
            } else {
                $condition = "WHERE $col = $val";
            }
        }

        $trStmt = $con->prepare("SELECT COUNT($col) FROM $table $condition");
        $trStmt->execute();

        return $trStmt->fetchColumn();
    }

    function getLatestRecords($cols, $table, $orderBy, $limit = 5) {
        global $con;
        if ($table == "users") {
            $cond = "WHERE type = 0";
        } else {
            $cond = "";
        }
        $lrStmt = $con->prepare("SELECT $cols FROM $table $cond ORDER BY $orderBy DESC LIMIT $limit");
        $lrStmt->execute();

        return $lrStmt;
    }

    function addGetBy($url, $get) {
        return strpos($url, "?") ? $url . "&" . $get : $url . "?" . $get;
    }

    function slice($str, $min = 0, $max = "") {
        if ($max == "") {
            $max = strlen($str);
        }
        if ($min <= $max) {
            $sl = "";
            for ($i = $min; $i < $max; $i += 1) {
                $sl .= $str[$i];
            }
            return $sl;
        } else {
            return NULL;
        }
    }

    function orderByHref($get) {
        if (isset($_GET['orderBy'])) {

            $orderByPos = strpos($_SERVER["REQUEST_URI"], "orderBy");

            $urlFirstPart = slice($_SERVER["REQUEST_URI"], 0, $orderByPos - 1);

            if (strpos($_SERVER["REQUEST_URI"], "&", $orderByPos)) {
                $urlLastPart = slice($_SERVER["REQUEST_URI"], strpos($_SERVER["REQUEST_URI"], "&", $orderByPos));
            } else {
                $urlLastPart = "";
            }
            
            echo addGetBy($urlFirstPart, $get) .  $urlLastPart;

        } else {
            echo addGetBy($_SERVER["REQUEST_URI"], $get);
        }
    }

    function linkWithoutEdited() {
        if (strpos($_SERVER["HTTP_REFERER"], "edited")) {
            
            // Return the link without edited
            $pos = strpos($_SERVER["HTTP_REFERER"], "edited");
            return slice($_SERVER["HTTP_REFERER"], 0, $pos - 1);

        } else {
            return $_SERVER["HTTP_REFERER"];
        }
    }

    function random_name($len) {
        $random_name = "";
        for ($i = 0; $i < $len; $i += 1) {
            $random_name .= rand(1, 9);
        }
        return $random_name;
    }

    function isAllowADS($catId) {
        global $con;
        $AllowADSStmt = $con->prepare("SELECT allowADS FROM categories WHERE catId = :catId AND allowADS = 1");
        $AllowADSStmt->bindParam("catId", $catId);
        $AllowADSStmt->execute();

        return $AllowADSStmt->rowCount() > 0;
    }

    function generateNotification($notNotification, $notNotClass, $notUserId) {
        global $con;

        $setNotification = $con->prepare("INSERT INTO notifications (notification, notClass, userId) VALUES (:notification, :notClass, :userId)");
        $setNotification->bindParam("notification", $notNotification);
        $setNotification->bindParam("notClass", $notNotClass);
        $setNotification->bindParam("userId", $notUserId);
        $setNotification->execute();
    }

    function myDate($date) {
        return date("d/m/Y H:i", strtotime($date));
    }

    // Profile image
    function proImgPath($path, $fn) {
        if (is_null($path)) {
            $fl = substr($fn, 0, 1);
            if (ctype_alpha($fl)) {
                return "letters/letter-" . strtolower($fl) . ".gif";
            } else {
                return "letters/default.gif";
            }
        } else {
            return $path;
        }
    }