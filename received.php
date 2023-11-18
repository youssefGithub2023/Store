<?php

    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_GET["cId"]) && is_numeric($_GET["cId"]) && isset($_GET["itId"]) && is_numeric($_GET["itId"]) && isset($_GET["comment"]) && isset($_GET["rat"])) {

            $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

            if (isActivatedUser($sessionId)) {

                $cartId = intval(filter_var($_GET["cId"], FILTER_SANITIZE_NUMBER_INT));
                $itemId = intval(filter_var($_GET["itId"], FILTER_SANITIZE_NUMBER_INT));
                $comment = filter_var($_GET["comment"], 513);
                $rat = filter_var($_GET["rat"], 513);

                // Update order status
                $upStatus = $con->prepare("UPDATE cart SET status = 3 WHERE cartId = :cartId AND userId = :userId AND status != 0 AND status != 3");
                $upStatus->bindParam(":cartId", $cartId);
                $upStatus->bindParam(":userId", $sessionId);
                $upStatus->execute();

                if ($upStatus->rowCount() > 0) {

                    // Commenting block
                    if (! empty($comment)) {
                        // Check if the item is allow commenting
                        $allowC = $con->prepare("SELECT itemId FROM items JOIN categories USING (catId) WHERE itemId = :itemId AND allowCommenting = 1");
                        $allowC->bindParam(":itemId", $itemId);
                        $allowC->execute();
                        if ($allowC->rowCount() > 0) {

                            // Check if the current user is already add comment for this item
                            $isAlreadyAddComment = $con->prepare("SELECT commentId FROM comments WHERE userId = :userId AND itemId = :itemId");
                            $isAlreadyAddComment->bindParam(":userId", $sessionId);
                            $isAlreadyAddComment->bindParam(":itemId", $itemId);
                            $isAlreadyAddComment->execute();

                            if ($isAlreadyAddComment->rowCount() > 0) {
                                // Update comment
                                $upComment = $con->prepare("UPDATE comments SET comment = :comment, commentStatus = 0 WHERE userId = :userId AND itemId = :itemId");
                                $upComment->bindParam(":comment", $comment);
                                $upComment->bindParam(":userId", $sessionId);
                                $upComment->bindParam(":itemId", $itemId);
                                $upComment->execute();
                            } else {
                                // Insert comment
                                $inComment = $con->prepare("INSERT INTO comments(comment, userId, itemId) VALUES (:comment, :userId, :itemId)");
                                $inComment->bindParam(":comment", $comment);
                                $inComment->bindParam(":userId", $sessionId);
                                $inComment->bindParam(":itemId", $itemId);
                                $inComment->execute();
                            }

                        }
                    }

                    // Rating block
                    if (! empty($rat)) {
                        if ($rat == "l") {
                            $rating = 1;
                        } else {
                            $rating = 0;
                        }

                        // Check is already rated
                        $isAlreadyRated = $con->prepare("SELECT rating FROM rating WHERE userId = :userId AND itemId = :itemId");
                        $isAlreadyRated->bindParam(":userId", $sessionId);
                        $isAlreadyRated->bindParam(":itemId", $itemId);
                        $isAlreadyRated->execute();

                        if ($isAlreadyRated->rowCount() > 0) {

                            if ($rating != $isAlreadyRated->fetchColumn()) {
                                $upRating = $con->prepare("UPDATE rating SET rating = :rating WHERE userId = :userId AND itemId = :itemId");
                                $upRating->bindParam(":rating", $rating);
                                $upRating->bindParam(":userId", $sessionId);
                                $upRating->bindParam(":itemId", $itemId);
                                $upRating->execute();
                            }

                        } else {
                            $inRating = $con->prepare("INSERT INTO rating VALUES (:userId, :itemId, :rating)");
                            $inRating->bindParam(":userId", $sessionId);
                            $inRating->bindParam(":itemId", $itemId);
                            $inRating->bindParam(":rating", $rating);
                            $inRating->execute();
                        }
                        
                    }

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