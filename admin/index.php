<?php
    session_start();

    if (isset($_SESSION["username"])) {
        header("location: dashboard.php");
        exit();
    }

    $titleKey = "login"; // Key for get the title from lang file
    $noNavbar = "";

    include "init.inc.php";

    if (isset($_POST['login'])) {
        $username   = filter_var($_POST["username"], 513);
        $Pass       = filter_var($_POST["password"], 513);

        $stmt = $con->prepare("SELECT userId, username, password FROM users WHERE username = :user AND type = 1 LIMIT 1");
        $stmt->bindParam("user", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();

            if (password_verify($Pass, $row["password"])) {
                // If the password is valid
                $_SESSION["userId"]     = $row["userId"];
                $_SESSION["username"]   = $username;
                header("location: dashboard.php");
                exit();
            } else {
                echo "<section class='alert container login-error'>" . lang("login_failed") . "</section>";
            }
        } else {
            echo "<section class='alert container login-error'>" . lang("login_failed") . "</section>";
        }
        
    }
?>

        <section class="login">
            <form action="<?PHP echo $_SERVER['PHP_SELF']; ?>" method="post">

                <h1><?php echo lang("admin_login"); ?></h1>

                <label class="form-label c1" for="username"><?php echo lang("user"); ?></label>
                <section class="form-group">
                    <section class="icon c1"><i class="fa-solid fa-user"></i></section><input class="c1" type="text" name="username" placeholder="<?php echo lang("PHuser"); ?>" autocomplete="off">
                </section>

                <label class="form-label c2" for="password"><?php echo lang("pass"); ?></label>
                <section class="form-group">
                    <section class="icon c2"><i class="fa-solid fa-lock"></i></section><input class="c2" type="password" name="password" placeholder="<?php echo lang("PHpass"); ?>" autocomplete="off">
                </section>

                <button type="submit" name="login"><?php echo lang("login"); ?></button>

            </form>
        </section>

<?php include tpl . "footer.inc.php"; ?>