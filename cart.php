<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        if (isActivatedUser($userId)) {
            $getUserItemsCart = $con->prepare("SELECT cart.itemId, cart.quantity, itemName, itemPrice, itemImg, itemStatus, items.discountId, percent,  ROUND(if (ISNULL(items.discountId), itemPrice, itemPrice - (itemPrice * percent/100)), 2) * quantity as finalPrice FROM cart JOIN items ON cart.itemId = items.itemId LEFT JOIN discounts ON items.discountId = discounts.discountId  WHERE cart.userId = :userId AND status = 0 ORDER BY cart.addedDate DESC");
            $getUserItemsCart->bindParam(":userId", $sessionId);
            $getUserItemsCart->execute();
            $itemsCount = $getUserItemsCart->rowCount();

            echo '
            <section class="shopping-cart">
                <section class="container">
            ';
            
            if ($itemsCount > 0) {
            ?>
                    <article class="cart">
                        <header>
                            <h2><i class="fa-solid fa-cart-shopping"></i> Shopping cart</h2>
                            <span><?php echo $itemsCount ?> items</span>
                        </header>
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>prodact details</th>
                                    <th>price</th>
                                    <th>discount</th>
                                    <th>quantity</th>
                                    <th>final price</th>
                                    <th>delete</th>
                                </tr>
                            </thead>
                            <tbody>

                            <?php

                                $totalPrice = 0;

                                while ($cartItem = $getUserItemsCart->fetch()) {

                                    $totalPrice += $cartItem["finalPrice"];

                                    if (is_null($cartItem["discountId"])) {
                                        $percent = "none";
                                    } else {
                                        $percent = $cartItem["percent"] . "%";
                                    }

                                    echo '
                                    <tr>
                                        <td>
                                            <section class="item-info">
                                                <figure>
                                                    <img src="' . itemImgs . $cartItem["itemImg"] . '">
                                                </figure>
                                                <section class="item-name">
                                                    <a href="itemDetails.php?itemId=' . $cartItem["itemId"] . '">' . $cartItem["itemName"] . '</a>
                                                    <span>' . $cartItem["itemStatus"] . '</span>
                                                </section>
                                            </section>
                                        </td>
                                        <td>' . $theCurrency . $cartItem["itemPrice"] . '</td>
                                        <td>' . $percent . '</td>
                                        <td>
                                            <form action="cartUpdateQuantity.php" method="post">
                                                <input type="hidden" name="itId" value="' . $cartItem["itemId"] . '">
                                                <input type="number" name="quantity" value="' . $cartItem["quantity"] . '"><button type="submit" name="saveQuantity"><i class="fa-solid fa-floppy-disk"></i> save</button>
                                            </form>
                                        </td>
                                        <td>' . $theCurrency . $cartItem["finalPrice"] . '</td>
                                        <td>
                                            <a href="deleteItemFromCart.php?itId=' . $cartItem["itemId"] .'" class="item-cart-del"><i class="fa-solid fafa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                    ';
                                }

                            ?>

                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">total</th>
                                    <th><?php echo $theCurrency . $totalPrice?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <span class="btn" id="checkout">checkout</span>
                    </article>
            <?php

            } else {
                echo "<section class='worning'>There is no items the cart</section>";
            }

            echo '
                </section>
                <section class="overlay" id="cartOverlay">
                    <section class="order-details">
                    <h2>order details</h2>
                    <form method="post" action="buy.php">
                        <section class="cont-inputs">
                            <section class="left">
                                <label class="form-label" for="firstName">first name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="firstName" id="firstName">
                                </section>

                                <label class="form-label" for="lastName">last name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="lastName" id="lastName">
                                </section>

                                <label class="form-label" for="email">Email</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa fa-envelope"></i></section><input type="email" name="email" id="email">
                                </section>

                                <label class="form-label" for="phone">phone</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa fa-envelope"></i></section><input type="text" name="phone" id="phone">
                                </section>
                            </section>

                            <section class="right">
                                <label class="form-label" for="zipCode">zip code</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="zipCode" id="zipCode">
                                </section>

                                <label class="form-label" for="city">city</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="city" id="city">
                                </section>

                                <label class="form-label" for="street">street</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="street" id="street">
                                </section>

                                <label class="form-label" for="home">home</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="home" id="home">
                                </section>

                            </section>
                        </section>
                        <section class="buttons">
                            <button type="submit" name="buy" class="btn">buy now</button>
                            <button type="button" class="btn" id="overlayCancel">cancel</button>
                        </section>
                    </form>
                    </section>
                </section>
            </section>
            ';

        } else {
            redirect("<section class='container'><section class='worning'>You are not activated yet</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }