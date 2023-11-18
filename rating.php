<?php

    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        
        if ((isset($_GET["rat"]) && ($_GET["rat"] == "l" || $_GET["rat"] == "d")) && (isset($_GET["itId"]) && is_numeric($_GET["itId"]))) {
            include "init.inc.php";
            $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

            if (isActivatedUser($sessionId)) {
                $itemId = filter_var($_GET["itId"], FILTER_SANITIZE_NUMBER_INT);
                
                $isItThisItemAreMine = $con->prepare("SELECT itemId FROM items WHERE itemId = :itemId AND userId != :userId");
                $isItThisItemAreMine->bindParam(":itemId", $itemId);
                $isItThisItemAreMine->bindParam(":userId", $sessionId);
                $isItThisItemAreMine->execute();

                if ($isItThisItemAreMine->rowCount() > 0) {

                    if ($_GET["rat"] == "l") {
                        $rating = 1;
                    } else {
                        $rating = 0;
                    }

                    $isAlreadyRated = $con->prepare("SELECT rating FROM rating WHERE userId = :userId AND itemId = :itemId");
                    $isAlreadyRated->bindParam(":userId", $sessionId);
                    $isAlreadyRated->bindParam(":itemId", $itemId);
                    $isAlreadyRated->execute();

                    if ($isAlreadyRated->rowCount() == 0) {
                        
                        $stmt = $con->prepare("INSERT INTO rating VALUES (:userId, :itemId, :rating)");
                        $stmt->bindParam(":userId", $sessionId);
                        $stmt->bindParam(":itemId", $itemId);
                        $stmt->bindParam(":rating", $rating);
                        $stmt->execute();

                        if ($stmt) {
                            goTohttpreferer();
                        }

                    } else {

                        if ($rating == $isAlreadyRated->fetchColumn()) {
                            $deleteRating = $con->prepare("DELETE FROM rating WHERE userId = :userId AND itemId = :itemId");
                            $deleteRating->bindParam(":userId", $sessionId);
                            $deleteRating->bindParam(":itemId", $itemId);
                            $deleteRating->execute();
                            if ($deleteRating) {
                                goTohttpreferer();
                            }
                        } else {
                            $updateRating = $con->prepare("UPDATE rating SET rating = :rating WHERE userId = :userId AND itemId = :itemId");
                            $updateRating->bindParam(":rating", $rating);
                            $updateRating->bindParam(":userId", $sessionId);
                            $updateRating->bindParam(":itemId", $itemId);
                            $updateRating->execute();
                            if ($updateRating) {
                                goTohttpreferer();
                            }
                        }
                        
                    }

                } else {
                    goTohttpreferer();
                }
            } else {
                echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
            }
            include tpl . "footer.inc.php";

        } else {
            header("location: index.php");
        }


        

        
        
    } else {
        header("location: sign.php?block=login");
    }