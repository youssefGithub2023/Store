<?php
    session_start();
    if (isset($_SESSION["username"])) {
        $titleKey = "comments";
        include "init.inc.php";

        if (isset($_GET["block"])) {
            if ($_GET["block"] == "edit") {
                if (isset($_POST["save"])) {

                    if (empty($_POST["comment"])) {
                        redirect("<section class='container'><section class='alert'>Comment cant be empty</section></section>", $_SERVER["HTTP_REFERER"], 3);
                    } else {
                        $stmt = $con->prepare("UPDATE comments SET comment = :comment WHERE commentId = :commentId");
                        $stmt->bindParam("comment", $_POST["comment"]);
                        $stmt->bindParam("commentId", $_POST["commentId"]);
                        $stmt->execute();

                        header("location: " . linkWithoutEdited());
                        exit();
                    }

                } else {
                    if (isset($_GET["commentId"]) && is_numeric($_GET["commentId"])) {
                        $commentId = intval($_GET["commentId"]);
                        $stmt = $con->prepare("SELECT commentId, comment FROM comments WHERE commentId = :commentId");
                        $stmt->bindParam("commentId", $commentId);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0)  {
                            $comment = $stmt->fetch();

                            ?>
                            <section class="get-info">
                                <section class="container">
                                    <h1>Edit comment</h1>
                                    <?php
                                        if (isset($_GET["edited"])) {
                                            echo "<section class='success'>Edited successfully</section>";
                                        }
                                    ?>
                                    <form method="post" action="comments.php?block=edit">
                                        <section class="desc">
                                            <input type="hidden" name="commentId" value="<?php echo $comment['commentId'] ?>">
                                            <label class="form-label" for="desc-item">comment</label>
                                            <textarea class="desc-item comment" id="desc-item" name="comment"><?php echo $comment["comment"] ?></textarea>
                                        </section>
        
                                        <button type="submit" name="save" class="btn">save</button>
                                    </form>
                                </section>
                            </section>
                            <?php
                        } else {
                            redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "comments.php", 3);
                        }
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "comments.php", 3);
                    }
                }
            } elseif ($_GET["block"] == "delete") {

                if (isset($_GET["commentId"]) && is_numeric($_GET["commentId"])) {
                    $commentId = intval($_GET["commentId"]);

                    $stmt = $con->prepare("DELETE FROM comments WHERE commentId = :commentId");
                    $stmt->bindParam("commentId", $commentId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        redirect("<section class='container'><section class='success'>The comment has \"ID = $commentId\" deleted successfully</section></section>", isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "comments.php", 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "comments.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "comments.php", 3);
                }

            } elseif ($_GET["block"] == "accept") {

                if (isset($_GET["commentId"]) && is_numeric($_GET["commentId"])) {
                    $commentId = intval($_GET["commentId"]);

                    $stmt = $con->prepare("UPDATE comments SET commentStatus = 1 WHERE commentId = :commentId");
                    $stmt->bindParam("commentId", $commentId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        redirect("<section class='container'><section class='success'>The comment has \"ID = $commentId\" accepted successfully</section></section>", isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "comments.php", 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "comments.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "comments.php", 3);
                }

            } else {
                header("location: comments.php");
                exit();
            }
        } else {
            if (isset($_GET["only"]) && $_GET["only"] = "pending") {
                $cond = "WHERE commentStatus = 0";
            } else {
                $cond = "";
            }

            $stmt = $con->prepare("SELECT commentId, comment, comments.addedDate, commentStatus, comments.userId, comments.itemId, username, profileImgPath, itemName, itemImg FROM comments JOIN users USING (userId) JOIN items USING (itemId) $cond ORDER BY commentId DESC");
            $stmt->execute();

            ?>
            <section class="comments-cont">
                <section class="container">
                    <section class="head">
                        <h1><?php echo empty($cond) ? "show all comments" : "show pending comments" ?></h1>
                        <section class="">
                            <a href="comments.php?only=pending">pending comments</a>
                            <section class="totalSec">
                                <span class="totalText"><?php echo empty($cond) ? "total comments: " : "total pending comments: " ?></span> <span class="totalResult"><?php echo $stmt->rowCount() ?></span>
                            </section>
                        </section>
                    </section>

                    <section class="comments">

                    <?php
                        while ($row = $stmt->fetch()) {
                            echo '
                                <article class="comment">
                                    <header>
                                        <section class="user">
                                            <figure>
                                                <img src="' . profileImgs . proImgPath($row["profileImgPath"], $row["username"]) . '">
                                            </figure>
                                            <p>' . $row["username"] . '</p>
                                        </section>

                                        <section class="item">
                                            <figure>
                                                <img src="' . itemImgs . $row["itemImg"] . '">
                                            </figure>

                                            <p>' . $row["itemName"] . '</p>
                                        </section>
                                    </header>

                                    <p class="comment-text">' . $row["comment"] . '</p>

                                    <footer>
                                        <section class="added-date">' . myDate($row["addedDate"]) . '</section>
                                        <section class="operations">
                                ';

                                                if ($row["commentStatus"] == 0) {
                                                    echo '<a href="comments.php?block=accept&commentId=' . $row["commentId"] . '" class="activate"><i class="fa-solid fa-check"></i>accept</a>';
                                                }

                                echo '
                                                <a href="comments.php?block=edit&commentId=' . $row["commentId"] . '" class="edit"><i class="fa-solid fa-pen-to-square"></i>edit</a>
                                                
                                                <a href="comments.php?block=delete&commentId=' . $row["commentId"] . '" class="delete"><i class="fa-solid fa-trash-can"></i>delete</a>

                                        </section>
                                    </footer>
                                </article>
                            ';
                        }
                        
                    ?>
                    </section>
                </section>
            </section>

            <?php
            include tpl . "footer.inc.php";

        }
    } else {
        header("location: index.php");
        exit();
    }
