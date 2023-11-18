<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $sessionId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        if (isActivatedUser($sessionId)) {

            // The filter block
            $co = "";
            $otw = "";
            $ro = "";
            $h1Text = "My orders";
            $nbText = "You don't have any orders";
            if (isset($_GET["only"])) {
                if (is_numeric($_GET["only"])) {
                    $only = intval($_GET["only"]);
                    if ($only == 1) {
                        $statusCond = "status = 1";
                        $co = "use";
                        $h1Text = "My confirmed orders";
                        $nbText = "You don't have any confirmed orders";
                    } elseif ($only == 2) {
                        $statusCond = "status = 2";
                        $otw = "use";
                        $h1Text = "My orders are on the way";
                        $nbText = "You don't have any orders on the way";
                    } elseif ($only == 3) {
                        $statusCond = "status = 3";
                        $ro = "use";
                        $h1Text = "My received orders";
                        $nbText = "You don't have any received orders";
                    } else {
                        $statusCond = "status != 0";
                    }
                } else {
                    $statusCond = "status != 0";
                }
            } else {
                $statusCond = "status != 0";
            }

            $stmt = $con->prepare("SELECT cart.cartId, cart.itemId, cart.userId AS buyer, quantity, status, netPrice, items.userId AS seller, itemName, itemImg, date(buyDate) AS date, deliveryCode, username  FROM cart JOIN items ON cart.itemId = items.itemId JOIN orderdetails ON cart.odId = orderdetails.odId JOIN users ON items.userId = users.userId WHERE cart.userId = :userId AND $statusCond ORDER BY buyDate");
            $stmt->bindParam(":userId", $sessionId);
            $stmt->execute();

            echo '
                <section class="orders">
                    <section class="container">
                    <header>
                        <h1>' . $h1Text . '</h1>
                        <section class="dropdown">
                            <span class="dropdown-btn" id="dropdown-btn"><i class="fa-solid fa-filter"></i> Filter</span>
                            <ul class="dropdown-items" id="dropdown-items">
                                <li><a class="' . $co . '" href="purchases.php?only=1"><i class="fa-solid fa-check"></i> Confirmed orders</a></li>
                                <li><a class="' . $otw . '" href="purchases.php?only=2"><i class="fa-solid fa-truck"></i> On the way orders</a></li>
                                <li><a class="' . $ro . '" href="purchases.php?only=3"><i class="fa-solid fa-people-carry-box"></i> Received orders</a></li>
                            </ul>
                        </section>
                    </header>
            ';

            if ($stmt->rowCount() > 0) {
                
                $date = null;

                while ($order = $stmt->fetch()) {

                    // Date
                    if ($date != $order["date"]) {
                        echo '<section class="date">' . date("d/m/Y" ,strtotime($order["date"])) . '</section>';
                        $date = $order["date"];
                    }

                    // Status
                    $receivedBtn = '<span class="btn received" data-cid="' . $order["cartId"] . '" data-itid="' . $order["itemId"] . '"><i class="fa-solid fa-people-carry-box"></i> I received it</span>';

                    if ($order["status"] == 1) {
                        $co = "stat-succ";
                        $oo = "";
                        $ro = "";
                    } elseif ($order["status"] == 2) {
                        $co = "stat-succ";
                        $oo = "stat-succ";
                        $ro = "";
                    } elseif ($order["status"] == 3) {
                        $co = "stat-succ";
                        $oo = "stat-succ";
                        $ro = "stat-succ";
                        $receivedBtn = "";
                    }

                    echo '
                        <section class="order">
                            <section class="order-info">
                                <figure>
                                    <img src="' . itemImgs . $order["itemImg"] . '">
                                </figure>
                                <section class="oi">
                                    <span class="n">name</span>
                                    <a href="itemDetails.php?itemId=' . $order["itemId"] . '" class="v">' . $order["itemName"] . '</a>
                                </section>
                                <section class="oi">
                                    <span class="n">quantity</span>
                                    <span class="v">' . $order["quantity"] . '</span>
                                </section>
                                <section class="oi">
                                    <span class="n">net price</span>
                                    <span class="v">' . $theCurrency . $order["netPrice"] . '</span>
                                </section>
                                <section class="oi">
                                    <span class="n">delivery code</span>
                                    <span class="v">' . $order["deliveryCode"] . '</span>
                                </section>
                                <section class="oi">
                                    <span class="n">the seller</span>
                                    <a href="#go to the saller profile" class="v">' . $order["username"] . '</a>
                                </section>
                            </section>

                            <section class="order-show-details">
                                <section class="show-details-btn-cont">
                                    <button class="show-details-btn"><i class="fa-solid fa-circle-info"></i> details</button>
                                </section>

                                <section class="order-details">
                                    <section class="order-status">
                                        <section class="stat ' . $co . '">
                                            <section class="bar"><span><i class="fa-solid fa-check"></i></span></section>
                                            <p>Confirm order</p>
                                        </section>
                                        <section class="stat ' . $oo . '">
                                            <section class="bar"><span><i class="fa-solid fa-truck"></i></span></section>
                                            <p>On the way</p>
                                        </section>
                                        <section class="stat ' . $ro . '">
                                            <section class="bar"><span><i class="fa-solid fa-people-carry-box"></i></span></section>
                                            <p>Receive order</p>
                                        </section>
                                    </section>

                                    ' . $receivedBtn . '

                                </section>
                            </section>

                        </section>
                    ';
                }

                echo '
                    <section class="overlay" id="cartOverlay">
                        <section class="testmonial">
                            <form>
                                <input type="hidden" id="cartId">
                                <input type="hidden" id="itemId">
                                <textarea placeholder="Your comment" id="comment"></textarea>
                            </form>
                            <section class="rating">
                                <section class="test-info" id="like">
                                    <span class="ico"><i class="fa-solid fa-thumbs-up"></i></span>
                                    <span class="count">like</span>
                                </section>
                                <section class="test-info" id="dislike">
                                    <span class="ico"><i class="fa-solid fa-thumbs-down"></i></span>
                                    <span class="count">dislike</span>
                                </section>
                            </section>
                            <section class="buttons">
                                <button type="button" class="btn ok" id="ok">ok</button>
                                <button type="button" class="btn" id="overlayCancel">cancel</button>
                            </section>
                        </section>
                    </section>
                ';

            } else {
                echo "<section class='worning'>" . $nbText . "</section>";
            }

            echo '
                    </section>
                </section>
            ';

        } else {
            echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
        }
        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }