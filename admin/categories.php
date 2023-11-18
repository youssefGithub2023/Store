<?php
    session_start();
    if (isset($_SESSION["username"])) {
        $titleKey = "categories";
        include "init.inc.php";
        if (isset($_GET["block"])) {
            if ($_GET["block"] == "add") {
                if (isset($_POST["add"])) {
                    $errors = array();

                    if (empty($_POST["catName"])) {
                        array_push($errors, "category name input cant be empty");
                    }

                    if (empty($_POST["catDescription"])) {
                        array_push($errors, "category description input cant be empty");
                    }
                    
                    if (empty($errors)) {
                        
                        if (isAlreadyInUse("categories", "catName", $_POST["catName"])) {
                            redirect("<section class='container'><section class='alert'>category name \"" . $_POST["catName"] . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        } else {
                            $stmt = $con->prepare("INSERT INTO categories(catName, catDescription, catParent, ordering, visibility, allowCommenting, allowADS) VALUES (:catName, :catDescription, :catParent, :ordering, :visibility, :allowCommenting, :allowADS)");
                            $stmt->bindParam("catName", $_POST["catName"]);
                            $stmt->bindParam("catDescription", $_POST["catDescription"]);
                            $stmt->bindParam("catParent", $_POST["catParent"]);
                            $stmt->bindParam("ordering", $_POST["ordering"]);
                            $stmt->bindParam("visibility", $_POST["visibility"]);
                            $stmt->bindParam("allowCommenting", $_POST["allowCommenting"]);
                            $stmt->bindParam("allowADS", $_POST["allowADS"]);
                            $stmt->execute();

                            redirect("<section class='container'><section class='success'>One category added successfully</section></section>", "categories.php", 3);
                        }
                    } else {
                        echo "<section class='container'>";
                        foreach ($errors as $error) {
                            echo "<section class='alert'>$error</section>";
                        }
                        echo "</section>";
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }
                } else {
            ?>
                <section class="get-info">
                    <section class="container">
                        <form method="post" action="categories.php?block=add">
                            <h1>Add new categories</h1>
                            <section class="cont-inputs">
                                <section class="left">
                                    <label class="form-label" for="catName">category name</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="catName" id="catName">
                                    </section>

                                    <section class="desc">
                                        <label class="form-label" for="desc">description</label>
                                        <textarea class="desc-cat" id="desc" name="catDescription"></textarea>
                                    </section>
                                </section>

                                <section class="right">
                                    <label class="form-label" for="catParent">parent category</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                        <select name="catParent" id="catParent">
                                            <option value="0">none</option>
                                            <?php
                                                $getCats = $con->prepare("SELECT catId, catName FROM categories WHERE catParent = 0 ORDER BY ordering");
                                                $getCats->execute();

                                                while ($cat = $getCats->fetch()) {
                                                    echo "<option value='" . $cat['catId'] . "'>" . $cat['catName'] . "</option>";
                                                }
                                            ?>
                                        </select>
                                    </section>

                                    <label class="form-label" for="ordering">category order</label>
                                    <section class="form-group">
                                        <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="ordering" id="ordering">
                                    </section>

                                    <section class="cont-radio">
                                        <span>visible</span>
                                        <section>
                                            <input type="radio" name="visibility" value="1" id="vis-yes" checked><label for="vis-yes">yes</label>
                                        </section>
                                        <section>
                                            <input type="radio" name="visibility" value="0" id="vis-no"><label for="vis-no">no</label>
                                        </section>
                                    </section>

                                    <section class="cont-radio">
                                        <span>allow commenting</span>
                                        <section>
                                            <input type="radio" name="allowCommenting" value="1" id="com-yes" checked><label for="com-yes">yes</label>
                                        </section>
                                        <section>
                                            <input type="radio" name="allowCommenting" value="0" id="com-no"><label for="com-no">no</label>
                                        </section>
                                    </section>

                                    <section class="cont-radio">
                                        <span>allow ads</span>
                                        <section>
                                            <input type="radio" name="allowADS" value="1" id="ads-yes" checked><label for="ads-yes">yes</label>
                                        </section>
                                        <section>
                                            <input type="radio" name="allowADS" value="0" id="ads-no"><label for="ads-no">no</label>
                                        </section>
                                    </section>
                                </section>
                            </section>
                            <button type="submit" name="add" class="btn">add</button>
                        </form>
                    </section>
                </section>
            <?php
                }
            } elseif ($_GET["block"] == "edit") {
                if (isset($_POST["save"])) {
                    $errors = array();

                    if (empty($_POST["catName"])) {
                        array_push($errors, "category name input cant be empty");
                    }

                    if (empty($_POST["catDescription"])) {
                        array_push($errors, "category description input cant be empty");
                    }
                    
                    if (empty($errors)) {
                            $inUse = $con->prepare("SELECT catId FROM categories WHERE catName = :catName && catId != :catId");
                            $inUse->bindParam("catName", $_POST["catName"]);
                            $inUse->bindParam("catId", $_POST["catId"]);
                            $inUse->execute();
                            
                            if ($inUse->rowCount() > 0) {
                                redirect("<section class='container'><section class='alert'>category name \"" . $_POST["catName"] . "\" is already in use</section></section>", linkWithoutEdited(), 3);
                            } else {
                                $stmt = $con->prepare("UPDATE categories SET catName = :catName, catDescription = :catDescription, catParent = :catParent, ordering = :ordering, visibility = :visibility, allowCommenting = :allowCommenting, allowADS = :allowADS WHERE catId = :catId");
                                $stmt->bindParam("catName", $_POST["catName"]);
                                $stmt->bindParam("catDescription", $_POST["catDescription"]);
                                $stmt->bindParam("catParent", $_POST["catParent"]);
                                $stmt->bindParam("ordering", $_POST["ordering"]);
                                $stmt->bindParam("visibility", $_POST["visibility"]);
                                $stmt->bindParam("allowCommenting", $_POST["allowCommenting"]);
                                $stmt->bindParam("allowADS", $_POST["allowADS"]);
                                $stmt->bindParam("catId", $_POST["catId"]);
                                $stmt->execute();

                                if (strpos($_SERVER["HTTP_REFERER"], "edited")) {
                                    $link = $_SERVER["HTTP_REFERER"];
                                } else {
                                    $link = $_SERVER["HTTP_REFERER"] . "&edited";
                                }
                                header("location: " . $link);
                                exit();
                            }
                    } else {
                        echo "<section class='container'>";
                        foreach ($errors as $error) {
                            echo "<section class='alert'>$error</section>";
                        }
                        echo "</section>";
                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }

                } else {
                    if (isset($_GET["catId"]) && is_numeric($_GET["catId"])) {
                        $catId = intval($_GET["catId"]);
                        $stmt = $con->prepare("SELECT * FROM categories WHERE catId = :catId");
                        $stmt->bindParam("catId", $catId);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            $category = $stmt->fetch();
            ?>
                            <section class="get-info">
                                <section class="container">
                                    <form method="post" action="categories.php?block=edit">
                                        <input type="hidden" name="catId" value="<?php echo $category['catId'] ?>">
                                        <h1>Edit categories</h1>
                                        <?PHP
                                            if (isset($_GET["edited"])) {
                                                echo "<section class='success'>Edited successfully</section>";
                                            }
                                        ?>
                                        <section class="cont-inputs">
                                            <section class="left">
                                                <label class="form-label" for="catName">category name</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="catName" id="catName" value="<?php echo $category["catName"] ?>">
                                                </section>

                                                <section class="desc">
                                                    <label class="form-label" for="desc">description</label>
                                                    <textarea class="desc-cat" id="desc" name="catDescription"><?php echo $category["catDescription"] ?></textarea>
                                                </section>
                                            </section>

                                            <section class="right">
                                                <label class="form-label" for="catParent">parent category</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section>
                                                    <select name="catParent" id="catParent">
                                                        <option value="0">none</option>
                                                        <?php
                                                            $getCats = $con->prepare("SELECT catId, catName FROM categories WHERE catParent = 0 ORDER BY ordering");
                                                            $getCats->execute();

                                                            while ($cat = $getCats->fetch()) {
                                                                if ($cat['catId'] == $category["catParent"]) {
                                                                    $selected = "selected";
                                                                } else {
                                                                    $selected = "";
                                                                }
                                                                echo "<option value='" . $cat['catId'] . "' $selected>" . $cat['catName'] . "</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </section>

                                                <label class="form-label" for="ordering">category order</label>
                                                <section class="form-group">
                                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="ordering" id="ordering" value="<?php echo $category["ordering"] ?>">
                                                </section>

                                                <section class="cont-radio">
                                                    <span>visible</span>
                                                    <section>
                                                        <input type="radio" name="visibility" value="1" id="vis-yes" <?php echo $category["visibility"] == 1 ? "checked" : "" ?>><label for="vis-yes">yes</label>
                                                    </section>
                                                    <section>
                                                        <input type="radio" name="visibility" value="0" id="vis-no" <?php echo $category["visibility"] == 0 ? "checked" : "" ?>><label for="vis-no">no</label>
                                                    </section>
                                                </section>

                                                <section class="cont-radio">
                                                    <span>allow commenting</span>
                                                    <section>
                                                        <input type="radio" name="allowCommenting" value="1" id="com-yes" <?php echo $category["allowCommenting"] == 1 ? "checked" : "" ?>><label for="com-yes">yes</label>
                                                    </section>
                                                    <section>
                                                        <input type="radio" name="allowCommenting" value="0" id="com-no" <?php echo $category["allowCommenting"] == 0 ? "checked" : "" ?>><label for="com-no">no</label>
                                                    </section>
                                                </section>

                                                <section class="cont-radio">
                                                    <span>allow ads</span>
                                                    <section>
                                                        <input type="radio" name="allowADS" value="1" id="ads-yes" <?php echo $category["allowADS"] == 1 ? "checked" : "" ?>><label for="ads-yes">yes</label>
                                                    </section>
                                                    <section>
                                                        <input type="radio" name="allowADS" value="0" id="ads-no" <?php echo $category["allowADS"] == 0 ? "checked" : "" ?>><label for="ads-no">no</label>
                                                    </section>
                                                </section>
                                            </section>
                                        </section>
                                        <button type="submit" name="save" class="btn">save</button>
                                    </form>
                                </section>
                            </section>
            <?php
                        } else {
                            redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "categories.php", 3);
                        }
                    
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "categories.php", 3);
                    }
                }
            } elseif ($_GET["block"] == "delete") {
                if (isset($_GET["catId"]) == is_numeric($_GET["catId"])) {
                    $catId = intval($_GET["catId"]);

                    $stmt = $con->prepare("DELETE FROM categories WHERE catId = :catId");
                    $stmt->bindParam("catId", $catId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        
                        $deleteSubCat = $con->prepare("DELETE FROM categories WHERE catParent = :catId");
                        $deleteSubCat->bindParam("catId", $catId);
                        $deleteSubCat->execute();

                        if ($deleteSubCat->rowCount() > 0) {
                            $deleteChilds = "and " . $deleteSubCat->rowCount() . " of his children";
                        } else {
                            $deleteChilds = "";
                        }

                        redirect("<section class='container'><section class='success'>The category has \"ID = $catId\" deleted successfully $deleteChilds</section></section>", isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "categories.php", 3);

                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "categories.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "categories.php", 3);
                }
            } else {
                header("location: categories.php");
                exit();
            }
        } else {
            if (isset($_GET['sort']) && $_GET['sort'] == "desc") {
                $sort = "DESC";
            } else {
                $sort = "ASC";
            }

            $totalText = "Total categories:";

            if (isset($_GET["search-btn"]) && !empty($_GET["search"])) {
                $like = "%" . $_GET["search"] . "%";
                $inputeVal = $_GET["search"];
                $totalText = "Total searched categories:";
            } else {
                $like = "%";
                $inputeVal = "";
            }

            if (isset($_GET["only"]) && is_numeric($_GET["only"])) {
                $only = intval($_GET["only"]);
                $cond = "&& catId = :catId";
            } else {
                $cond = "";
            }

            $stmt = $con->prepare("SELECT * FROM categories WHERE catParent = 0 AND catName LIKE :searchText $cond ORDER BY ordering $sort");
            $stmt->bindParam("searchText", $like);
            if (isset($only)) {
                $stmt->bindParam("catId", $only);
            }
            $stmt->execute();
            ?>
            <section class="categories">
                <section class="container cat-flex">
                    <h1>Show all getegories</h1>
                    <section class="sortAndSearch <?php echo isset($only) ? 'hide' : '' ?>">
                        <section class="totalSec">
                            <span class="totalText"><?php echo $totalText ?></span> <span class="totalResult"><?php echo $stmt->rowCount() ?></span>
                        </section>

                        <form method="get" action="categories.php">
                            <input type="text" placeholder="search" name="search" value="<?php echo $inputeVal ?>"><button type="submit" name="search-btn" value="search"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>

                        <a href="<?php echo $sort == "DESC" ? slice($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "sort") - 1) : addGetBy($_SERVER['REQUEST_URI'], 'sort=desc') ?>" class="sort"><?php echo $sort == "DESC" ? '<i class="fa-solid  fa-arrow-down-1-9"></i> ASC' : '<i class="fa-solid fa-arrow-down-9-1"></i> DESC' ?></a>
                    </section>
                </section>
                <section class="container cat-flex <?php echo isset($only) ? 'center' : '' ?>">
                    <?php
                    while ($row = $stmt->fetch()) {
                    ?>
                        <section class="category">
                            <h2><?php echo $row["catName"] ?></h2>
                            <p>
                                <?php echo $row["catDescription"] ?>
                            </p>
                            <section class="cat-foot">
                                <section class="properties">
                                    <span>
                                        <i class="<?php echo $row['visibility'] == 0 ? 'fa-solid fa-eye-slash danger' : 'fa-solid fa-eye' ?>"></i>
                                    </span>
                                    <span>
                                        <i class="<?php echo $row['allowCommenting'] == 0 ? 'fa-solid fa-comment-slash danger' : 'fa-solid fa-comment' ?>"></i>
                                    </span>
                                    <span>
                                        <i class="<?php echo $row['allowADS'] == 0 ? 'fa-solid fa-slash danger' : 'fa-solid fa-audio-description' ?>"></i>
                                    </span>
                                </section>
                                <section class="operations">
                                    <a href='categories.php?block=edit&catId=<?php echo $row["catId"] ?>' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a>
                                    <a href='categories.php?block=delete&catId=<?php echo $row["catId"] ?>' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>
                                </section>
                            </section>
                            
                                    <?php
                                        $subCats = $con->prepare("SELECT catId, catName FROM categories WHERE catParent = {$row['catId']} ORDER BY ordering");
                                        $subCats->execute();

                                        if ($subCats->rowCount() > 0) {
                                            echo '
                                            <section class="sub-categories">
                                                <h3>subcategories</h3>
                                                <ul>
                                            ';
                                            while ($subCat = $subCats->fetch()) {
                                                echo "<li>
                                                        <span class='subCat-name'>" . $subCat["catName"] . "</span>
                                                        <span class='subCat-ops'>
                                                            <a href='categories.php?block=edit&catId=" . $subCat["catId"]."' class='edit'><i class='fa-solid fa-pen-to-square'></i>edit</a>
    
                                                            <a href='categories.php?block=delete&catId=" . $subCat["catId"] . "' class='delete'><i class='fa-solid fa-trash-can'></i>delete</a>
                                                        </span>
                                                    </li>";
                                            }
                                            echo '
                                                </ul>
                                            </section>
                                            ';
                                        }

                                        

                                    ?>
                        </section>
                    <?php   
                    }
                    ?>
                </section>
                <section class="container">
                    <a href='categories.php?block=add' class="add"><i class="fa-solid fa-plus"></i>add new categories</a>
                </section>
            </section>
            <?php
        }
        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
        exit();
    }