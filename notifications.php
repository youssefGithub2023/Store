<?php
    session_start();
    if (isset($_SESSION["userfront"])) {
        $titleKey = "login";
        include "init.inc.php";
        $userId = filter_var($_SESSION["userfrontid"], FILTER_SANITIZE_NUMBER_INT);

        // Make all notifications as read
        $stmt = $con->prepare("UPDATE notifications SET notStatus = 1 WHERE userId = :userId AND notStatus = 0");
        $stmt->bindParam("userId", $userId);
        $stmt->execute();

        // Get all notifications
        $allNotifications = getAllRecords("*", "notifications", "userId = $userId", "notId");
        echo '
        <section class="notifications">
            <h1>notifications</h1>
            <section class="container">
        ';
        
        if ($allNotifications->rowCount() > 0) {
            while ($notification = $allNotifications->fetch()) {
            ?>
                <article class="notification <?php echo $notification["notClass"] ?>">
                    <section class="not-content">
                        <p><?php echo $notification["notification"] ?></p>
                        <section class="date"><?php echo myDate($notification["addedDate"]) ?></section>
                    </section>
                    <span class="not-delete" data-notId="<?= $notification["notId"] ?>">
                        <i class="fa-solid fa-trash-can"></i>
                    </span>
                </article>
            <?php
            }
            echo "
            <span id='deleteAllNots' class='btn not-delete-all' ><i class='fa-solid fa-trash-can'></i> Delete all</span>
            ";
        } else {
            echo "<section class='worning'>There is no notifications to show</section>";
        }
        echo "
            </section>
        </section>
        ";
        
        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
    }