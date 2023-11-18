<?php
    session_start();

    include "init.inc.php";

    //Import PHPMailer classes into the global namespace
    //These must be at the top of your script, not inside a function
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require libsInc . "sendMail/" . 'src/Exception.php';
    require libsInc . "sendMail/" . 'src/PHPMailer.php';
    require libsInc . "sendMail/" . 'src/SMTP.php';


    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";

        if (isset($_POST["buy"])) {
            // old place of the header inc

            $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);
    
            if (isActivatedUser($sessionId)) {

                // Get all items in the cart its quantities are not available
                $itemsQuantity = $con->prepare("SELECT cart.itemId, items.itemName, items.itemQuantity, cart.quantity FROM cart JOIN items USING(itemId) WHERE status = 0 AND cart.userId = :userId AND cart.quantity > items.itemQuantity");
                $itemsQuantity->bindParam(":userId", $sessionId);
                $itemsQuantity->execute();

                if ($itemsQuantity->rowCount() == 0) {
                    // ==== If all items' quantities is available

                    // Filter form data
                    $firstName      = filter_var($_POST["firstName"], 513);
                    $lastName       = filter_var($_POST["lastName"], 513);
                    $email          = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
                    $phone          = filter_var($_POST["phone"], FILTER_SANITIZE_NUMBER_INT);
                    $zipCode        = filter_var($_POST["zipCode"], FILTER_SANITIZE_NUMBER_INT);
                    $city           = filter_var($_POST["city"], 513);
                    $street         = filter_var($_POST["street"], 513);
                    $home           = filter_var($_POST["home"], 513);

                    $deliveryCode   = random_name(6);
                    $buyDate   = date("Y-m-d H:i:s");

                    // Validate form data
                    $errors = array();
                    
                    if (empty($firstName)) {
                        array_push($errors, "First name input cant be empty");
                    }
                    if (empty($lastName)) {
                        array_push($errors, "Last name input cant be empty");
                    }
                    if (empty($email)) {
                        array_push($errors, "Email input cant be empty");
                    } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        array_push($errors, "Invalid email");
                    }
                    if (empty($zipCode)) {
                        array_push($errors, "Zip code input cant be empty");
                    }
                    if (empty($city)) {
                        array_push($errors, "City input cant be empty");
                    }
                    if (empty($street)) {
                        array_push($errors, "Street input cant be empty");
                    }
                    if (empty($home)) {
                        array_push($errors, "Home input cant be empty");
                    }

                    // Send form data to db
                    if (empty($errors)) {

                        $insertOrderdetails = $con->prepare("INSERT INTO orderdetails (city, street, home, zipCode, firstName, lastName, email, phone, deliveryCode, buyDate) VALUES (:city, :street, :home, :zipCode, :firstName, :lastName, :email, :phone, :deliveryCode, :buyDate)");
                        $insertOrderdetails->bindParam(":city", $city);
                        $insertOrderdetails->bindParam(":street", $street);
                        $insertOrderdetails->bindParam(":home", $home);
                        $insertOrderdetails->bindParam(":zipCode", $zipCode);
                        $insertOrderdetails->bindParam(":firstName", $firstName);
                        $insertOrderdetails->bindParam(":lastName", $lastName);
                        $insertOrderdetails->bindParam(":email", $email);
                        $insertOrderdetails->bindParam(":phone", $phone);
                        $insertOrderdetails->bindParam(":deliveryCode", $deliveryCode);
                        $insertOrderdetails->bindParam(":buyDate", $buyDate);
                        $insertOrderdetails->execute();

                        if ($insertOrderdetails) {

                            $getOrderdetailsId = getAllRecords("odId", "orderdetails", "deliveryCode = $deliveryCode AND buyDate = '$buyDate'", "odId");
                            $orderdetailsId = $getOrderdetailsId->fetchColumn();

                            $updateCart = $con->prepare("UPDATE cart JOIN items ON cart.itemId = items.itemId LEFT JOIN discounts ON items.discountId = discounts.discountId SET cart.status = 1, cart.netPrice = ROUND(if (ISNULL(items.discountId), itemPrice, itemPrice - (itemPrice * percent/100)), 2) * quantity, cart.odId = $orderdetailsId, items.itemQuantity = items.itemQuantity - cart.quantity WHERE cart.status = 0 AND cart.userId = :userId");
                            $updateCart->bindParam(":userId", $sessionId);
                            $updateCart->execute();

                            if ($updateCart) {

                                $getPurchases = $con->prepare("SELECT quantity, netPrice, itemName, itemStatus, itemImg, firstName, email FROM cart JOIN items USING (itemId) JOIN users ON items.userId = users.userId WHERE odId = $orderdetailsId");
                                $getPurchases->execute();

                                //Create an instance; passing `true` enables exceptions
                                $mail = new PHPMailer(true);

                                try {
                                    $mail->isSMTP();                                            //Send using SMTP
                                    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
                                    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                                    $mail->Username   = 'elzeroyoussef2022@gmail.com';                     //SMTP username
                                    $mail->Password   = 'jjpigjestodrvwur';                               //SMTP password
                                    $mail->SMTPSecure = "ssl";                                  //Enable implicit TLS encryption
                                    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
                            
                                    $mail->setFrom('elzeroyoussef2022@gmail.com', 'Youssef Shop');
                                    $mail->isHTML(true);                                  //Set email format to HTML
                                    $mail->Subject = 'New order';

                                    while ($purch = $getPurchases->fetch()) {
                                        $mail->addAddress($purch["email"]);
                                        $mail->addEmbeddedImage(itemImgs . $purch["itemImg"], $purch["itemImg"]);
                                        $mail->Body = '
                                            <html>
                                                <head>
                                                    <style>
                                                        table {
                                                            border-collapse: collapse;
                                                            width: 100%;
                                                            margin-top: 12px
                                                        }
                                                        table td, th {
                                                            text-align: center;
                                                            padding: 8px 0
                                                        }
                                                        table tbody {
                                                            font-weight: bold
                                                        }
                                                        table tbody tr td {
                                                            border-top: 2px solid #ffff00
                                                        }
                                                        table tbody .item-info {
                                                            display: flex;
                                                            align-items: center;
                                                            padding: 8px 0
                                                        }
                                                        table tbody .item-info figure {
                                                            width: 40px;
                                                            height: 40px;
                                                            margin-right: 12px
                                                        }
                                                        table tbody .item-info figure img {
                                                            width: 100%;
                                                            height: 100%;
                                                            object-fit: cover;
                                                            border: 2px solid #000;
                                                            border-radius: 8px
                                                        }
                                                        table tbody .item-info .item-name span {
                                                            display: block
                                                        }
                                                    </style>
                                                </head>
                                                <body>
                                                    <p>Hello <b>' . $purch["firstName"] . '</b> you have a new order:</p>
                                                    <table class="cart-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Prodact details</th>
                                                                <th>Quantity</th>
                                                                <th>Net price</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                                
                                                            <tr>
                                                                <td>
                                                                    <section class="item-info">
                                                                        <figure>
                                                                            <img src="cid:' . $purch["itemImg"] . '">
                                                                        </figure>
                                                                        <section class="item-name">
                                                                            <span>' . $purch["itemName"] . '</span>
                                                                            <span>' . $purch["itemStatus"] . '</span>
                                                                        </section>
                                                                    </section>
                                                                </td>

                                                                <td>' . $purch["quantity"] . '</td>
                                                                <td>' . $purch["netPrice"] . '</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </body>
                                            </html>
                                        ';
                                        $mail->send();
                                        $mail->clearAddresses();
                                        $mail->clearAttachments();
                                    }

                                    echo "<section class='container'><section class='success'>Items purchased successfully</section></section>";
                                    
                                } catch (Exception $e) {
                                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                                }
                            }

                        }

                    } else {
                        echo "<section class='container'>";
                        foreach ($errors AS $error) {
                            echo "<section class='alert'>" . $error . "</section>";
                        }
                        echo "</section>";
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }

                } else {
                    // ==== If an item's or items' quantities are not available

                    echo "<section class='container'>";
                    while ($itemQuantity = $itemsQuantity->fetch()) {
                        
                        echo "<section class='worning'>The quantity you requested <span class='focus'>" . $itemQuantity["quantity"] . "</span> from the <span class='focus'>" . $itemQuantity["itemName"] . "</span> item no longer available, The available quantity now is: <span class='focus'>" . $itemQuantity["itemQuantity"] . "</span></section>";
                    }
                    echo "</section>";
                }

            } else {
                echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
            }

            // Old place of the footer
        } else {
            header("location: cart.php");
        }

    } else {
        header("location: sign.php?block=login");
    }

    include tpl . "footer.inc.php";