<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        include "init.inc.php";
        $sessionId = intval($_SESSION["userfrontid"]); // Current user userId

        if (isActivatedUser($sessionId)) {

            if (isset($_GET["block"])) {
                if ($_GET["block"] == "create") {
                    if (isset($_POST["create"])) {

                        $discountName   = filter_var($_POST["discountName"], FILTER_SANITIZE_STRING);
                        $percent        = filter_var($_POST["percent"], FILTER_SANITIZE_STRING);
                        $endsIn         = str_ireplace("t", " ", $_POST["endsIn"]);

                        $errors = array();

                        if (empty($discountName)) {
                            array_push($errors, "Discount name can't be empty");
                        }

                        if (empty($percent)) {
                            array_push($errors, "Discount percent can't be empty");
                        } elseif ($percent > 100) {
                            array_push($errors, "Discount percent can't be greator than 100");
                        }

                        if (empty($endsIn)) {
                            $endsIn = null;
                        } elseif (! preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/", $endsIn)) {
                            array_push($errors, "Date format \"Ends in input\" is: \"YYYY-MM-DD HH:MM\"");
                        } elseif (date("Y-m-d H:i:s") >= $endsIn) {
                            array_push($errors, "Ends in date must be larger than current date");
                        }

                        if (empty($errors)) {

                            $inUse = $con->prepare("SELECT discountId FROM discounts WHERE discountName = :discountName AND userId = :userId");
                            $inUse->bindParam("discountName", $discountName);
                            $inUse->bindParam("userId", $sessionId);
                            $inUse->execute();

                            if ($inUse->rowCount() > 0) {
                                redirect("<section class='container'><section class='alert'>This discount name is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                            } else {
                                $create = $con->prepare("INSERT INTO discounts (discountName, percent, endsIn, userId) VALUES (:discountName, :percent, :endsIn, :userId)");
                                $create->bindParam("discountName", $discountName);
                                $create->bindParam("percent", $percent);
                                $create->bindParam("endsIn", $endsIn);
                                $create->bindParam("userId", $sessionId);
                                $create->execute();

                                if ($create) {
                                    redirect("<section class='container'><section class='success'>One discount created successfully</section></section>", "discounts.php", 3);
                                }
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
                        <section class="createDiscount">
                            <h1>Create new discount</h1>
                            <section class="container">
                                <form method="post" action="">
                                    <section class="cont-inputs">
                                        <section class="left">

                                            <label class="form-label" for="discountName">Discount name</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-spell-check"></i></section><input type="text" name="discountName" id="discountName">
                                            </section>
            
                                            <label class="form-label" for="endsIn">Ends in</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-calendar-days"></i></section><input type="datetime-local" name="endsIn" id="endsIn" placeholder="YYYY-MM-DD HH:MM">
                                            </section>

                                        </section>
            
                                        <section class="right">

                                            <label class="form-label" for="percent">Discount percent</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-percent"></i></section><input type="number" name="percent" id="percent">
                                            </section>
            
                                        </section>
                                    </section>
                                    <button type="submit" name="create" class="btn"><i class="fa-solid fa-plus"></i> create</button>
                                </form>
                            </section>
                        </section>
                    <?php
                    }
                } elseif ($_GET["block"] == "edit") {

                    if (isset($_GET["disId"]) && is_numeric($_GET["disId"])) {
                        $discountId = filter_var($_GET["disId"], FILTER_SANITIZE_NUMBER_INT);

                        $getDiscountInfo = getAllRecords("*", "discounts", "discountId = $discountId AND userId = $sessionId", "discountId");

                        // Check if this discount for this signed user
                        if ($getDiscountInfo->rowCount() > 0) {

                            if (isset($_POST["save"])) {

                                $discountName   = filter_var($_POST["discountName"], FILTER_SANITIZE_STRING);
                                $percent        = filter_var($_POST["percent"], FILTER_SANITIZE_STRING);
                                $endsIn         = str_ireplace("t", " ", $_POST["endsIn"]);
    
                                $errors = array();
    
                                if (empty($discountName)) {
                                    array_push($errors, "Discount name can't be empty");
                                }
    
                                if (empty($percent)) {
                                    array_push($errors, "Discount percent can't be empty");
                                } elseif ($percent > 100) {
                                    array_push($errors, "Discount percent can't be greator than 100");
                                }
                                
                                if (empty($endsIn)) {
                                    $endsIn = null;
                                } elseif (! preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/", $endsIn)) {
                                    array_push($errors, "Date format \"Ends in input\" is: \"YYYY-MM-DD HH:MM\"");
                                } elseif (date("Y-m-d H:i:s") >= $endsIn) {
                                    array_push($errors, "Ends in date must be larger than current date");
                                }
    
                                if (empty($errors)) {
    
                                    $inUse = $con->prepare("SELECT discountId FROM discounts WHERE discountName = :discountName AND discountId != :discountId AND userId = :userId");
                                    $inUse->bindParam("discountName", $discountName);
                                    $inUse->bindParam("discountId", $discountId);
                                    $inUse->bindParam("userId", $sessionId);
                                    $inUse->execute();
    
                                    if ($inUse->rowCount() > 0) {
                                        redirect("<section class='container'><section class='alert'>This discount name is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                                    } else {
                                        $edit = $con->prepare("UPDATE discounts SET discountName = :discountName, percent = :percent, endsIn = :endsIn WHERE discountId = :discountId");
                                        $edit->bindParam("discountName", $discountName);
                                        $edit->bindParam("percent", $percent);
                                        $edit->bindParam("endsIn", $endsIn);
                                        $edit->bindParam("discountId", $discountId);
                                        $edit->execute();
    
                                        if ($edit) {
                                            redirect("<section class='container'><section class='success'>One discount edited successfully</section></section>", "discounts.php", 3);
                                        }
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

                                $discountInfo = $getDiscountInfo->fetch();
                                ?>
                                <section class="editDiscount">
                                    <h1>Edit discount</h1>
                                    <section class="container">
                                        <form method="post" action="discounts.php?block=edit&disId=<?php echo $discountInfo["discountId"] ?>">
                                            <section class="cont-inputs">
                                                <section class="left">
    
                                                    <label class="form-label" for="discountName">Discount name</label>
                                                    <section class="form-group">
                                                        <section class="icon"><i class="fa-solid fa-spell-check"></i></section><input type="text" name="discountName" id="discountName" value="<?php echo $discountInfo["discountName"] ?>">
                                                    </section>
                    
                                                    <label class="form-label" for="endsIn">Ends in</label>
                                                    <section class="form-group">
                                                        <section class="icon"><i class="fa-solid fa-calendar-days"></i></section><input type="datetime-local" name="endsIn" id="endsIn" placeholder="YYYY-MM-DD HH:MM" value="<?php echo str_ireplace(" ",  "T", $discountInfo["endsIn"]) ?>">
                                                    </section>
    
                                                </section>
                    
                                                <section class="right">
    
                                                    <label class="form-label" for="percent">Discount percent</label>
                                                    <section class="form-group">
                                                        <section class="icon"><i class="fa-solid fa-percent"></i></section><input type="number" name="percent" id="percent" value="<?php echo $discountInfo["percent"] ?>">
                                                    </section>
                    
                                                </section>
                                            </section>
                                            <button type="submit" name="save" class="btn"><i class="fa-solid fa-floppy-disk"></i> save</button>
                                        </form>
                                    </section>
                                </section>
                                <?php
                            }
    
                        } else {
                            redirect("<section class='container'><section class='alert'>There is no match or this discount is not for you</section></section>", "discounts.php", 3);
                        }
                        
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "discounts.php", 3);
                    }

                } elseif ($_GET["block"] == "delete") {

                    if (isset($_GET["disId"]) && is_numeric($_GET["disId"])) {
                        $discountId = intval($_GET["disId"]);
    
                        $stmt = $con->prepare("DELETE FROM discounts WHERE discountId = :discountId AND userId = :userId");
                        $stmt->bindParam("discountId", $discountId);
                        $stmt->bindParam("userId", $sessionId);
                        $stmt->execute();
    
                        if ($stmt->rowCount() > 0) {
                            redirect("<section class='container'><section class='success'>One discount deleted successfully</section></section>", "discounts.php", 3);
                        } else {
                            redirect("<section class='container'><section class='worning'>there is no match</section></section>", "discounts.php", 3);
                        }
    
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "discounts.php", 3);
                    }

                } else {
                    header("location: discounts.php");
                }

            } else {
                $discounts = getAllRecords("*", "discounts", "userId = $sessionId", "percent DESC");

                echo '
                <section class="discounts">
                    <h1>Shaw all discounts</h1>
                    <section class="container">
                ';

                if ($discounts->rowCount() > 0) {
                ?>
                            <table class="main-tb">
                                <thead>
                                    <tr>
                                        <th><i class="fa-solid fa-spell-check"></i>name</th>
                                        <th><i class="fa-solid fa-percent"></i>percent</th>
                                        <th><i class="fa-solid fa-calendar-days"></i>created at</th>
                                        <th><i class="fa-solid fa-calendar-days"></i>ends in</th>
                                        <th><i class="fa-solid fa-gear"></i>operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($discount = $discounts->fetch()) {

                                        if (is_null($discount["endsIn"])) {
                                            $endsIn = "Unknown";
                                        } else {
                                            $endsIn = myDate($discount["endsIn"]);
                                        }

                                        echo "
                                            <tr>
                                                <td>" . $discount["discountName"] . "</td>
                                                <td>" . $discount["percent"] . "%</td>
                                                <td>" . myDate($discount["createdAt"]) . "</td>
                                                <td>" . $endsIn . "</td>
                                                <td>
                                                    <a href='discounts.php?block=edit&disId=" . $discount["discountId"] . "' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a> 
                                                    <a href='discounts.php?block=delete&disId=" . $discount["discountId"] . "' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>
                                                </td>
                                            </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                <?php
                } else {
                    echo "<section class='worning'>There is no discounts to show</section>";
                }

                echo "
                            <a href='discounts.php?block=create' class='add'><i class='fa-solid fa-plus'></i> Create new discount</a>
                        </section>
                    </section>
                ";
            }

        } else {
            echo "<section class='container'><section class='worning'>Your account is not activated yet</section></section>";
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
        exit();
    }