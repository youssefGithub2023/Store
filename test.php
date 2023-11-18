<?php

    session_start();
    if (isset($_SESSION["userfront"])) {
        require_once "admin/connection.php";
        $userId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);



            if ($_GET["block"] == "delete") {

                if (isset($_GET["notId"]) && is_numeric($_GET["notId"])) {
                    $notId = filter_var($_GET["notId"], 519);

                    $stmt = $con->prepare("DELETE FROM notifications WHERE notId = :notId AND userId = :sessionId");
                    $stmt->bindParam("notId", $notId);
                    $stmt->bindParam("sessionId", $userId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        echo "1";
                    } else {
                        echo "0";
                    }

                }

            } elseif ($_GET["block"] == "deleteAll") {

                $stmt = $con->prepare("DELETE FROM notifications WHERE userId = :sessionId");
                $stmt->bindParam("sessionId", $userId);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    header("location: notifications.php");
                } else {
                    redirect("<section class='container'><section class='worning'>there is no match</section></section>", "notifications.php", 3);
                }

            }
        
        
    }


    echo "hi";