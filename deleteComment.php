<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        if (isset($_GET["cId"]) && is_numeric($_GET["cId"])) {
            $cId = intval($_GET["cId"]);
            $sessionId = intval($_SESSION["userfrontid"]);

            $stmt = $con->prepare("DELETE FROM comments WHERE commentId = :commentId AND userId = :sessionId");
            $stmt->bindParam("commentId", $cId);
            $stmt->bindParam("sessionId", $sessionId);
            $stmt->execute();

            if ($stmt) {
                if ($stmt->rowCount() > 0) {
                    redirect("<section class='container'><section class='success'>The comment is deleted successfully</section></section>", "profile.php", 3);
                } else {
                    redirect("<section class='container'><section class='alert'>There is no match</section></section>", "profile.php", 3);
                }
            }
        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }
        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }