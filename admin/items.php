<?php
    session_start();
    
    if (isset($_SESSION["username"])) {
        $titleKey = "items";
        include "init.inc.php";

        if (isset($_GET['block'])) {
            if ($_GET['block'] == "add") {
                if (isset($_POST["add"])) {

                    $errors = array();

                    if (empty($_POST["itemName"])) {
                        array_push($errors, "Item name input can't be empty");
                    }

                    if (empty($_POST["itemDescription"])) {
                        array_push($errors, "Item description input can't be empty");
                    }

                    if (empty($_POST["itemPrice"])) {
                        array_push($errors, "Item price input can't be empty");
                    }

                    $itemImg = $_FILES["itemImg"];
                    if ($itemImg["error"] == 4) {
                        array_push($errors, "You didn't upload any image for this item");
                    } else {
                        $II_tmp_name = $itemImg["tmp_name"];
                        $II_name = $itemImg["name"];
                        $II_size = $itemImg["size"];
                        $ex = explode(".", $II_name);
                        $II_ext = strtolower(end($ex));
                        $avilable_extensions = array("png", "jpg", "jpeg", "gif");

                        if (! in_array($II_ext, $avilable_extensions)) {
                            array_push($errors, '"png", "jpg", "jpeg" and "gif" is the avilable extensions');
                        }

                        if ($II_size > 4194304) {
                            array_push($errors, 'This image is too large size must be smaller than 4MB');
                        }
                    }

                    if (empty($_POST["madeCountry"])) {
                        array_push($errors, "Item made country input can't be empty");
                    }

                    if (empty($_POST["itemStatus"])) {
                        array_push($errors, "Check the item status");
                    } elseif (!in_array($_POST["itemStatus"], ["new", "like new", "used", "old"])) {
                        array_push($errors, "Check the item status (new, like new, used, old)");
                    }

                    $itemTags = strtolower(trim(str_replace(" ", "", $_POST["itemTags"]), ","));
                    if (! empty($itemTags)) {
                        if (! preg_match("/^[a-z,]+$/", $itemTags)) {
                            array_push($errors, "tags input accept only letters and coma");
                        }
                    }

                    if (empty($_POST["userId"])) {
                        array_push($errors, "Check the user");
                    } elseif (!isAlreadyInUse("users", "userId", $_POST["userId"])) {
                        array_push($errors, "The user checked not exist");
                    }

                    if (empty($_POST["catId"])) {
                        array_push($errors, "Check the category");
                    } elseif (!isAlreadyInUse("categories", "catId", $_POST["catId"])) {
                        array_push($errors, "The category checked not exist");
                    }

                    if (empty($errors)) {
                        $activatedUser = $con->prepare("SELECT userId FROM users WHERE userId = :id AND regStatus = 1 AND (type = 0 OR userId = :sessionId)");
                        $activatedUser->bindParam("id", $_POST["userId"]);
                        $activatedUser->bindParam("sessionId", $_SESSION["userId"]);
                        $activatedUser->execute();

                        if ($activatedUser->rowCount() > 0) {
                            if (isAllowADS($_POST["catId"])) {
                                // Upload item image
                                $itemImgName = random_name(40) . "." . $II_ext;
                                move_uploaded_file($II_tmp_name, itemImgs . $itemImgName);

                                // Start item secondary images
                                $secondaryImgs = $_FILES["secondaryImgs"];
                                if ($secondaryImgs["error"][0] == 0) {
                                    $secondaryImgsValue = array();
                                    $avilable_extensions = array("png", "jpg", "jpeg", "gif");
                                    for ($i = 0; $i < 4; $i += 1) {
                                        if (isset($secondaryImgs["name"][$i])) {
                                            $si_tmp_name = $secondaryImgs["tmp_name"][$i];
                                            $si_size = $secondaryImgs["size"][$i];
                                            $si_type = $secondaryImgs["type"][$i];
                                            $si_type_ex = explode("/", $si_type);
                                            $si_ext = strtolower(end($si_type_ex));
                                            if (in_array($si_ext, $avilable_extensions)) {
                                                if ($si_size < 4194304) {
                                                    $si_random_name = random_name(40) . "." . $si_ext;
                                                    move_uploaded_file($si_tmp_name, itemSecondaryImgs . $si_random_name);
                                                    array_push($secondaryImgsValue, $si_random_name);
                                                }
                                            }
                                        } else {
                                            array_push($secondaryImgsValue, null);
                                        }
                                    }
                                } else {
                                    $secondaryImgsValue = array(null, null, null, null);
                                }
                                // End item secondary images

                                $stmt = $con->prepare("INSERT INTO items(itemName, itemDescription, itemPrice, itemImg, si1, si2, si3, si4, madeCountry, itemStatus, itemTags, accept, userId, catId) VALUES (:itemName, :itemDescription, :itemPrice, :itemImg, :si1, :si2, :si3, :si4, :madeCountry, :itemStatus, :itemTags, 1, :userId, :catId)");
                                $stmt->bindParam("itemName", $_POST["itemName"]);
                                $stmt->bindParam("itemDescription", $_POST["itemDescription"]);
                                $stmt->bindParam("itemPrice", $_POST["itemPrice"]);
                                $stmt->bindParam("itemImg", $itemImgName);
                                $stmt->bindParam("si1", $secondaryImgsValue[0]);
                                $stmt->bindParam("si2", $secondaryImgsValue[1]);
                                $stmt->bindParam("si3", $secondaryImgsValue[2]);
                                $stmt->bindParam("si4", $secondaryImgsValue[3]);
                                $stmt->bindParam("madeCountry", $_POST["madeCountry"]);
                                $stmt->bindParam("itemStatus", $_POST["itemStatus"]);
                                $stmt->bindParam("itemTags", $itemTags);
                                $stmt->bindParam("userId", $_POST["userId"]);
                                $stmt->bindParam("catId", $_POST["catId"]);
                                $stmt->execute();

                                // Insert notification after added an item by the admin for any user
                                $notification = "The <span class='focus'>" . $_POST["itemName"] . "</span> is added by admin for you";
                                generateNotification($notification, "not-normal", $_POST["userId"]);

                                $stmt2 = $con->prepare("SELECT itemId FROM items ORDER BY itemId DESC LIMIT 1");
                                $stmt2->execute();
                                $lastItemId = $stmt2->fetchColumn();

                                redirect("<section class='container'><section class='success'>One item added successfully</section></section>", "items.php?block=details&itemId=". $lastItemId, 3);
                            } else {
                                redirect("<section class='container'><section class='alert'>The category you selected is incorrect</section></section>", $_SERVER["HTTP_REFERER"], 3);
                            }
                        } else {
                            redirect("<section class='container'><section class='alert'>The item not added, because the selected user is incorrect</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        }
                    } else {
                        echo "<section class='container'>";
                        foreach ($errors as $error) {
                            echo "<section class='alert'>" . $error . "</section>";
                        }
                        echo "</section>";
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }
                } else {
                ?>
                    <section class="get-info">
                        <section class="container">
                            <form method="post" action="items.php?block=add" enctype="multipart/form-data">
                                <h1>Add new item</h1>
                                <section class="cont-inputs">
                                    <section class="left">
                                        <label class="form-label" for="itemName">item name</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemName" id="itemName">
                                        </section>

                                        <label class="form-label" for="itemImg">item main image</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="itemImg" id="itemImg">
                                        </section>

                                        <label class="form-label" for="secondaryImgs">item secondary images (max=4)</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="secondaryImgs[]" multiple id="secondaryImgs">
                                        </section>

                                        <section class="desc">
                                            <label class="form-label" for="desc-item">description</label>
                                            <textarea class="desc-item" id="desc-item" name="itemDescription"></textarea>
                                        </section>
                                    </section>

                                    <section class="right">
                                        <label class="form-label" for="itemPrice">price in <?php echo $theCurrency ?></label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemPrice" id="itemPrice" placeholder='<?php echo $theCurrency ?>'>
                                        </section>

                                        <label class="form-label" for="madeCountry">made in</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="madeCountry" id="madeCountry" placeholder='Country'>
                                        </section>

                                        <label class="form-label" for="itemStatus">status</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="itemStatus" id="itemStatus">
                                                <option value="">...check the status</option>
                                                <option value="new">new</option>
                                                <option value="like new">like new</option>
                                                <option value="used">used</option>
                                                <option value="old">old</option>
                                            </select>
                                        </section>

                                        <label class="form-label" for="itemTags">tags</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemTags" id="itemTags" placeholder='Enter tags seperate by coma ","' pattern="[a-z, ]+" title="Only accept letters, coma and spaces">
                                        </section>

                                        <label class="form-label" for="userId">membere</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="userId" id="userId">
                                                <option value="">...check the membere</option>
                                            <?php
                                                $usersStmt = $con->prepare("SELECT userId, username FROM users WHERE regStatus = 1 AND (type = 0 OR userId = :sessionId)");
                                                $usersStmt->bindParam("sessionId", $_SESSION["userId"]);
                                                $usersStmt->execute();

                                                while ($user = $usersStmt->fetch()) {
                                                    echo "<option value='" . $user['userId'] . "'>" . $user['username'] . "</option>";
                                                }
                                            ?>
                                            </select>
                                        </section>

                                        <label class="form-label" for="catId">categories</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="catId" id="catId">
                                                <option value="">...check the category</option>
                                            <?php
                                                $catsStmt = getAllRecords("catId, catName", "categories", "catParent = 0 AND allowADS = 1", "ordering");

                                                while ($cat = $catsStmt->fetch()) {
                                                    echo "<option value='" . $cat['catId'] . "'>" . $cat['catName'] . "</option>";

                                                    $subCats = getAllRecords("catId, catName", "categories", "catParent = {$cat['catId']} AND allowADS = 1", "ordering");

                                                    while ($subCat = $subCats->fetch()) {
                                                        echo "<option value='" . $subCat['catId'] . "'>--- " . $subCat['catName'] . "</option>";
                                                    }
                                                }
                                            ?>
                                            </select>
                                        </section>

                                    </section>
                                </section>
                                <button type="submit" name="add" class="btn">add</button>
                            </form>
                        </section>
                    </section>
                <?php
                }
            } elseif ($_GET['block'] == "details") {

                if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {
                    $itemId = intval($_GET["itemId"]);

                    $stmt = $con->prepare("SELECT items.*, username, catName, percent, endsIn FROM items JOIN users USING (userId) JOIN categories USING (catId) LEFT JOIN discounts ON items.discountId = discounts.discountId WHERE itemId = :itemId");
                    $stmt->bindParam("itemId", $itemId);
                    $stmt->execute();
                
                    if ($stmt->rowCount() > 0) {
                        $row = $stmt->fetch();
                        ?>
                        <section class="item-details comments-cont">
                            <h1><?php echo $row["itemName"] ?></h1>
                            <section class="container">

                                <article class="card">
                                    <section class="card-info">
                                        <section class="item-images">
                                            <figure class="main-img">
                                                <img src="<?php echo itemImgs . $row["itemImg"] ?>">
                                            </figure>
                                            <section class="secondary-imgs">
                                                <figure>
                                                    <img src="<?php echo itemSecondaryImgs . (is_null($row["si1"]) ? "question.png" : $row["si1"]); ?>">
                                                </figure>
                                                <figure>
                                                    <img src="<?php echo itemSecondaryImgs . (is_null($row["si2"]) ? "question.png" : $row["si2"]); ?>">
                                                </figure>
                                                <figure>
                                                    <img src="<?php echo itemSecondaryImgs . (is_null($row["si3"]) ? "question.png" : $row["si3"]); ?>">
                                                </figure>
                                                <figure>
                                                    <img src="<?php echo itemSecondaryImgs . (is_null($row["si4"]) ? "question.png" : $row["si4"]); ?>">
                                                </figure>
                                            </section>
                                        </section>

                                        <section class="item-info">
                                            <ul>
                                                <li><span class="q">name: </span><span class="a"><?php echo $row["itemName"] ?></span></li>
                                                <li><span class="q">price: </span><span class="a"><?php echo $theCurrency . $row["itemPrice"] ?></span></li>
                                                <li><span class="q">made in: </span><span class="a"><?php echo $row["madeCountry"] ?></span></li>
                                                <li><span class="q">status: </span><span class="a"><?php echo $row["itemStatus"] ?></span></li>
                                                <li><span class="q">quantity: </span><span class="a"><?php echo $row["itemQuantity"] ?></span></li>
                                                <li>
                                                    <span class="q">user: </span>
                                                    <span class="a">
                                                        <a href="members.php?only=<?php echo $row["userId"] ?>"><?php echo $row["username"] ?></a>
                                                    </span>
                                                </li>
                                                <li>
                                                    <span class="q">category: </span>
                                                    <span class="a">
                                                        <a href="categories.php?only=<?php echo $row["catId"] ?>"><?php echo $row["catName"] ?></a>
                                                    </span>
                                                </li>
                                                <li>
                                                    <span class="q">added in: </span><span class="a"><?php echo myDate($row["addedDate"]) ?></span>
                                                </li>
                                            </ul>

                                            <?php
                                                if (!is_null($row["discountId"])) {

                                                    if (is_null($row["endsIn"])) {
                                                        $endsIn = "Unknown";
                                                    } else {
                                                        $endsIn = myDate($row["endsIn"]);
                                                    }

                                                    $newPrice = $row["itemPrice"] - ($row["itemPrice"] * ($row["percent"] / 100));

                                                    echo '
                                                    <section class="discount">
                                                        <span class="percent">-' . $row["percent"] . '%</span>
                                                        <span class="endsIn">' . $endsIn . '</span>
                                                        <span class="new-price">(' . $theCurrency . $newPrice . ')</span>
                                                    </section>
                                                    ';
                                                }

                                                $getLikesCount = getAllRecords("itemId", "rating", "itemId = {$row['itemId']} AND rating = 1", "itemId");
                                                $likesCount = $getLikesCount->rowCount();

                                                $getDislikesCount = getAllRecords("itemId", "rating", "itemId = {$row['itemId']} AND rating = 0", "itemId");
                                                $dislikesCount = $getDislikesCount->rowCount();
                                            ?>

                                            <section class="testmonials">
                                                <section class="test-info">
                                                    <span class="ico"><i class="fa-solid fa-eye"></i></span>
                                                    <span class="count"><?php echo $row["itemViews"] ?></span>
                                                </section>
                                                <section class="test-info">
                                                    <span class="ico"><i class="fa-solid fa-thumbs-up"></i></span>
                                                    <span class="count"><?php echo $likesCount ?></span>
                                                </section>
                                                <section class="test-info">
                                                    <span class="ico"><i class="fa-solid fa-thumbs-down"></i></span>
                                                    <span class="count"><?php echo $dislikesCount ?></span>
                                                </section>
                                            </section>

                                            <section class="item-des">
                                                <h2>description</h2>
                                                <p>
                                                    <?php echo $row["itemDescription"] ?>
                                                </p>
                                            </section>

                                            <?php
                                                if (! empty($row["itemTags"])) {
                                                    echo '
                                                    <section class="item-tags">
                                                        <h2>tags</h2>
                                                        <section class="tags">
                                                        ';
                                                        $tags = explode(",", $row["itemTags"]);
                                                        foreach ($tags as $tag) {
                                                            echo "<span class='tag'>" . $tag . "</span>";
                                                        }
                                                        echo '
                                                        </section>
                                                    </section>
                                                    ';
                                                }
                                            ?>
                                            <section class="operations">
                                                <?php
                                                if ($row["accept"] == 0) {
                                                    echo "<a href='items.php?block=accept&itemId=" . $row["itemId"] . "' class='activate'><i class='fa-solid fa-check'></i>accept</a>";
                                                }
                                                ?>
                                                <a href='items.php?block=edit&itemId=<?php echo $row["itemId"] ?>' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a>
                                                <a href='items.php?block=delete&itemId=<?php echo $row["itemId"] ?>' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>
                                            </section>
                                        </section>
                                    </section>
                                </article>

                            </section>

                            <section class="container">
                                <section class="comments">
                                    <h2>comments</h2>
                                <?php
                                    // Get comments for this item
                                    $stmt2 = $con->prepare("SELECT comments.*, username, profileImgPath FROM comments JOIN users USING (userId) WHERE itemId = :itemId ORDER BY commentId DESC");
                                    $stmt2->bindParam("itemId", $row["itemId"]);
                                    $stmt2->execute();

                                    if ($stmt2->rowCount() > 0) {

                                        while ($record = $stmt2->fetch()) {
                                            echo '
                                                <article class="comment">
                                                    <header>
                                                        <section class="user">
                                                            <figure>
                                                                <img src="' . profileImgs . proImgPath($record["profileImgPath"], $record["username"]) . '">
                                                            </figure>
                                                            <p>' . $record["username"] . '</p>
                                                        </section>
                                                    </header>
                
                                                    <p class="comment-text">' . $record["comment"] . '</p>
                
                                                    <footer>
                                                        <section class="added-date">' . myDate($record["addedDate"]) . '</section>
                                                        <section class="operations">
                                                ';
                
                                                                if ($record["commentStatus"] == 0) {
                                                                    echo '<a href="comments.php?block=accept&commentId=' . $record["commentId"] . '" class="activate"><i class="fa-solid fa-check"></i>accept</a>';
                                                                }
                
                                                echo '
                                                                <a href="comments.php?block=edit&commentId=' . $record["commentId"] . '" class="edit"><i class="fa-solid fa-pen-to-square"></i>edit</a>
                                                                
                                                                <a href="comments.php?block=delete&commentId=' . $record["commentId"] . '" class="delete"><i class="fa-solid fa-trash-can"></i>delete</a>
                
                                                        </section>
                                                    </footer>
                                                </article>
                                            ';
                                        }

                                    } else {
                                        echo "<section class='worning'>There is no comments</section>";
                                    }
                                
                                ?>
                                </section>
                            </section>
                        </section>
                        <?php

                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "items.php", 3);
                    }
                
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visit this page directly</section></section>", "items.php", 3);
                }

            } elseif ($_GET["block"] == "edit") {
                if (isset($_POST["save"])) {
                    $errors = array();

                    if (empty($_POST["itemName"])) {
                        array_push($errors, "Item name input can't be empty");
                    }

                    if (empty($_POST["oldName"])) {
                        array_push($errors, "Item oldName input can't be empty");
                    }

                    if (empty($_POST["itemDescription"])) {
                        array_push($errors, "Item description input can't be empty");
                    }

                    if (empty($_POST["itemPrice"])) {
                        array_push($errors, "Item price input can't be empty");
                    }

                    $itemImg = $_FILES["itemImg"];
                    if ($itemImg["error"] != 4) {
                        $II_tmp_name = $itemImg["tmp_name"];
                        $II_name = $itemImg["name"];
                        $II_size = $itemImg["size"];
                        $ex = explode(".", $II_name);
                        $II_ext = strtolower(end($ex));
                        $avilable_extensions = array("png", "jpg", "jpeg", "gif");

                        if (! in_array($II_ext, $avilable_extensions)) {
                            array_push($errors, '"png", "jpg", "jpeg" and "gif" is the avilable extensions');
                        }

                        if ($II_size > 4194304) {
                            array_push($errors, 'This image is too large size must be smaller than 4MB');
                        }
                    }

                    if (empty($_POST["madeCountry"])) {
                        array_push($errors, "Item made country input can't be empty");
                    }

                    if (empty($_POST["itemStatus"])) {
                        array_push($errors, "Check the item status");
                    } elseif (!in_array($_POST["itemStatus"], ["new", "like new", "used", "old"])) {
                        array_push($errors, "Check the item status (new, like new, used, old)");
                    }

                    $itemTags = strtolower(trim(str_replace(" ", "", $_POST["itemTags"]), ","));
                    if (! empty($itemTags)) {
                        if (! preg_match("/^[a-z,]+$/", $itemTags)) {
                            array_push($errors, "tags input accept only letters and coma");
                        }
                    }

                    if (empty($_POST["userId"])) {
                        array_push($errors, "Check the user");
                    } elseif (!isAlreadyInUse("users", "userId", $_POST["userId"])) {
                        array_push($errors, "The user checked not exist");
                    }

                    if (empty($_POST["catId"])) {
                        array_push($errors, "Check the category");
                    } elseif (!isAlreadyInUse("categories", "catId", $_POST["catId"])) {
                        array_push($errors, "The category checked not exist");
                    }

                    if (empty($errors)) {
                        $activatedUser = $con->prepare("SELECT userId FROM users WHERE userId = :id AND regStatus = 1 AND (type = 0 OR userId = :sessionId)");
                        $activatedUser->bindParam("id", $_POST["userId"]);
                        $activatedUser->bindParam("sessionId", $_SESSION["userId"]);
                        $activatedUser->execute();

                        if ($activatedUser->rowCount() > 0) {
                            if (isAllowADS($_POST["catId"])) {
                                // Upload item image
                                if (isset($II_tmp_name)) {
                                    // Delete his old image
                                    unlink(itemImgs . $_POST["oldItemImg"]);

                                    // Upload his new image
                                    $itemImgName = random_name(40) . "." . $II_ext;
                                    move_uploaded_file($II_tmp_name, itemImgs . $itemImgName);
                                } else {
                                    $itemImgName = $_POST["oldItemImg"];
                                }

                                // Upload secondary images
                                $oldSecondaryImgs = explode(",", filter_var($_POST["oldSecondaryImgs"], 513));
                                $secondaryImgs = $_FILES["secondaryImgs"];
                                if ($secondaryImgs['error'][0] == 0) {
                                    // Remove old secondary images
                                    for ($i = 0; $i < count($oldSecondaryImgs); $i++) {
                                        if (! empty($oldSecondaryImgs[$i])) {
                                            unlink(itemSecondaryImgs . $oldSecondaryImgs[$i]);
                                        } else {
                                            break;
                                        }
                                    }

                                    $secondaryImgsValue = array();
                                    $avilable_extensions = array("png", "jpg", "jpeg", "gif");
                                    for ($i = 0; $i < 4; $i += 1) {
                                        if (isset($secondaryImgs["name"][$i])) {
                                            $si_tmp_name = $secondaryImgs["tmp_name"][$i];
                                            $si_size = $secondaryImgs["size"][$i];
                                            $si_type = $secondaryImgs["type"][$i];
                                            $si_type_ex = explode("/", $si_type);
                                            $si_ext = strtolower(end($si_type_ex));
                                            if (in_array($si_ext, $avilable_extensions)) {
                                                if ($si_size < 4194304) {
                                                    $si_random_name = random_name(40) . "." . $si_ext;
                                                    move_uploaded_file($si_tmp_name, itemSecondaryImgs . $si_random_name);
                                                    array_push($secondaryImgsValue, $si_random_name);
                                                }
                                            }
                                        } else {
                                            array_push($secondaryImgsValue, null);
                                        }
                                    }
                                } else {
                                    $secondaryImgsValue = $oldSecondaryImgs;
                                    // Replace empty value to null for set it in db
                                    $secondaryImgsValue = array_map(function ($e) {
                                        if (empty($e)) {
                                            return null;
                                        } else {
                                            return $e;
                                        }
                                    }, $secondaryImgsValue);
                                }
                                $secondaryImgsValue = array_pad($secondaryImgsValue, 4, null);

                                $stmt = $con->prepare("UPDATE items SET itemName = :itemName, itemDescription = :itemDescription, itemPrice = :itemPrice, itemImg = :itemImg, si1 = :si1, si2 = :si2, si3 = :si3, si4 = :si4, madeCountry = :madeCountry, itemStatus = :itemStatus, itemTags = :itemTags, userId = :userId, catId = :catId WHERE itemId = :itemId");

                                $stmt->bindParam("itemName", $_POST["itemName"]);
                                $stmt->bindParam("itemDescription", $_POST["itemDescription"]);
                                $stmt->bindParam("itemPrice", $_POST["itemPrice"]);
                                $stmt->bindParam("itemImg", $itemImgName);
                                $stmt->bindParam("si1", $secondaryImgsValue[0]);
                                $stmt->bindParam("si2", $secondaryImgsValue[1]);
                                $stmt->bindParam("si3", $secondaryImgsValue[2]);
                                $stmt->bindParam("si4", $secondaryImgsValue[3]);
                                $stmt->bindParam("madeCountry", $_POST["madeCountry"]);
                                $stmt->bindParam("itemStatus", $_POST["itemStatus"]);
                                $stmt->bindParam("itemTags", $itemTags);
                                $stmt->bindParam("userId", $_POST["userId"]);
                                $stmt->bindParam("catId", $_POST["catId"]);
                                $stmt->bindParam("itemId", $_POST["itemId"]);
                                $stmt->execute();

                                // Insert a notification after edited an item by the admin for any user
                                $notification = "The <span class='focus'>" . $_POST["oldName"] . "</span> item is edited by admin";
                                generateNotification($notification, "not-normal", $_POST["userId"]);

                                redirect("<section class='container'><section class='success'>One item Edited successfully</section></section>", "items.php?block=details&itemId=" . $_POST["itemId"] . "", 3);
                            } else {
                                redirect("<section class='container'><section class='alert'>The category you selected is incorrect</section></section>", $_SERVER["HTTP_REFERER"], 3);
                            }
                        } else {
                            redirect("<section class='container'><section class='alert'>The item not edited, because the selected user is incorrect</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        }
                    } else {
                        echo "<section class='container'>";
                        foreach ($errors as $error) {
                            echo "<section class='alert'>" . $error . "</section>";
                        }
                        echo "</section>";
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }
                } else {
                    if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {
                        $itemId = intval($_GET["itemId"]);
                        
                        $stmt = getAllRecords("*", "items", "itemId = $itemId", "itemId");
                        
                        if ($stmt->rowCount() > 0) {
                            $item = $stmt->fetch();
                        ?>
                            <section class="get-info">
                                <section class="container">
                                    <form method="post" action="items.php?block=edit" enctype="multipart/form-data">
                                        <input type="hidden" value="<?php echo $item['itemId'] ?>" name="itemId">
                                        <h1>Edit item</h1>
                                        <section class="cont-inputs">
                                            <section class="left">
                                                <label class="form-label" for="itemName">item name</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemName" id="itemName" value="<?php echo $item['itemName'] ?>">
                                                    <input type="hidden" value="<?php echo $item['itemName'] ?>" name="oldName">
                                                </section>

                                                <label class="form-label" for="itemImg">item main image</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="itemImg" id="itemImg">
                                                    <input type="hidden" name="oldItemImg" value="<?php echo $item['itemImg'] ?>">
                                                </section>

                                                <label class="form-label" for="secondaryImgs">item secondary images (max=4)</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="secondaryImgs[]" multiple id="secondaryImgs">
                                                    <input type="hidden" name="oldSecondaryImgs" value="<?php echo $item['si1'] . ',' . $item['si2'] . ',' . $item['si3'] . "," . $item['si4'] ?>">
                                                </section>

                                                <section class="desc">
                                                    <label class="form-label" for="desc-item">description</label>
                                                    <textarea class="desc-item" id="desc-item" name="itemDescription"><?php echo $item['itemDescription'] ?></textarea>
                                                </section>
                                            </section>

                                            <section class="right">
                                                <label class="form-label" for="itemPrice">price in <?php echo $theCurrency ?></label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemPrice" id="itemPrice" placeholder='<?php echo $theCurrency ?>' value="<?php echo $item['itemPrice'] ?>">
                                                </section>

                                                <label class="form-label" for="madeCountry">made in</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="madeCountry" id="madeCountry" value="<?php echo $item['madeCountry'] ?>" placeholder='Country'>
                                                </section>

                                                <label class="form-label" for="itemStatus">status</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                                    <select name="itemStatus" id="itemStatus">
                                                        <option value="new" <?php echo $item['itemStatus'] == "new" ? "selected" : "" ?>>new</option>
                                                        <option value="like new" <?php echo $item['itemStatus'] == "like new" ? "selected" : "" ?>>like new</option>
                                                        <option value="used" <?php echo $item['itemStatus'] == "used" ? "selected" : "" ?>>used</option>
                                                        <option value="old" <?php echo $item['itemStatus'] == "old" ? "selected" : "" ?>>old</option>
                                                    </select>
                                                </section>

                                                <label class="form-label" for="itemTags">tags</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemTags" id="itemTags" value="<?php echo $item['itemTags'] ?>" placeholder='Enter tags seperate by coma ","' pattern="[a-z, ]+" title="Only accept letters, coma and spaces">
                                                </section>

                                                <label class="form-label" for="userId">membere</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                                    <select name="userId" id="userId">
                                                    <?php
                                                        $usersStmt = $con->prepare("SELECT userId, username FROM users WHERE regStatus = 1 AND (type = 0 OR userId = :sessionId)");
                                                        $usersStmt->bindParam("sessionId", $_SESSION["userId"]);
                                                        $usersStmt->execute();

                                                        while ($user = $usersStmt->fetch()) {
                                                            if ($item['userId'] == $user["userId"]) {
                                                                $selected = "selected";
                                                            } else {
                                                                $selected = "";
                                                            }
                                                            echo "<option value='" . $user['userId'] . "' $selected>" . $user['username'] . "</option>";
                                                        }
                                                    ?>
                                                    </select>
                                                </section>

                                                <label class="form-label" for="catId">categories</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                                    <select name="catId" id="catId">
                                                    <?php
                                                        $catsStmt = getAllRecords("catId, catName", "categories", "catParent = 0 AND allowADS = 1", "ordering");

                                                        while ($cat = $catsStmt->fetch()) {
                                                            if ($item['catId'] == $cat['catId']) {
                                                                $selected = "selected";
                                                            } else {
                                                                $selected = "";
                                                            }
                                                            echo "<option value='" . $cat['catId'] . "' $selected>" . $cat['catName'] . "</option>";

                                                            $subCats = getAllRecords("catId, catName", "categories", "catParent = " . $cat['catId'] . " AND allowADS = 1", "ordering");

                                                            while ($subCat = $subCats->fetch()) {
                                                                if ($item['catId'] == $subCat["catId"]) {
                                                                    $selected = "selected";
                                                                } else {
                                                                    $selected = "";
                                                                }
                                                                echo "<option value='" . $subCat['catId'] . "' $selected>--- " . $subCat['catName'] . "</option>";
                                                            }
                                                        }
                                                    ?>
                                                    </select>
                                                </section>

                                            </section>
                                        </section>
                                        <button type="submit" name="save" class="btn">save</button>
                                    </form>
                                </section>
                            </section>
                        <?php
                        } else {
                            redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "items.php", 3);
                        }
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "items.php", 3);
                    }
                }
            } elseif ($_GET["block"] == "delete") {

                if (isset($_GET["itemId"]) == is_numeric($_GET["itemId"])) {
                    $itemId = intval($_GET["itemId"]);

                    // Get item info before delete the item
                    $getItemInfo = $con->prepare("SELECT userId, itemName FROM items WHERE itemId = :itemId");
                    $getItemInfo->bindParam("itemId", $itemId);
                    $getItemInfo->execute();

                    // Delete the item
                    $stmt = $con->prepare("DELETE FROM items WHERE itemId = :itemId");
                    $stmt->bindParam("itemId", $itemId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {

                        // Insert notification if the item is deleted successfully
                        $itemInfo = $getItemInfo->fetch();
                        $userId = $itemInfo["userId"];
                        $itemName = $itemInfo["itemName"];
                        $notification = "The admin deleted the <span class='focus'>" . $itemName . "</span> item, because it is inappropriate.";
                        generateNotification($notification, "not-danger", $userId);
                        
                        if (isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"])) {
                            if (strpos($_SERVER["HTTP_REFERER"], "details")) {
                                $target = "items.php";
                            } else {
                                $target = $_SERVER["HTTP_REFERER"];
                            }
                        } else {
                            $target = "items.php";
                        }

                        redirect("<section class='container'><section class='success'>The item has \"ID = $itemId\" deleted successfully</section></section>", $target, 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "items.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "items.php", 3);
                }
            } elseif ($_GET["block"] == "accept") {
                if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {

                    $itemId = intval($_GET["itemId"]);
                    $stmt = $con->prepare("UPDATE items SET accept = 1 WHERE itemId = :itemId");
                    $stmt->bindParam("itemId", $itemId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {

                        $getItemInfo = $con->prepare("SELECT itemName, userId, isEditedPrevName FROM items WHERE itemId = :itemId");
                        $getItemInfo->bindParam("itemId", $itemId);
                        $getItemInfo->execute();
                        $itemInfo = $getItemInfo->fetch();

                        $uId = $itemInfo["userId"];
                        $itemName = $itemInfo["itemName"];
                        $isEditedPrevName = $itemInfo["isEditedPrevName"];

                        if (empty($isEditedPrevName)) {
                            $notification = "Your item <span class='focus'>" . $itemName . "</span> is accepted successfully";
                        } else {
                            $notification = "Item <span class='focus'>" . $isEditedPrevName . "</span> modifications accepted successfully";
                        }

                        generateNotification($notification, "not-success", $uId);


                        redirect("<section class='container'><section class='success'>The item has \"ID=$itemId\" activated successfully</section></section>", isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != "" ? $_SERVER["HTTP_REFERER"] : "items.php", 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "items.php", 3);
                    }

                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "items.php", 3);
                }
            }  else {
                header("location: items.php");
                exit();
            }
        } else {
            if (isset($_GET["only"]) && $_GET["only"] == "pending") {
                $cond = "WHERE accept = 0";
                $h1Text = "show pending items";
                $totalText = "Total pending items:";
            } else {
                $cond = "";
                $h1Text = "show all items";
                $totalText = "Total items:";
            }

            $stmt = $con->prepare("SELECT itemId, itemName, itemPrice, accept, username, catName FROM items JOIN users USING (userId) JOIN categories USING (catId) $cond ORDER BY itemId DESC");
            $stmt->execute();
        ?>
            <section class="items-cont">
                <section class="container">
                    <section class="head">
                        <h1><?php echo $h1Text ?></h1>
                        <section class="totalSec">
                            <span class="totalText"><?php echo $totalText ?></span> <span class="totalResult"><?php echo $stmt->rowCount() ?></span>
                        </section>
                    </section>
                    <table class="main-tb">
                        <thead>
                            <tr>
                                <th><a href="#">#id</a></th>
                                <th><a href="#">name</a></th>
                                <th><a href="#">price</a></th>
                                <th><a href="#">user</a></th>
                                <th><a href="#">category</a></th>
                                <th>operations</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            while ($row = $stmt->fetch()) {
                                echo "<tr>";
                                    echo "<td>" . $row["itemId"] . "</td>";
                                    echo "<td>" . $row["itemName"] . "</td>";
                                    echo "<td>" . $theCurrency . $row["itemPrice"] . "</td>";
                                    echo "<td>" . $row["username"] . "</td>";
                                    echo "<td>" . $row["catName"] . "</td>";
                                    echo "<td>";
                                        echo "<a href='items.php?block=details&itemId=" . $row["itemId"] . "' class='details'><i class='fa-solid fa-circle-info'></i>details</a>";
                                        if ($row["accept"] == 0) {
                                            echo " <a href='items.php?block=accept&itemId=" . $row["itemId"] . "' class='activate'><i class='fa-solid fa-check'></i>accept</a>";
                                        }
                                        echo " <a href='items.php?block=edit&itemId=" . $row["itemId"] . "' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a>";
                                        echo " <a href='items.php?block=delete&itemId=" . $row["itemId"] . "' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>";
                                    echo "</td>";
                                echo "</tr>";
                            }
                        
                        ?>
                        </tbody>
                    </table>
                    <a href="items.php?block=add" class="add"><i class="fa-solid fa-plus"></i>Add new item</a>
                </section>
            </section>
        <?php
        }
        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
        exit();
    }