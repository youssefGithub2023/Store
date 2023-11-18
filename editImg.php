<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        if (isset($_POST["editType"]) && ($_POST["editType"] == "itemImg" || $_POST["editType"] == "profileImg")) {

            if ($_POST["editType"] == "profileImg") {

                // Start edit and delete profile image
                if (isset($_POST["edit"])) {

                    $newProfileImg = $_FILES["newProfileImg"];

                    if ($newProfileImg["error"] == 0) {
                        
                        $npi_tmp_name = $newProfileImg["tmp_name"];
                        $npi_type = $newProfileImg["type"];
                        $npi_size = $newProfileImg["size"];
                        
                        if (preg_match("/image\/(png|gif|jpe?g)/i", $npi_type)) {
                            if ($npi_size < 4194304) {

                                $npi_ex = explode("/", $npi_type);
                                $npi_ext = strtolower(end($npi_ex));
                                $profileImgName = random_name(40) . "." . $npi_ext;
                                move_uploaded_file($npi_tmp_name, profileImgs . $profileImgName);

                                // Delete old profile image
                                $selStmt = $con->prepare("SELECT profileImgPath FROM users WHERE userId = :sessionId LIMIT 1");
                                $selStmt->bindParam(":sessionId", $sessionId);
                                $selStmt->execute();
                                if ($selStmt) {
                                    if ($selStmt->rowCount() > 0) {
                                        $oldProfileImg = $selStmt->fetchColumn();
                                        if (! is_null($oldProfileImg)) {
                                            unlink(profileImgs . $oldProfileImg);
                                        }
                                    }
                                }

                                // Set new profile image name in the db
                                $upStmt = $con->prepare("UPDATE users SET profileImgPath = :profileImgName WHERE userId = :sessionId");
                                $upStmt->bindParam(":profileImgName", $profileImgName);
                                $upStmt->bindParam(":sessionId", $sessionId);
                                $upStmt->execute();
                                if ($upStmt) {
                                    header("Location: profile.php");
                                }
                            } else {
                                echo "<section class='container'><section class='alert'>Image size is too large maximum is (4MB)</section></section>";
                            }
                        } else {
                            echo "<section class='container'><section class='alert'>Only accept images (png, jpg, jpeg or gif)</section></section>";
                        }

                    } else {
                        echo "<section class='container'><section class='alert'>The profile image not edited because the input is empty or an error happens</section></section>";
                    }

                } elseif (isset($_POST["delete"])) {

                    $selStmt = $con->prepare("SELECT profileImgPath FROM users WHERE userId = :sessionId LIMIT 1");
                    $selStmt->bindParam(":sessionId", $sessionId);
                    $selStmt->execute();
                    if ($selStmt) {
                        if ($selStmt->rowCount() > 0) {
                            $oldProfileImg = $selStmt->fetchColumn();

                            if (! is_null($oldProfileImg)) {
                                $upStmt = $con->prepare("UPDATE users SET profileImgPath = NULL WHERE userId = :sessionId");
                                $upStmt->bindParam(":sessionId", $sessionId);
                                $upStmt->execute();
                                if ($upStmt) {
                                    // Delete old profile image
                                    unlink(profileImgs . $oldProfileImg);
                                }
                            }
                        }
                    }

                    header("Location: profile.php");

                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
                }
                // End edit and delete profile image

            } else {
                
                // Start edit item imgs
                $col = filter_var($_POST["col"], 513);

                if (preg_match("/^(itemImg)|(si[1-4])$/", $col)) {
                    $itemId = filter_var($_POST["itId"], FILTER_SANITIZE_NUMBER_INT);

                    // Get the img name and check if this item is for the login user
                    $imgName = $con->prepare("SELECT $col FROM items WHERE itemId = :itemId AND userId = :sessionId LIMIT 1");
                    $imgName->bindParam("itemId", $itemId);
                    $imgName->bindParam(":sessionId", $sessionId);
                    $imgName->execute();

                    if ($imgName->rowCount() == 1) {
                        $oldImgName = $imgName->fetchColumn();

                        if (isset($_POST["edit"])) {

                            if ($col == "itemImg") {
                                $path = itemImgs;
                            } else {
                                $path = itemSecondaryImgs;
                            }

                            $newItemImg = $_FILES["newImg"];

                            if ($newItemImg["error"] == 0) {
                                
                                $nii_tmp_name   = $newItemImg["tmp_name"];
                                $nii_type       = $newItemImg["type"];
                                $nii_size       = $newItemImg["size"];
                                
                                if (preg_match("/image\/(png|gif|jpe?g)/i", $nii_type)) {
                                    if ($nii_size < 4194304) {

                                        $nii_ex = explode("/", $nii_type);
                                        $nii_ext = strtolower(end($nii_ex));
                                        $itemImgName = random_name(40) . "." . $nii_ext;
                                        move_uploaded_file($nii_tmp_name, $path . $itemImgName);

                                        $upStmt = $con->prepare("UPDATE items SET $col = :itemImgName WHERE itemId = :itemId");
                                        $upStmt->bindParam(":itemImgName", $itemImgName);
                                        $upStmt->bindParam(":itemId", $itemId);
                                        $upStmt->execute();
                                        
                                        if (! is_null($oldImgName)) {
                                            // Delete old image
                                            unlink($path . $oldImgName);
                                        }

                                        goTohttpreferer();

                                    } else {
                                        echo "<section class='container'><section class='alert'>Image size is too large maximum is (4MB)</section></section>";
                                    }
                                } else {
                                    echo "<section class='container'><section class='alert'>Only accept images (png, jpg, jpeg or gif)</section></section>";
                                }

                            } else {
                                echo "<section class='container'><section class='alert'>The item image not edited because the input is empty or an error happens</section></section>";
                            }

                        } elseif (isset($_POST["delete"])) {

                            if ($col != "itemImg") {
                                if (! is_null($oldImgName)) {

                                    $upStmt = $con->prepare("UPDATE items SET $col = NULL WHERE itemId = :itemId");
                                    $upStmt->bindParam(":itemId", $itemId);
                                    $upStmt->execute();
                                    if ($upStmt) {
                                        // Delete old secondry image
                                        unlink(itemSecondaryImgs . $oldImgName);
                                    }
            
                                }
                            }

                            goTohttpreferer();
                            
                        }
                    }
                }
                // End edit item imgs
                
            }

        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }