<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $userId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        if (isActivatedUser($userId)) {
            if (isset($_POST["add"])) {
                // Phase (1): Filter
                $itemName = filter_var($_POST["itemName"], 513);
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
    
                if (empty($itemDescription)) {
                    array_push($errors, "Item description input can't be empty");
                }
    
                if (empty($itemPrice)) {
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
                        $secondaryImgsValue = array_pad($secondaryImgsValue, 4, null);
                        // End item secondary images
        
                        $stmt = $con->prepare("INSERT INTO items(itemName, itemDescription, itemPrice, itemImg, si1, si2, si3, si4, discountId, madeCountry, itemStatus, itemQuantity, itemTags, userId, catId) VALUES (:itemName, :itemDescription, :itemPrice, :itemImg, :si1, :si2, :si3, :si4, :discountId, :madeCountry, :itemStatus, :itemQuantity, :itemTags, :userId, :catId)");
        
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
                        $stmt->bindParam("userId", $userId);
                        $stmt->bindParam("catId", $catId);
                        $stmt->execute();
        
                        if ($stmt) {
                            redirect("<section class='container'><section class='success'>One item added successfully</section></section>", "profile.php", 3);
                        }
                    } else {
                        redirect("<section class='container'><section class='alert'>The category you selected is incorrect</section></section>", $_SERVER["HTTP_REFERER"], 3);
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
            ?>
                <section class="addItem">
                    <h1>Add new item</h1>
                    <section class="container">
                        <form method="post" action="" enctype="multipart/form-data">
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

                                    <label class="form-label" for="itemPrice">price in <?php echo $theCurrency ?></label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemPrice" id="itemPrice" placeholder='<?php echo $theCurrency ?>'>
                                    </section>
    
                                    <section class="desc">
                                        <label class="form-label" for="desc-item">description</label>
                                        <textarea class="desc-item" id="desc-item" name="itemDescription"></textarea>
                                    </section>
                                </section>
    
                                <section class="right">
    
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

                                    <label class="form-label" for="itemQuantity">quantity</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="number" name="itemQuantity" id="itemQuantity" value="1">
                                    </section>
    
                                    <label class="form-label" for="catId">categories</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                        <select name="catId" id="catId">
                                            <option value="">...check the category</option>
                                        <?php
                                            $categories = getAllRecords("catId, catName", "categories", "catParent = 0 AND allowADS = 1", "ordering");
    
                                            while ($cat = $categories->fetch()) {
                                                echo "<option value='" . $cat['catId'] . "'>" . $cat['catName'] . "</option>";
    
                                                $subCats = getAllRecords("catId, catName", "categories", "catParent = {$cat['catId']} AND allowADS = 1", "ordering");
    
                                                while ($subCat = $subCats->fetch()) {
                                                    echo "<option value='" . $subCat['catId'] . "'>--- " . $subCat['catName'] . "</option>";
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
                                                    echo "<option value='" . $myDiscount["discountId"] . "'>" . $myDiscount["discountName"] . " (" . $myDiscount["percent"] . "%)" . "</option>";
                                                }
                                            } else {
                                                echo '<option value="0" disabled>You dont have any discounts</option>';
                                            }
                                            ?>
                                        </select>
                                    </section>

                                    <label class="form-label" for="itemTags">tags</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="itemTags" id="itemTags" placeholder='Enter tags seperate by coma ","' pattern="[a-z, ]+" title="Only accept letters, coma and spaces">
                                    </section>
    
                                </section>
                            </section>
                            <button type="submit" name="add" class="btn">add</button>
                        </form>
                    </section>
                </section>
            <?php
            }
        } else {
            echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }