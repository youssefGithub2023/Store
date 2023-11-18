<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_GET["itemId"]) && is_numeric($_GET["itemId"])) {
            $itemId = intval($_GET["itemId"]);
            $current_userId = intval($_SESSION["userfrontid"]);

            $getItem = getAllRecords("*", "items", "itemId = $itemId AND userId = $current_userId", "itemId");
            
            if ($getItem->rowCount() > 0) {

                if (isset($_POST["save"])) {
                    // Phase (1): Filter
                    $itemName = filter_var($_POST["itemName"], 513);
                    $isEditedPrevName = filter_var($_POST["isEditedPrevName"], 513);
                    $itemDescription = filter_var($_POST["itemDescription"], 513);
                    $itemPrice = filter_var($_POST["itemPrice"], FILTER_SANITIZE_NUMBER_INT);
                    $madeCountry = filter_var($_POST["madeCountry"], 513);
                    $itemStatus = filter_var($_POST["itemStatus"], 513);
                    $itemQuantity = filter_var($_POST["itemQuantity"], FILTER_SANITIZE_NUMBER_INT);
                    $catId = filter_var($_POST["catId"], FILTER_SANITIZE_NUMBER_INT);
                    $discountId = filter_var($_POST["discountId"], FILTER_SANITIZE_NUMBER_INT);
                    $itemTags = strtolower(trim(preg_replace("/(,)?(discount)(s)?(,)?/i", ",", str_replace(" ", "", filter_var($_POST["itemTags"], 513))), " ,"));
        
                    // Phase (2): Validate
                    $errors = array();
        
                    if (empty($itemName)) {
                        array_push($errors, "Item name input can't be empty");
                    }

                    if (empty($isEditedPrevName)) {
                        array_push($errors, "Item isEditedPrevName input can't be empty");
                    }
        
                    if (empty($itemDescription)) {
                        array_push($errors, "Item description input can't be empty");
                    }
        
                    if (empty($itemPrice)) {
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
        
                    if (empty($madeCountry)) {
                        array_push($errors, "Item made country input can't be empty");
                    }
        
                    if (empty($itemStatus)) {
                        array_push($errors, "Check the item status");
                    } elseif (!in_array($itemStatus, ["new", "like new", "used", "old"])) {
                        array_push($errors, "Check the item status from (new, like new, used, old)");
                    }

                    if (empty($itemQuantity)) {
                        array_push($errors, "Item quantity input can't be empty");
                    } elseif ($itemQuantity < 1) {
                        array_push($errors, "The quantity cant be less than 1");
                    }
        
                    if (! empty($itemTags)) {
                        if (! preg_match("/^[a-z,]+$/", $itemTags)) {
                            array_push($errors, "tags input accept only letters and coma");
                        }
                    }
        
                    if (empty($catId)) {
                        array_push($errors, "Check the category");
                    } elseif (!isAlreadyInUse("categories", "catId", $catId)) {
                        array_push($errors, "The category checked not exist");
                    }
                    // Phase (3): Send to database

                    if ($discountId == "0") {
                        $discountId = null;
                    } else {
                        // Check if this discount is for me, if is not for me set "NULL" in discountId filed in items table
                        $discountForMe = getAllRecords("discountId", "discounts", "discountId = $discountId AND userId = $userId", "discountId");
                        
                        if ($discountForMe->rowCount() == 0) {
                            $discountId = null;
                        }
                    }
        
                    if (empty($errors)) {
                        if (isAllowADS($catId)) {
                            // Upload item image
                            $oldItemImg = filter_var($_POST["oldItemImg"], 513);
                            if (isset($II_tmp_name)) {
                                // Delete the old image
                                unlink(itemImgs . $oldItemImg);
            
                                // Upload the new image
                                $itemImgName = random_name(40) . "." . $II_ext;
                                move_uploaded_file($II_tmp_name, itemImgs . $itemImgName);
                            } else {
                                $itemImgName = $oldItemImg;
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

            
                            $stmt = $con->prepare("UPDATE items SET itemName = :itemName, itemDescription = :itemDescription, itemPrice = :itemPrice, itemImg = :itemImg, si1 = :si1, si2 = :si2, si3 = :si3, si4 = :si4, discountId = :discountId, madeCountry = :madeCountry, itemStatus = :itemStatus, itemQuantity = :itemQuantity, itemTags = :itemTags, catId = :catId WHERE itemId = :itemId AND userId = :userId");
            
                            $stmt->bindParam("itemName", $itemName);
                            $stmt->bindParam("itemDescription", $itemDescription);
                            $stmt->bindParam("itemPrice", $itemPrice);
                            $stmt->bindParam("itemImg", $itemImgName);
                            $stmt->bindParam("si1", $secondaryImgsValue[0]);
                            $stmt->bindParam("si2", $secondaryImgsValue[1]);
                            $stmt->bindParam("si3", $secondaryImgsValue[2]);
                            $stmt->bindParam("si4", $secondaryImgsValue[3]);
                            $stmt->bindParam("discountId", $discountId);
                            $stmt->bindParam("madeCountry", $madeCountry);
                            $stmt->bindParam("itemStatus", $itemStatus);
                            $stmt->bindParam("itemQuantity", $itemQuantity);
                            $stmt->bindParam("itemTags", $itemTags);
                            $stmt->bindParam("catId", $catId);
                            $stmt->bindParam("itemId", $itemId);
                            $stmt->bindParam("userId", $current_userId);
                            $stmt->execute();
            
                            if ($stmt) {
                                if ($stmt->rowCount() > 0) {
                                    $stmt2 = $con->prepare("UPDATE items SET accept = 0, isEditedPrevName = :isEditedPrevName WHERE itemId = :itemId AND userId = :userId");
                                    $stmt2->bindParam("isEditedPrevName", $isEditedPrevName);
                                    $stmt2->bindParam("itemId", $itemId);
                                    $stmt2->bindParam("userId", $current_userId);
                                    $stmt2->execute();
                                }

                                redirect("<section class='container'><section class='success'>One item edited successfully</section></section>", "itemDetails.php?itemId=" . $itemId . "&referer=profile", 3);
                            }
                        } else {
                            redirect("<section class='container'><section class='alert'>The item not added, because the selected user not activated</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        }
                    } else {
                        echo "<section class='container'>";
                        foreach($errors as $error) {
                            echo "<section class='alert'>" . $error . "</section>";
                        }
                        echo "</section>";
        
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }
        
                } else {
                    // Fetch item data
                    $item = $getItem->fetch();
                ?>
                    <section class="editItem">
                        <h1>edit item</h1>
                        <section class="container">
                            <form method="post" action="?itemId=<?php echo $item["itemId"] ?>" enctype="multipart/form-data">
                                <section class="cont-inputs">
                                    <section class="left">
                                        <label class="form-label" for="itemName">item name</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemName" id="itemName" value="<?php echo $item['itemName'] ?>">
                                            <input type="hidden" name="isEditedPrevName" value="<?php echo $item['itemName'] ?>">
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

                                        <label class="form-label" for="itemPrice">price in <?php echo $theCurrency ?></label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemPrice" id="itemPrice" placeholder='<?php echo $theCurrency ?>' value="<?php echo $item['itemPrice'] ?>">
                                        </section>
        
                                        <section class="desc">
                                            <label class="form-label" for="desc-item">description</label>
                                            <textarea class="desc-item" id="desc-item" name="itemDescription"><?php echo $item['itemDescription'] ?></textarea>
                                        </section>
                                    </section>
        
                                    <section class="right">
                                        
                                        <label class="form-label" for="madeCountry">made in</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="madeCountry" id="madeCountry" placeholder='Country' value="<?php echo $item['madeCountry'] ?>">
                                        </section>
        
                                        <label class="form-label" for="itemStatus">status</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="itemStatus" id="itemStatus">
                                                <option value="new" <?php echo $item["itemStatus"] == "new" ? "selected" : "" ?>>new</option>
                                                <option value="like new" <?php echo $item["itemStatus"] == "like new" ? "selected" : "" ?>>like new</option>
                                                <option value="used" <?php echo $item["itemStatus"] == "used" ? "selected" : "" ?>>used</option>
                                                <option value="old" <?php echo $item["itemStatus"] == "old" ? "selected" : "" ?>>old</option>
                                            </select>
                                        </section>

                                        <label class="form-label" for="itemQuantity">quantity</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemQuantity" id="itemQuantity" value="<?php echo $item["itemQuantity"] ?>">
                                        </section>
        
                                        <label class="form-label" for="catId">categories</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="catId" id="catId">
                                            <?php
                                                $categories = getAllRecords("catId, catName", "categories", "catParent = 0 AND allowADS = 1", "ordering");
        
                                                while ($cat = $categories->fetch()) {
                                                    if ($item["catId"] == $cat['catId']) {
                                                        $selected = "selected";
                                                    } else {
                                                        $selected = "";
                                                    }
        
                                                    echo "<option value='" . $cat['catId'] . "' $selected>" . $cat['catName'] . "</option>";
        
                                                    $subCats = getAllRecords("catId, catName", "categories", "catParent = {$cat['catId']} AND allowADS = 1", "ordering");
        
                                                    while ($subCat = $subCats->fetch()) {
                                                        if ($item["catId"] == $subCat['catId']) {
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

                                        <label class="form-label" for="discountId">discount</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                            <select name="discountId" id="discountId">
                                                <option value="0">None</option>
                                                <?php
                                                $getMyDiscounts = getAllRecords("discountId, discountName, percent", "discounts", "userId = $userId", "percent DESC");
                                                
                                                if ($getMyDiscounts->rowCount() > 0) {
                                                    while ($myDiscount = $getMyDiscounts->fetch()) {
                                                        if ($item["discountId"] == $myDiscount["discountId"]) {
                                                            $selectedDis = "selected";
                                                        } else {
                                                            $selectedDis = "";
                                                        }

                                                        echo "<option value='" . $myDiscount["discountId"] . "' $selectedDis>" . $myDiscount["discountName"] . " (" . $myDiscount["percent"] . "%)" . "</option>";
                                                    }
                                                } else {
                                                    echo '<option value="0" disabled>You dont have any discounts</option>';
                                                }
                                                ?>
                                            </select>
                                        </section>

                                        <label class="form-label" for="itemTags">tags</label>
                                        <section class="form-group">
                                            <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemTags" id="itemTags" placeholder='Enter tags seperate by coma ","' pattern="[a-z, ]+" title="Only accept letters, coma and spaces" value="<?php echo $item['itemTags'] ?>">
                                        </section>
        
                                    </section>
                                </section>
                                <button type="submit" name="save" class="btn">save</button>
                            </form>
                        </section>
                    </section>
                <?php
                }
            } else {
                redirect("<section class='container'><section class='alert'>This item is not for you</section></section>", "profile.php", 3);
            }
        } else {
            redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "index.php", 3);
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }