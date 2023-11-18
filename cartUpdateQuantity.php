<?php

    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_POST["saveQuantity"])) {

            $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

            $quantity = filter_var($_POST["quantity"], FILTER_SANITIZE_NUMBER_INT);
            $itemId = filter_var($_POST["itId"], FILTER_SANITIZE_NUMBER_INT);

            $errors = array();

            if ($quantity <= 0) {
                array_push($errors, "<section class='alert'>Quantity cant be small than 1</section>");
            }

            if (empty($errors)) {

                // Check if the quantity is exist
                $getItemQuantity = getAllRecords("itemQuantity", "items", "itemId = $itemId", "itemId");
                $itemQuantity = $getItemQuantity->fetchColumn();
                $check = true;
                if ($quantity > $itemQuantity) {
                    $quantity = $itemQuantity;
                    $check = false;
                }

                $stmt = $con->prepare("UPDATE cart SET quantity = :quantity WHERE itemId = :itemId AND userId = :userId");
                $stmt->bindParam(":quantity", $quantity);
                $stmt->bindParam(":itemId", $itemId);
                $stmt->bindParam(":userId", $sessionId);
                $stmt->execute();

                if ($stmt) {
                    if ($check) {
                        header("location: cart.php");
                    } else {
                        echo "<section class='container'><section class='worning'>The available Quantity of this item is: " . $itemQuantity . "</section></section>";
                        header("refresh: 3; URL = cart.php");
                    }
                }

            } else {
                foreach($errors AS $error) {
                    echo "<section class='container'>" . $error . "</section>";
                }

                header("refresh: 3; URL=cart.php");
            }

        } else {
            header("location: cart.php");
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }