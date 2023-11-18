<?php
    session_start();

    $titleKey = "login"; // Key for get the title from lang file
    include "init.inc.php";

    if (isset($_GET["type"]) && in_array($_GET["type"], ["categories", "discount", "tags"])) {
        $type = filter_var($_GET["type"], 513);
        $h1Text = "";

        if ($type == "categories") {
            if (isset($_GET["catId"]) && is_numeric($_GET["catId"])) {
                $catId = intval(filter_var($_GET["catId"], FILTER_SANITIZE_NUMBER_INT));

                // Get category name
                $getCatName = getAllRecords("catName", "categories", "catId = $catId AND visibility = 1", "catId");
                if ($getCatName->rowCount() > 0) {
                    $catName = $getCatName->fetchColumn();

                    // Pagination
                    $maxRecords = 12;
                    $totalRecords = $con->prepare("SELECT COUNT(itemId) FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE catId = $catId AND accept = 1");
                    $totalRecords->execute();
                    $totalRecords = $totalRecords->fetchColumn();
                    $pagesCount = ceil($totalRecords / $maxRecords);

                    if (isset($_GET["page"])) {
                        if (is_numeric($_GET["page"]) && $_GET["page"] >= 1 && $_GET["page"] <= $pagesCount) {
                            $currentPage = intval($_GET["page"]);
                        } else {
                            $currentPage = 1;
                        }
                    } else {
                        $currentPage = 1;
                    }

                    $offset = ($currentPage - 1) * 12;
                     // Get only accepted items (itemStatus = 1)
                    $items =  $con->prepare("SELECT items.*, percent, endsIn FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE catId = $catId AND accept = 1 ORDER BY RAND() LIMIT $maxRecords OFFSET $offset");
                    $items->execute();

                    $h1Text = $catName . " categorie";

                } else {
                    redirect("<section class='container'><section class='alert'>The category you selected is incorrect</section></section>", "index.php", 3);
                    exit();
                }

            } else {
                redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
                exit();
            }

        } elseif ($type == "discount") {

            // Get the visible categories id
            $visibleCats = getAllRecords("catId", "categories", "visibility = 1", "ordering");
            $vCats = "(";
            while ($catId = $visibleCats->fetchColumn()) {
                $vCats .= $catId . ", ";
            }
            $vCats = trim($vCats, ", ") . ")";

            // Pagination
            $maxRecords = 12;
            $totalRecords = $con->prepare("SELECT COUNT(itemId) FROM items WHERE ! ISNULL(discountId) AND accept = 1 AND catId IN $vCats");
            $totalRecords->execute();
            $totalRecords = $totalRecords->fetchColumn();
            $pagesCount = ceil($totalRecords / $maxRecords);

            if (isset($_GET["page"])) {
                if (is_numeric($_GET["page"]) && $_GET["page"] >= 1 && $_GET["page"] <= $pagesCount) {
                    $currentPage = intval($_GET["page"]);
                } else {
                    $currentPage = 1;
                }
            } else {
                $currentPage = 1;
            }

            $offset = ($currentPage - 1) * 12;
            $items = $con->prepare("SELECT items.*, percent, endsIn FROM items JOIN discounts ON items.discountId = discounts.discountId WHERE accept = 1 AND catId IN $vCats LIMIT $maxRecords OFFSET $offset");
            $items->execute();

            $h1Text = "Items have discount";

        } elseif ($type == "tags") {
            if (isset($_GET["tag"])) {
                $tag = filter_var($_GET["tag"], 513);

                // Get the visible categories id
                $visibleCats = getAllRecords("catId", "categories", "visibility = 1", "ordering");
                $vCats = "(";
                while ($catId = $visibleCats->fetchColumn()) {
                    $vCats .= $catId . ", ";
                }
                $vCats = trim($vCats, ", ") . ")";

                // Pagination
                $maxRecords = 12;
                $totalRecords = $con->prepare("SELECT COUNT(itemId) FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE itemTags LIKE '%$tag%' AND accept = 1 AND catId IN $vCats");
                $totalRecords->execute();
                $totalRecords = $totalRecords->fetchColumn();
                $pagesCount = ceil($totalRecords / $maxRecords);

                if (isset($_GET["page"])) {
                    if (is_numeric($_GET["page"]) && $_GET["page"] >= 1 && $_GET["page"] <= $pagesCount) {
                        $currentPage = intval($_GET["page"]);
                    } else {
                        $currentPage = 1;
                    }
                } else {
                    $currentPage = 1;
                }

                $offset = ($currentPage - 1) * 12;

                $items = $con->prepare("SELECT items.*, percent, endsIn FROM `items` LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE itemTags LIKE '%$tag%' AND accept = 1 AND catId IN $vCats ORDER BY RAND() LIMIT $maxRecords OFFSET $offset");
                $items->execute();

                $h1Text = $tag . " tag";

            } else {
                redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
                exit();
            }
        }

        // Add to cart
        if (isset($_SESSION["userfront"])) {
            $sessionId = intval($_SESSION["userfrontid"]);
            // Return The items "id" added to cart by this user "sessionId"
            $cartItems = getAllRecords("itemId", "cart", "status = 0 AND userId = " . $sessionId, "itemId");
            $cartItemsId = array();
            while ($cartItemId = $cartItems->fetchColumn()) {
                array_push($cartItemsId, $cartItemId);
            }
        }

        if ($items->rowCount() > 0) {
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
        ?>

            <section class="items">
                <h1><?php echo $h1Text ?></h1>
                <section class="container">
                <?php
                while ($item = $items->fetch()) {
                    $desc = $item["itemDescription"];
                    if (strlen($desc) > 80) {
                        $desc = substr($desc, 0, 65) . '...';
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

                    // // Check if this item is already add to cart by the current user
                    $cart = '<a href="addToCart.php?itId=' . $item["itemId"] . '" class="btn add-cart"><i class="fa-solid fa-cart-plus"></i></a>';
                    if (isset($cartItemsId)) {
                        if (in_array($item["itemId"], $cartItemsId)) {
                            $cart = "<span class='already-add'><i class='fa-solid fa-check'></i></span>";
                        }
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
                <ul class="pagination">
                    <li><a href="<?= slice($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "&")) . '&page=' . ($currentPage - 1) ?>" class="prev-next <?= $currentPage == 1 ? "disabled" : "" ?>"><i class="fa-sharp fa-solid fa-arrow-left"></i> Prev</a></li>

                <?php

                for ($i = 1; $i <= $pagesCount; $i += 1) {

                    if ($i == $currentPage) {
                        $c = 'class="active disabled"';
                    } else {
                        $c = "";
                    }

                    echo '<li> <a ' . $c . ' href="' . slice($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "&")) . '&page=' . $i . '">' . $i . '</i></a></li>';
                }
                
                ?>

                    <li><a href="<?= slice($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "&")) . '&page=' . ($currentPage + 1) ?>" class="prev-next <?= $currentPage == $pagesCount ? "disabled" : "" ?>">Next <i class="fa-sharp fa-solid fa-arrow-right"></i></a></li>
                </ul>
            </section>

        <?php
        } else {
            echo "<section class='container'><section class='worning'>There is no items</section></section>";
        }

    } else {
        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
    }

    include tpl . "footer.inc.php";