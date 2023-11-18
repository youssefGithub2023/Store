<?php

    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {
            $itemId = intval($_GET["itemId"]);
            $userId = intval($_SESSION["userfrontid"]);

            $stmt = $con->prepare("DELETE FROM items WHERE itemId = :itemId AND userId = :userId");
            $stmt->bindParam("itemId", $itemId);
            $stmt->bindParam("userId", $userId);
            $stmt->execute();

            if ($stmt->rowCount()) {
                redirect("<section class='container'><section class='success'>item delted successfully</section></section>", "profile.php", 3);
            } else {
                redirect("<section class='container'><section class='worning'>there is no match</section></section>", "profile.php", 3);
            }

        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }