<?php
    session_start();

    if (isset($_SESSION["userfront"])) {

        if (isset($_GET["itId"]) && is_numeric($_GET["itId"])) {
            $sessionId = intval($_SESSION["userfrontid"]);
            $itemId = intval($_GET["itId"]);

            $titleKey = "login";
            include "init.inc.php";

            if (isActivatedUser($sessionId)) {
                $isexist = $con->prepare("SELECT userId FROM cart WHERE status = 0 AND userId = :userId AND itemId = :itemId");
                $isexist->bindParam(":userId", $sessionId);
                $isexist->bindParam(":itemId", $itemId);
                $isexist->execute();

                if ($isexist->rowCount() > 0) {

                    if (isset($_SERVER["HTTP_REFERER"])) {
                        $target = $_SERVER["HTTP_REFERER"];
                    } else {
                        $target = "index.php";
                    }
                    redirect("<section class='container'><section class='worning'>This item is already added</section></section>", $target, 3);
                    
                } else {

                    $stmt = $con->prepare("INSERT INTO cart (userId, itemId) VALUES (:userId, :itemId)");
                    $stmt->bindParam(":userId", $sessionId);
                    $stmt->bindParam(":itemId", $itemId);

                    $stmt->execute();

                    if ($stmt) {
                        
                        if (isset($_SERVER["HTTP_REFERER"])) {
                            $target = $_SERVER["HTTP_REFERER"];
                        } else {
                            $target = "index.php";
                        }

                        redirect("<section class='container'><section class='success'>The item add to cart successfully</section></section>", $target, 3);
                    }
                }
            } else {
                redirect("<section class='container'><section class='worning'>You are not activated yet</section></section>", "index.php", 3);
            }

        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";

    } else {
        header("location: sign.php?block=login");
        exit();
    }

