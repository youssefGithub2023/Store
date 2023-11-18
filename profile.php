<?php

    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        // Get user info
        $getUser = $con->prepare("SELECT *, CONCAT(firstName, ' ', lastName) AS fullName, DATE(dateOfRegistration) AS registredIn FROM users WHERE userId = :userId");
        $getUser->bindParam("userId", $_SESSION["userfrontid"]);
        $getUser->execute();
        $user = $getUser->fetch();

        // Get user items
        $getItems = $con->prepare("SELECT items.*, percent, endsIn FROM items LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE items.userId = {$user['userId']} ORDER BY itemId DESC");
        $getItems->execute();

        // Get the invisible categories
        $invisibleCats = getAllRecords("catId", "categories", "visibility = 0", "ordering");
        $invCats = array();
        while ($catId = $invisibleCats->fetchColumn()) {
            array_push($invCats, $catId);
        }

        // Get user comments
        $getComments = $con->prepare("SELECT comments.*, items.itemName, items.itemImg FROM comments JOIN items USING (itemId) WHERE comments.userId = :userId ORDER BY commentId DESC");
        $getComments->bindParam("userId", $user["userId"]);
        $getComments->execute();

        ?>
        <h1 class="pro-h1">profile</h1>
        <section class="profile container">
            <section class="personal-info">
                <section class="imgAndName">
                    <figure>
                        <img src="<?php echo profileImgs . proImgPath($user["profileImgPath"], $user["username"]) ?>">
                        <span id="editProfileImg"><i class="fa-solid fa-gear"></i></span>
                    </figure>
                    <span class="full-name"><?php echo $user["fullName"] ?></span>
                </section>
                <ul>
                    <li><i class="fa-solid fa-user"></i> <?php echo $user["username"] ?></li>
                    <li><i class="fa fa-envelope"></i> <?php echo $user["email"] ?></li>
                    <li><i class="fa-solid fa-calendar-days"></i> registred in: <?php echo date("d/m/Y", strtotime($user["registredIn"])) ?></li>
                </ul>

                <a class="edit" href="editProfile.php"><i class='fa-solid fa-user-pen'></i>edit profile</a>

                <section class="fav">
                    <section class="fav-cat">
                        <p>favorite category</p>
                        <?php
                            $getFavCat = $con->prepare("SELECT catName FROM items JOIN categories USING (catId) WHERE userId = :sessionId GROUP BY catId ORDER BY count(itemId) DESC LIMIT 1");
                            $getFavCat->bindParam("sessionId", $user["userId"]);
                            $getFavCat->execute();
                            $favCat = $getFavCat->fetchColumn();

                            echo "<span>$favCat</span>";
                        ?>
                    </section>

                    <section class="total-items">
                        <p>total items</p>
                        <span><?php echo $getItems->rowCount() ?></span>
                    </section>
                </section>

                <?php
                    $catsStat = $con->prepare("SELECT catName, COUNT(catName) AS count FROM views JOIN items USING (itemId) JOIN categories USING (catId) WHERE items.userId = :userId GROUP BY categories.catId ORDER BY count");
                    $catsStat->bindParam("userId", $user["userId"]);
                    $catsStat->execute();
                    if ($catsStat->rowCount() > 0) {

                ?>
                    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
                    <script type="text/javascript">
                        google.charts.load('current', {'packages':['corechart']});
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {

                            var data = google.visualization.arrayToDataTable([
                                ['Category', 'Views']
                                <?php
                                while ($catStat = $catsStat->fetch()) {
                                    echo ", ['" . $catStat["catName"] . "', " . $catStat["count"] . "]";
                                }
                                ?>
                            ]);

                            var options = {
                                title: 'Categories statistics',
                                fontSize: 14
                            };

                            var chart = new google.visualization.PieChart(document.getElementById('piechart'));

                            chart.draw(data, options);
                        }
                    </script>
                    <section class="cats-stat">
                        <div id="piechart"></div>
                    </section>
                <?php
                    }
                ?>
            </section>

            <section class="work">
                <section class="profile-items">
                    <h2>your items</h2>
                    <section class="user-items">
                    <?php
                    if ($getItems->rowCount() > 0) {

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

                        while ($item = $getItems->fetch()) {
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

                            if (is_null($item["discountId"])) {
                                // The item dont has a discount
                                echo '
                                <section class="item-box" data-location="itemDetails.php?itemId=' . $item["itemId"] . '&referer=profile">
                                    <figure>
                                        <img src="' . itemImgs . $item["itemImg"] . '">
                                    </figure>
            
                                    <h2>' . $item["itemName"] . '</h2>
                                    <p class="desc">
                                    ' . $desc . '
                                    </p>
                                    <section class="price">' . $theCurrency . $item["itemPrice"] . '</section>
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
                                    </section>'
                                    ;

                                    if ($item["accept"] == 0) {
                                        echo '<section class="item-nb item-accept">Not accept yet</section>';
                                    }

                                    if (in_array($item["catId"], $invCats)) {
                                        echo '<section class="item-nb item-invisible">Invisible item</section>';
                                    }
                                    
                                echo '
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
                                <section class="item-box" data-location="itemDetails.php?itemId=' . $item["itemId"] . '&referer=profile">
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
                                    </section>'
                                    ;

                                    if ($item["accept"] == 0) {
                                        echo '<section class="item-nb item-accept">Not accept yet</section>';
                                    }

                                    if (in_array($item["catId"], $invCats)) {
                                        echo '<section class="item-nb item-invisible">Invisible item</section>';
                                    }

                                echo '
                                    <section class="discount-logo">
                                        <span class="dis-percent">-' . $item["percent"] . '%</span>
                                        <span class="dis-endsIn">' . $endsIn . '</span>
                                    </section>
                                </section>
                                ';
                            }

                        }
                    } else {
                        echo "<section class='worning'>You have not uploaded any items</section>";
                    }
                    ?>
                    </section>
                    <a href="addItem.php" class="add"><i class="fa-solid fa-plus"></i>Add new item</a>
                </section>
                <section class="profile-comments">
                    <h2>your comments</h2>
                    <section class="user-comments">
                    <?php
                    if ($getComments->rowCount() > 0) {
                        while ($comment = $getComments->fetch()) {
                            echo '
                                <article class="comment">
                                    <header>
                                        <section>
                                            <figure>
                                                <img src="' . itemImgs . $comment["itemImg"] . '">
                                            </figure>

                                            <p>' . $comment["itemName"] . '</p>
                                        </section>
                                    </header>

                                    <p class="comment-text">' . $comment["comment"] . '</p>

                                    <footer>
                                        <section class="added-date">' . myDate($comment["addedDate"]) . '</section>
                                        <section class="operations">
                                            <a href="itemDetails.php?itemId=' . $comment["itemId"] . '#editComment" class="edit"><i class="fa-solid fa-pen-to-square"></i>edit</a>
                                        
                                            <a href="deleteComment.php?cId=' . $comment["commentId"] . '" class="delete"><i class="fa-solid fa-trash-can"></i>delete</a>
                                            </section>
                                    </footer>
                                </article>
                            ';
                        }
                    } else {
                        echo "<section class='worning'>You have not written any comments</section>";
                    }
                    ?>
                    </section>
                </section>
            </section>

            <section class="overlay overlayEditImg" id="profileOverlay">
                <form method="post" action="editImg.php" enctype="multipart/form-data">
                    <input type="hidden" name="editType" value="profileImg">
                    <label class="form-label" for="newProfileImg">new profile image</label>
                    <section class="form-group">
                        <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="newProfileImg" id="newProfileImg">
                    </section>
                    <section class="op-cont">
                        <button type="submit" class="btn" name="edit"><i class="fa-solid fa-pen"></i> edit</button>
                        <button type="submit" class="btn" name="delete"><i class="fa-solid fa-trash-can"></i> delete</button>
                    </section>
                    <section class="cancel" id="cancelOverlay"><i class="fa-solid fa-xmark"></i></section>
                </form>
            </section>
        </section>

        <?php
        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
        exit();
    }