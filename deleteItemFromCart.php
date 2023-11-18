<?php
    session_start();
    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        if (isset($_GET["itId"]) && is_numeric($_GET["itId"])) {
            $itemId = intval($_GET["itId"]);

            $stmt = $con->prepare("DELETE FROM cart WHERE status = 0 AND itemId = :itemId AND userId = :userId");
            $stmt->bindParam(":itemId", $itemId);
            $stmt->bindParam(":userId", $sessionId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                redirect("<section class='container'><section class='success'>One item deleted from cart successfully</section></section>", "cart.php", 3);
            } else {
                redirect("<section class='container'><section class='worning'>there is no match</section></section>", "cart.php", 3);
            }

        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "cart.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }