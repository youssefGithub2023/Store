<?php
    session_start();
    $titleKey = "login";
    include "init.inc.php";

    if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {
        $itemId = intval($_GET["itemId"]);

        if (isset($_SESSION["userfrontid"])) {
            $isLogin = true;
            if (isset($_GET["referer"]) && $_GET["referer"] == "profile") {
                $cond = "AND items.userId = :userId";
                $userId = intval($_SESSION["userfrontid"]);
                $check = true;
            } else {
                $cond = "AND accept = 1";
                $check = false;
            }
        } else {
            $isLogin = false;
            $cond = "AND accept = 1";
            $check = false;
        }

        $stmt = $con->prepare("SELECT items.*, username, catName, allowCommenting, percent, endsIn FROM items JOIN users USING (userId) JOIN categories USING (catId) LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE itemId = :itemId $cond");
        $stmt->bindParam("itemId", $itemId);
        if ($check) {
            $stmt->bindParam("userId", $userId);
        }
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $item = $stmt->fetch();
        ?>
            <section class="item-details">
                <h1><?php echo $item["itemName"] ?></h1>
                <?php
                if ($item["accept"] == 0) {
                    echo "<section class='container'><section class='worning'>This item is under review</section></section>";
                }

                if ($check) {
                    $mi  = '<span class="editImgBtn" data-col="itemImg" data-head="new main image"><i class="fa-solid fa-gear"></i></span>';
                    $si1 = '<span class="editImgBtn" data-col="si1" data-head="new secondry image 1"><i class="fa-solid fa-gear"></i></span>';
                    $si2 = '<span class="editImgBtn" data-col="si2" data-head="new secondry image 2"><i class="fa-solid fa-gear"></i></span>';
                    $si3 = '<span class="editImgBtn" data-col="si3" data-head="new secondry image 3"><i class="fa-solid fa-gear"></i></span>';
                    $si4 = '<span class="editImgBtn" data-col="si4" data-head="new secondry image 4"><i class="fa-solid fa-gear"></i></span>';
                } else {
                    $mi  = '';
                    $si1 = '';
                    $si2 = '';
                    $si3 = '';
                    $si4 = '';
                }
                ?>
                <section class="container">

                    <article class="card">
                        <section class="card-info">
                            <section class="item-images">
                                <figure class="main-img">
                                    <img src="<?php echo itemImgs . $item["itemImg"] ?>">
                                    <?php echo $mi ?>
                                </figure>
                                <section class="secondary-imgs">
                                    <figure>
                                        <img src="<?php echo itemSecondaryImgs . (is_null($item["si1"]) ? "question.png" : $item["si1"]); ?>">
                                        <?php echo $si1 ?>
                                    </figure>
                                    <figure>
                                        <img src="<?php echo itemSecondaryImgs . (is_null($item["si2"]) ? "question.png" : $item["si2"]); ?>">
                                        <?php echo $si2 ?>
                                    </figure>
                                    <figure>
                                        <img src="<?php echo itemSecondaryImgs . (is_null($item["si3"]) ? "question.png" : $item["si3"]); ?>">
                                        <?php echo $si3 ?>
                                    </figure>
                                    <figure>
                                        <img src="<?php echo itemSecondaryImgs . (is_null($item["si4"]) ? "question.png" : $item["si4"]); ?>">
                                        <?php echo $si4 ?>
                                    </figure>
                                </section>
                            </section>

                            <section class="item-info">
                                <ul>
                                    <li><span class="q">name: </span><span class="a"><?php echo $item["itemName"] ?></span></li>
                                    <li><span class="q">price: </span><span class="a"><?php echo $theCurrency . $item["itemPrice"] ?></span></li>
                                    <li><span class="q">made in: </span><span class="a"><?php echo $item["madeCountry"] ?></span></li>
                                    <li><span class="q">status: </span><span class="a"><?php echo $item["itemStatus"] ?></span></li>
                                    <li><span class="q">quantity: </span><span class="a"><?php echo $item["itemQuantity"] ?></span></li>
                                    <li>
                                        <span class="q">user: </span>
                                        <span class="a">
                                            <a href="#"><?php echo $item["username"] ?></a>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="q">category: </span>
                                        <span class="a">
                                            <a href="showItems.php?type=categories&catId=<?php echo $item["catId"] ?>"><?php echo $item["catName"] ?></a>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="q">added in: </span><span class="a"><?php echo myDate($item["addedDate"]) ?></span>
                                    </li>
                                </ul>

                                <?php
                                if (!is_null($item["discountId"])) {

                                    if (is_null($item["endsIn"])) {
                                        $endsIn = "Unknown";
                                    } else {
                                        $endsIn = date("d/m/Y H:i", strtotime($item["endsIn"]));
                                    }

                                    $newPrice = $item["itemPrice"] - ($item["itemPrice"] * ($item["percent"] / 100));

                                    echo '
                                    <section class="discount">
                                        <span class="percent">-' . $item["percent"] . '%</span>
                                        <span class="endsIn">' . $endsIn . '</span>
                                        <span class="new-price">(' . $theCurrency . $newPrice . ')</span>
                                    </section>
                                    ';
                                }

                                $getLikesCount = getAllRecords("itemId", "rating", "itemId = {$item['itemId']} AND rating = 1", "itemId");
                                $likesCount = $getLikesCount->rowCount();

                                $getDislikesCount = getAllRecords("itemId", "rating", "itemId = {$item['itemId']} AND rating = 0", "itemId");
                                $dislikesCount = $getDislikesCount->rowCount();

                                if ($isLogin) {
                                    if ($check) {
                                        $cdislike = "disabled";
                                        $clike = "disabled";
                                    } else {
                                        // Check if the current user is already rated current item
                                        $isAlreadyRated = $con->prepare("SELECT rating FROM rating WHERE userId = :userId AND itemId = :itemId");
                                        $isAlreadyRated->bindParam(":userId", $userId);
                                        $isAlreadyRated->bindParam(":itemId", $item['itemId']);
                                        $isAlreadyRated->execute();

                                        $cdislike = "";
                                        $clike = "";
                                        if ($isAlreadyRated->rowCount() > 0) {
                                            if ($isAlreadyRated->fetchColumn() == 1) {
                                                $clike = "active";
                                            } else {
                                                $cdislike = "active";
                                            }
                                        }
                                    }
                                } else {
                                    $cdislike = "";
                                    $clike = "";
                                }
                                ?>

                                <section class="testmonials">
                                    <section class="test-info watch">
                                        <span class="ico"><i class="fa-solid fa-eye"></i></span>
                                        <span class="count"><?php echo $item["itemViews"] ?></span>
                                    </section>
                                    <a href="rating.php?rat=l&itId=<?php echo $item["itemId"] ?>" class="test-info <?php echo $clike ?>">
                                        <span class="ico"><i class="fa-solid fa-thumbs-up"></i></span>
                                        <span class="count"><?php echo $likesCount ?></span>
                                    </a>
                                    <a href="rating.php?rat=d&itId=<?php echo $item["itemId"] ?>" class="test-info <?php echo $cdislike ?>">
                                        <span class="ico"><i class="fa-solid fa-thumbs-down"></i></span>
                                        <span class="count"><?php echo $dislikesCount ?></span>
                                    </a>
                                </section>

                                <section class="item-des">
                                    <h2>description</h2>
                                    <p>
                                        <?php echo $item["itemDescription"] ?>
                                    </p>
                                </section>

                                <?php
                                    if (! empty($item["itemTags"])) {
                                        echo '
                                        <section class="item-tags">
                                            <h2>tags</h2>
                                            <section class="tags">
                                            ';
                                            $tags = explode(",", $item["itemTags"]);
                                            foreach ($tags as $tag) {
                                                echo "<a href='showItems.php?type=tags&tag=" . $tag . "' class='tag'>" . $tag . "</a>";
                                            }
                                            echo '
                                            </section>
                                        </section>
                                        ';
                                    }
                                
                                    if ($check) {
                                    ?>
                                        <section class="operations">
                                            <a href='editItem.php?itemId=<?php echo $item["itemId"] ?>' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a>
                                            <a href='deleteItem.php?itemId=<?php echo $item["itemId"] ?>' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>
                                        </section>
                                    <?php
                                    }

                                    if ($isLogin) {
                                        if (intval($_SESSION["userfrontid"]) != $item["userId"]) {

                                            $alreadyAdded = getAllRecords("itemId", "cart", "status = 0 AND userId = " . intval($_SESSION["userfrontid"]) . " AND itemId = " . $item["itemId"], "itemId");

                                            if ($alreadyAdded->rowCount() > 0) {
                                                echo "<span class='already-add'><i class='fa-solid fa-cart-plus'></i> Already added</span>";
                                            } else {
                                                echo '<a href="addToCart.php?itId=' . $item["itemId"] . '" class="btn add-cart"><i class="fa-solid fa-cart-plus"></i> Add to cart</a>';
                                            }
                                        }
                                    } else {
                                        echo '<a href="addToCart.php?itId=' . $item["itemId"] . '" class="btn add-cart"><i class="fa-solid fa-cart-plus"></i> Add to cart</a>';
                                    }
                                ?>

                            </section>
                        </section>
                    </article>

                </section>

                <!-- Start overlay for edit item imgs -->
                <section class="overlay overlayEditImg" id="itemOverlay">
                    <form method="post" action="editImg.php" enctype="multipart/form-data">
                        <input type="hidden" name="editType" value="itemImg">
                        <input type="hidden" name="itId" value="<?php echo $item["itemId"] ?>">
                        <input type="hidden" name="col" id="col" value="">
                        <label class="form-label" id="labelText" for="newImg"></label>
                        <section class="form-group">
                            <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="newImg" id="newImg">
                        </section>
                        <section class="op-cont">
                            <button type="submit" class="btn" name="edit"><i class="fa-solid fa-pen"></i> edit</button>
                            <button type="submit" class="btn" id="delete" name="delete"><i class="fa-solid fa-trash-can"></i> delete</button>
                        </section>
                        <section class="cancel" id="cancelOverlay"><i class="fa-solid fa-xmark"></i></section>
                    </form>
                </section>
                <!-- End overlay for edit item imgs -->
            </section>

            <section class="item-comments">
                <section class="container">
                    <h2>comments</h2>

                    <!-- Start add and edit comment -->
                    <?php
                        if (isset($_SESSION['userfront'])) {

                            if ($item["allowCommenting"] == 1) {

                                $id = intval($_SESSION["userfrontid"]);
                                
                                if ($item["userId"] == $id) {
                                    echo "<section class='worning'>You can't add any comment, because this item is for you</section>";
                                } else {
                                    if (isActivatedUser($id)) {

                                        $isWriteComment = $con->prepare("SELECT commentId, comment FROM comments WHERE itemId = {$item['itemId']} AND userId = :sessionId");
                                        $isWriteComment->bindParam("sessionId", $id);
                                        $isWriteComment->execute();

                                        if ($isWriteComment->rowCount() == 0) {

                                            // Start add comment block
                                            echo '
                                                <section class="add-comment">
                                                    <form method="post">
                                                        <textarea name="comment" placeholder="your comment..."></textarea>
                                                        <input type="submit" name="addComment" value="add comment" class="btn">
                                                    </form>
                                                </section>
                                            ';
        
                                            if (isset($_POST["addComment"])) {
                                                $comment = trim(filter_var($_POST["comment"], FILTER_SANITIZE_STRING));
        
                                                if (empty($comment)) {
                                                    echo "<section class='alert'>The comment can't be empty</section>";
                                                } else {
                                                    $insertComment = $con->prepare("INSERT INTO comments(comment, userId, itemId) VALUES (:comment, :userId, :itemId)");
                                                    $insertComment->bindParam("comment", $comment);
                                                    $insertComment->bindParam("userId", $id);
                                                    $insertComment->bindParam("itemId", $item["itemId"]);
                                                    $insertComment->execute();

                                                    if ($insertComment) {
                                                        echo "<script>location.reload()</script>";
                                                    }
                                                }
                                            }
                                            // End add comment block

                                        } else {

                                            // Start edit comment block
                                            $commentRecord = $isWriteComment->fetch();
                                            $commentId = $commentRecord["commentId"];

                                            if (isset($_POST["save"])) {
                                                $commentText = trim($_POST["comment"]);
                                            } else {
                                                $commentText = $commentRecord["comment"];
                                            }

                                            echo '
                                                <section id="editComment" class="edit-comment">
                                                    <section class="success">Edit your comment</section>
                                                    <form method="post">
                                                        <textarea name="comment">' . $commentText . '</textarea>
                                                        <input type="submit" name="save" value="edit comment" class="btn">
                                                    </form>
                                                </section>
                                            ';
        
                                            if (isset($_POST["save"])) {
                                                $comment = trim(filter_var($_POST["comment"], FILTER_SANITIZE_STRING));
        
                                                if (empty($comment)) {

                                                    $deleteCommentStmt = $con->prepare("DELETE FROM comments WHERE commentId = $commentId");
                                                    $deleteCommentStmt->execute();

                                                    if ($deleteCommentStmt) {
                                                        echo "<script>location.reload()</script>";
                                                    }

                                                } else {
                                                    $updateComment = $con->prepare("UPDATE comments SET comment = :comment WHERE commentId = $commentId");
                                                    $updateComment->bindParam("comment", $comment);
                                                    $updateComment->execute();

                                                    if ($updateComment) {
                                                        if ($updateComment->rowCount() > 0) {
                                                            $inStmt = $con->prepare("UPDATE comments SET commentStatus = 0 WHERE itemId = {$item['itemId']} AND userId = :sessionId");
                                                            $inStmt->bindParam("sessionId", $id);
                                                            $inStmt->execute();

                                                            echo "<section class='success'>The comment edited successfully</section>";
                                                        }
                                                    }
                                                }
                                            }
                                            // End edit comment block

                                        }
                                    } else {
                                        echo "<section class='worning'>You can not added any comments because, your account is not activated yet</section>";
                                    }
                                }


                            
                            } else {
                                echo "<section class='worning'>The commenting is disabled</section>";
                            }
                        } else {
                            echo "<section class='worning'>Login or sign-up for add a comment</section>";
                        }
                    ?>
                    <!-- End add and edit comment -->

                    <!-- Start show comments -->
                    <section class="show-comments">
                        <?php
                            $showComments = $con->prepare("SELECT comments.*, username, profileImgPath FROM comments JOIN users USING (userId) WHERE itemId = :itemId AND commentStatus = 1 ORDER BY commentId DESC");
                            $showComments->bindParam("itemId", $item["itemId"]);
                            $showComments->execute();

                            if ($showComments->rowCount() > 0 ) {
                                while ($commentRec = $showComments->fetch()) {
                                    echo '
                                        <article class="comment">
                                            <header>
                                                <section>
                                                    <figure>
                                                        <img src="' . profileImgs . proImgPath($commentRec["profileImgPath"], $commentRec["username"]) . '">
                                                    </figure>
    
                                                    <p>' . $commentRec["username"] . '</p>
                                                </section>
                                            </header>
    
                                            <p class="comment-text">' . $commentRec["comment"] . '</p>
    
                                            <footer>
                                                <section class="added-date">' . myDate($commentRec["addedDate"]) . '</section>
                                            </footer>
                                        </article>
                                    ';
                                }
                            } else {
                                echo "<section class='worning'>There is no comments to show</section>";
                            }
                        ?>
                        <!-- End show comments -->
                    </section>
                </section>
            </section>
        
        <?php

            // This statment added for statistics like
            if (isset($_SESSION["userfrontid"])) {

                if ($_SESSION["userfrontid"] != $item["userId"]) {
                    // Add record to views table if the user is signed and this item is not him
                    $addViews = $con->prepare("INSERT INTO views (userId, itemId) VALUES (:sessionId, :itemId)");
                    $addViews->bindParam("sessionId", $_SESSION["userfrontid"]);
                    $addViews->bindParam("itemId", $item["itemId"]);
                    $addViews->execute();

                    // Add one view to items.views for this item
                    $addItemView = $con->prepare("UPDATE items SET itemViews = itemViews + 1 WHERE itemId = :itemId");
                    $addItemView->bindParam("itemId", $item["itemId"]);
                    $addItemView->execute();
                }

            }

        } else {
            echo "<section class='container'><section class='worning'>There is no match or this item under review</section></section>";
        }
        
    } else {
        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
    }

    include tpl . "footer.inc.php"; 