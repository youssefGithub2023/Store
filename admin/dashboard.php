<?php
    session_start();

    if (isset($_SESSION["username"])) {
        $titleKey = "dashboard"; // Key for get the title from lang file
        include "init.inc.php";
        
        ?>
        <h1>Dashboard</h1>
        <section class="stats">
            <section class="container">
                <section class="stat" data-location="members.php">
                    <section class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </section>
                    <section class="stat-body">
                        <section class="stat-title">Total members</section>
                        <section class="stat-result"><?php echo totalRecords("users", "userId"); ?></section>
                    </section>
                </section>

                <section class="stat" data-location="members.php?only=pending">
                    <section class="stat-icon">
                        <i class="fa-solid fa-user-clock"></i>
                    </section>
                    <section class="stat-body">
                        <section class="stat-title">Pending members</section>
                        <section class="stat-result"><?php echo totalRecords("users", "regStatus", "0"); ?></section>
                    </section>
                </section>

                <section class="stat" data-location="items.php">
                    <section class="stat-icon">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </section>
                    <section class="stat-body">
                        <section class="stat-title">Total items</section>
                        <section class="stat-result"><?php echo totalRecords("items", "itemId"); ?></section>
                    </section>
                </section>

                <section class="stat" data-location="items.php?only=pending">
                    <section class="stat-icon">
                        <i class="fa-solid fa-comments"></i>
                    </section>
                    <section class="stat-body">
                        <section class="stat-title">Pending items</section>
                        <section class="stat-result"><?php echo totalRecords("items", "accept", "0"); ?></section>
                    </section>
                </section>
            </section>
        </section>

        <section class="latest">
            <section class="container">
                <section class="latest-box">
                    <h2>Latest 5 members <i class="fade fa-solid fa-chevron-down"></i></h2>
                    <ul>
                    <?php
                        $latestUsers = getLatestRecords("userId, username, regStatus", "users", "userId");
                        
                        foreach ($latestUsers as $userRow) {
                            echo "<li>";
                                echo "<a href='members.php?only=" . $userRow["userId"] . "' class='left'>" . $userRow["username"] . "</a>";
                                
                                echo " <a href='members.php?block=delete&userId=" . $userRow["userId"] . "' class='delete right'><i class='fa-solid fa-user-xmark'></i>delete</a>";

                                echo " <a href='members.php?block=edit&userId=" . $userRow["userId"] . "' class='edit right'><i class='fa-solid fa-user-pen'></i>edit</a>";

                                if ($userRow["regStatus"] == 0) {
                                    echo " <a href='members.php?block=activate&userId=" . $userRow["userId"] . "' class='activate right'><i class='fa-solid fa-user-check'></i>activate</a>";
                                }
                            echo "</li>";
                        }
                    ?>
                    </ul>
                </section>

                <section class="latest-box">
                    <h2>Latest 5 items <i class="fade fa-solid fa-chevron-down"></i></h2>
                    <ul>
                    <?php
                        $latestItems = getLatestRecords("itemId, itemName, accept", "items", "itemId");
                        foreach ($latestItems as $itemRow) {
                            echo "<li>";
                                echo "<a href='items.php?block=details&itemId=" . $itemRow["itemId"] . "' class='left'>" . $itemRow["itemName"] . "</a>";

                                echo " <a href='items.php?block=delete&itemId=" . $itemRow["itemId"] . "' class='delete right'><i class='fa-solid fa-trash-can'></i>delete</a>";

                                echo " <a href='items.php?block=edit&itemId=" . $itemRow["itemId"] . "' class='edit right'><i class='fa-solid fa-pen-to-square'></i>edit</a>";

                                if ($itemRow["accept"] == 0) {
                                    echo " <a href='items.php?block=accept&itemId=" . $itemRow["itemId"] . "' class='activate right'><i class='fa-solid fa-check'></i>accept</a>";
                                }
                            echo "</li>";
                        }
                    ?>
                    </ul>
                </section>

                <section class="latest-box">
                    <h2>Latest 3 comments <i class="fade fa-solid fa-chevron-down"></i></h2>
                    <ul>
                    <?php
                        $stmt = $con->prepare("SELECT commentId, comment, commentStatus, username, userId FROM comments JOIN users USING (userId) ORDER BY commentId DESC LIMIT 3");
                        $stmt->execute();

                        foreach ($stmt as $commentRow) {
                            echo "<section class='comment-box'>";
                                echo "<span><a href='members.php?only=" . $commentRow["userId"] . "'>" . $commentRow["username"] . "</a></span>";
                                echo "<section class='com-body'>";
                                    echo "<p>" . $commentRow["comment"] . "</p>";
                                    echo "<section class='com-op'>";

                                        if ($commentRow["commentStatus"] == 0) {
                                            echo '<a href="comments.php?block=accept&commentId=' . $commentRow["commentId"] . '" class="activate"><i class="fa-solid fa-check"></i>accept</a>';
                                        }

                                        echo '
                                            <a href="comments.php?block=edit&commentId=' . $commentRow["commentId"] . '" class="edit"><i class="fa-solid fa-pen-to-square"></i>edit</a>
                                            
                                            <a href="comments.php?block=delete&commentId=' . $commentRow["commentId"] . '" class="delete"><i class="fa-solid fa-trash-can"></i>delete</a>
                                        ';

                                    echo "</section>";
                                echo "</section>";
                            echo "</section>";
                        }
                    ?>
                    </ul>
                </section>
            </section>
        </section>


        <?php
        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
        exit();
    }
    

