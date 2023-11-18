<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo printTitle(); ?></title>
        <link rel="stylesheet" href="<?php echo css; ?>all.min.css">
        <link rel="stylesheet" href="<?php echo css; ?>styles.css">
        <link rel="icon" href="<?php echo imgs . 'logo.png' ?>">
    </head>
    <body>
    <header>
        <section class="container">

            <section class="logo">
                <a href="index.php"><img src="<?php echo imgs . "logo.png" ?>"><span>ecom</span></a>
            </section>

            <form>
                <button><i class="fa-solid fa-magnifying-glass"></i></button><input type="search" placeholder="Search items" id="searchInput" list="suggestionsItems">
                <datalist id="suggestionsItems"></datalist>
            </form>

            <section class="nav-bar">
                <nav>
                    <section class="dropdown-btn-header">
                        <span>Categories <i class="fa-solid fa-chevron-down"></i></span>
                    </section>
                    <ul class="dropdown-items-header">
                        <li><a href="showItems.php?type=discount">Items has discount</a></li>
                    <?php
                        $parentCategories = getAllRecords("*", "categories", "catParent = 0 AND visibility = 1", "ordering");
                        
                        while ($parentCat = $parentCategories->fetch()) {
                            $childrenCategories = getAllRecords("*", "categories", "catParent = " . $parentCat["catId"] . " AND visibility = 1", "ordering");

                            if ($childrenCategories->rowCount() == 0) {

                                echo "<li><a href='showItems.php?type=categories&catId=" . $parentCat["catId"] . "'>" . $parentCat["catName"] . "</a></li>";

                            } else {

                                echo '
                                    <li class="cats-child-dropdown">
                                        <span>' . $parentCat["catName"] . ' <i class="fa-solid fa-chevron-left"></i></span>
                                        <ul>
                                            <li><a href="showItems.php?type=categories&catId=' . $parentCat["catId"] . '">' . $parentCat["catName"] . '</a></li>
                                ';

                                while ($childCat = $childrenCategories->fetch()) {
                                    echo "<li><a href='showItems.php?type=categories&catId=" . $childCat["catId"] . "'>" . $childCat["catName"] . "</a></li>";
                                }

                                echo '
                                        </ul>
                                    </li>
                                ';

                            }
                        }
                    ?>
                    </ul>
                </nav>

                <nav>
                    <section class="dropdown-btn-header">
                        <span>Operations <i class="fa-solid fa-chevron-down"></i></span>
                    </section>
                    <ul class="dropdown-items-header">

                <?php
                    if (isset($_SESSION["userfront"])) {
                        $userId = intval($_SESSION["userfrontid"]);

                        $getUser = getAllRecords("userId, username, profileImgPath, regStatus", "users", "userId = $userId", "userId");
                        $userData = $getUser->fetch();

                        // Get count of the not redable notifications
                        $getNotReadableNotsCount = $con->prepare("SELECT notId FROM notifications WHERE userId = :userId AND notStatus = 0");
                        $getNotReadableNotsCount->bindParam("userId", $userId);
                        $getNotReadableNotsCount->execute();
                        $count = $getNotReadableNotsCount->rowCount();

                        // Get count of current user's items in the cart
                        $countCart = getAllRecords("userId", "cart", "userId = $userId AND status = 0", "userId");
                        $cItemsCount = $countCart->rowCount();
                        ?>
                                <li><a href="cart.php" class="header-ico"><i class="fa-solid fa-cart-plus"></i> cart <span class="count"><?php echo $cItemsCount ?></span></a></li>
                                <li><a href="notifications.php" class="header-ico"><i class="fa-solid fa-bell"></i> notification <span class="count"><?php echo $count ?></span></a></li>
                                <li><a href="profile.php"><i class="fa-solid fa-user"></i> my profile</a></li>
                                <li><a href="purchases.php"><i class="fa-solid fa-basket-shopping"></i> my purchases</a></li>
                                <li><a href="sales.php"><i class="fa-solid fa-cart-flatbed"></i> my sales</a></li>
                                <li><a href="addItem.php"><i class="fa-solid fa-plus"></i> add item</a></li>
                                <li><a href="discounts.php"><i class="fa-solid fa-percent"></i> discounts</a></li>
                                <li><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> logout</a></li>
                        <?php
                    } else {
                        echo '
                                <li><a href="sign.php?block=login"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a></li>
                                <li><a href="sign.php?block=sign-up"><i class="fa-solid fa-person-walking-arrow-right"></i> sign up</a></li>
                        ';
                    }
                ?>

                    </ul>
                </nav>
            </section>
        </section>
    </header>