<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_GET["cId"]) && is_numeric($_GET["cId"])) {

            $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

            if (isActivatedUser($sessionId)) {

                $cartId = intval(filter_var($_GET["cId"], FILTER_SANITIZE_NUMBER_INT));

                // Update order status
                $upStatus = $con->prepare("UPDATE cart JOIN items USING (itemId) SET status = 2 WHERE cartId = :cartId AND items.userId = :userId AND status = 1");
                $upStatus->bindParam(":cartId", $cartId);
                $upStatus->bindParam(":userId", $sessionId);
                $upStatus->execute();

                if ($upStatus->rowCount() > 0) {
                    echo "<section class='container'><section class='success'>success</section></section>";
                } else {
                    echo "<section class='container'><section class='worning'>There is no match</section></section>";
                }

            } else {
                echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
            }

        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }