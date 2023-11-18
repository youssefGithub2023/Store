<header>
    <nav class="container">
        <ul class="links">
            <li><a href="dashboard.php"><?php echo lang("home"); ?></a></li>
            <li><a href="members.php"><?php echo lang("members"); ?></a></li>
            <li><a href="categories.php"><?php echo lang("categories"); ?></a></li>
            <li><a href="items.php"><?php echo lang("items"); ?></a></li>
            <li><a href="comments.php"><?php echo lang("comments"); ?></a></li>
            <li><a href="statistics.php"><?php echo lang("statistics"); ?></a></li>
            <li><a href="#"><?php echo lang("logs"); ?></a></li>
        </ul>

        <section id="dropdown-btn">
            <span><?php echo $_SESSION['username'] ?></span><i class="fa-solid fa-bars"></i>
        </section>
        <ul id="dropdown">
            <li><a href="../index.php">visit shop</a></li>
            <li>
                <?php echo "<a href='members.php?block=edit&userId=" . $_SESSION['userId'] . "'>" . lang('edit profile') . "</a>" ?>
                
            </li>
            <li><a href="#"><?php echo lang("settings"); ?></a></li>
            <li><a href="logout.php"><?php echo lang("logout"); ?></a></li>
        </ul>
    </nav>
</header>