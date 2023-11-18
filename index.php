<?php
    session_start();
    $titleKey = "login"; // Key for get the title from lang file
    include "init.inc.php";

    // Get the visible categories id
    $visibleCats = getAllRecords("catId", "categories", "visibility = 1", "ordering");
    $vCats = "(";
    while ($catId = $visibleCats->fetchColumn()) {
        $vCats .= $catId . ", ";
    }
    $vCats = trim($vCats, ", ") . ")";

    if (isset($_SESSION["userfront"])) {
        // Get the favorite category id
        $getFavCat = $con->prepare("SELECT catId FROM views JOIN items USING (itemId) WHERE views.userId = :sessionId GROUP BY catId ORDER BY count(views.userId) DESC LIMIT 1");
        $getFavCat->bindParam("sessionId", $_SESSION["userfrontid"]);
        $getFavCat->execute();
        // == Check if the user has a favorite category
        if ($getFavCat->rowCount() == 0) {
            // The current user don't has a favorite category
            $favCatCond = 1;
        } else {
            $favCatId = $getFavCat->fetchColumn();
            $favCatCond = "catId = $favCatId";
        }

        // Get four items from favorite category
        $getFavItems = $con->prepare("SELECT items.*, percent, endsIn FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE accept = 1 AND catId IN $vCats AND $favCatCond ORDER BY RAND() LIMIT 4");
        $getFavItems->execute();
        $favItems = $getFavItems->fetchAll();

        // Get the four favorite items id
        $favItemsId = "(";
        foreach ($favItems as $item) {
            $favItemsId .= $item["itemId"] . ", ";
        }
        $favItemsId = trim($favItemsId, ", ") . ")";

        // Get rest of the items
        $restOfItems = $con->prepare("SELECT items.*, percent, endsIn FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE accept = 1 AND catId IN $vCats AND itemId NOT IN $favItemsId ORDER BY RAND()");
        $restOfItems->execute();

        // Return all items (four favorite items) and (the rest items)
        $allItems = array_merge($favItems, $restOfItems->fetchAll());

        // Return The items "id" added to cart by this user "sessionId"
        $cartItems = getAllRecords("itemId", "cart", "status = 0 AND userId = " . intval($_SESSION["userfrontid"]), "itemId");
        $cartItemsId = array();
        while ($cartItemId = $cartItems->fetchColumn()) {
            array_push($cartItemsId, $cartItemId);
        }

    } else {
        // Return all accepted items
        $allItems = $con->prepare("SELECT items.*, percent, endsIn FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE accept = 1 AND catId IN $vCats ORDER BY RAND()");
        $allItems->execute();
        $allItems = $allItems->fetchAll();
    }

    // Get likes count and dislikes count for each item
    $getLikes = $con->prepare("SELECT items.itemId, COUNT(items.itemId) AS count FROM items JOIN rating USING (itemId) WHERE accept = 1 GROUP BY items.itemId, rating.rating HAVING rating = 1");
    $getLikes->execute();
    $likes = array();
    while ($like = $getLikes->fetch()) {
        $likes[$like["itemId"]] = $like["count"];
    }

    $getDislikes = $con->prepare("SELECT items.itemId, COUNT(items.itemId) AS count FROM items JOIN rating USING (itemId) WHERE accept = 1 GROUP BY items.itemId, rating.rating HAVING rating = 0");
    $getDislikes->execute();
    $dislikes = array();
    while ($dislike = $getDislikes->fetch()) {
        $dislikes[$dislike["itemId"]] = $dislike["count"];
    }
    
    if (count($allItems) > 0) {
    ?>
        <section class="items">
            <section class="container">
            <?php
            foreach ($allItems as $item) {
                $desc = $item["itemDescription"];
                if (strlen($desc) > 80) {
                    $desc = substr($desc, 0, 65) . '...';
                }

                // Check if this item is already add to cart by the current user
                $cart = '<a href="addToCart.php?itId=' . $item["itemId"] . '" class="btn add-cart"><i class="fa-solid fa-cart-plus"></i></a>';
                if (isset($cartItemsId)) {
                    if (in_array($item["itemId"], $cartItemsId)) {
                        $cart = "<span class='already-add'><i class='fa-solid fa-check'></i></span>";
                    }
                }

                // Item likes and dislikes count
                if (isset($likes[$item["itemId"]])) {
                    $likesCount =  $likes[$item["itemId"]];
                } else {
                    $likesCount =  0;
                }

                if (isset($dislikes[$item["itemId"]])) {
                    $dislikesCount = $dislikes[$item["itemId"]];
                } else {
                    $dislikesCount = "0";
                }

                if (is_null($item["discountId"])) {
                    // The item dont has a discount
                    echo '
                    <section class="item-box" data-location="itemDetails.php?itemId=' . $item["itemId"] . '">
                        <figure>
                            <img src="' . itemImgs . $item["itemImg"] . '">
                        </figure>

                        <h2>' . $item["itemName"] . '</h2>
                        <p class="desc">
                        ' . $desc . '
                        </p>
                        <section class="price">
                            <span class="main-price">' . $theCurrency . $item["itemPrice"] . '</span>
                        </section>
                        <section class="testmonials">
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-thumbs-up"></i></span>
                                <span class="count"> ' . $likesCount . '</span>
                            </section>
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-thumbs-down"></i></span>
                                <span class="count">' . $dislikesCount . '</span>
                            </section>
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-eye"></i></span>
                                <span class="count">' . $item["itemViews"] . '</span>
                            </section>
                        </section>

                        ' . $cart . '

                    </section>
                    ';
                } else {
                    // The item has a discount

                    // The price after discount
                    $newPrice = $item["itemPrice"] - ($item["itemPrice"] * ($item["percent"] / 100));
                    if (is_null($item["endsIn"])) {
                        $endsIn = "Unknown";
                    } else {
                        $endsIn = date("d/m H:i", strtotime($item["endsIn"]));
                    }

                    echo '
                    <section class="item-box" data-location="itemDetails.php?itemId=' . $item["itemId"] . '">
                        <figure>
                            <img src="' . itemImgs . $item["itemImg"] . '">
                        </figure>

                        <h2>' . $item["itemName"] . '</h2>
                        <p class="desc">
                        ' . $desc . '
                        </p>
                        <section class="price">
                            <span class="old-price">' . $theCurrency . $item["itemPrice"] . '</span>
                            <span class="new-price">' . $theCurrency . $newPrice . '</span>
                        </section>
                        <section class="testmonials">
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-thumbs-up"></i></span>
                                <span class="count"> ' . $likesCount . '</span>
                            </section>
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-thumbs-down"></i></span>
                                <span class="count">' . $dislikesCount . '</span>
                            </section>
                            <section class="rat-block">
                                <span class="ico"><i class="fa-solid fa-eye"></i></span>
                                <span class="count">' . $item["itemViews"] . '</span>
                            </section>
                        </section>

                        ' . $cart . '

                        <section class="discount-logo">
                            <span class="dis-percent">-' . $item["percent"] . '%</span>
                            <span class="dis-endsIn">' . $endsIn . '</span>
                        </section>

                    </section>
                    ';
                }
            }
            ?>
            </section>
        </section>
    <?php
    } else {
        echo "<section class='container'><section class='worning'>There is no items</section></section>";
    }

    include tpl . "footer.inc.php";