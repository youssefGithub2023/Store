<?php
    session_start();

    if (isset($_GET["block"]) && $_GET["block"] == "login") {
        $titleKey = "login";
        include "init.inc.php";

        if (isset($_SESSION["userfront"])) {
            header("location: index.php");
            exit();
        }

        if (isset($_POST["login"])) {

            $username = filter_var($_POST["username"], 513);

            $stmt = $con->prepare("SELECT userId, username, password FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam("username", $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                $pass = filter_var($_POST["password"], 513);
                if (password_verify($pass, $user["password"])) {
                    // If the password is valide.
                    $_SESSION["userfront"] = $user["username"];
                    $_SESSION["userfrontid"] = $user["userId"];
                    header("location: index.php");
                    exit();
                } else {
                    echo "<section class='alert container login-error'>" . lang("login_failed") . "</section>";
                }
            } else {
                echo "<section class='alert container login-error'>" . lang("login_failed") . "</section>";
            }
            
        } else {
        ?>
            <section class="login">
                <form  method="post" action="sign.php?block=login">

                    <h1>login</h1>

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
        <?php
        }

        include tpl . "footer.inc.php";
    } elseif (isset($_GET["block"]) && $_GET["block"] == "sign-up") {
        $titleKey = "login"; // Dont forget to replace this key to ""sign-up""
        include "init.inc.php";

        if (isset($_POST["sign-up"])) {

            // Phase (1): Filter
            $firstName = filter_var($_POST["firstName"], 513);
            $lastName = filter_var($_POST["lastName"], 513);
            $username = filter_var($_POST["username"], 513);
            $password = filter_var($_POST["password"], 513);
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

            // Phase (2): Validate
            $errors = array();
            
            if (empty($email)) {
                array_push($errors, "Email input can't be empty");
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Invalide email.");
            }

            if (empty($firstName)) {
                array_push($errors, "First name input can't be empty");
            }

            if (empty($lastName)) {
                array_push($errors, "Last name input can't be empty");
            }

            if (empty($username)) {
                array_push($errors, "Username input can't be empty");
            } elseif (strlen($username) < 5) {
                array_push($errors, "Username cant be less than 5 caracters");
            }

            if (empty($password)) {
                array_push($errors, "Password input can't be empty");
            } elseif (strlen($password) < 4) {
                array_push($errors, "Password cant be less than 4 caracters");
            } else {
                $hashedPassword = password_hash($_POST["password"], PASSWORD_DEFAULT);
            }

            $profileImg = $_FILES["profileImg"];
            if ($profileImg["error"] != 4) {
                $PI_tmp_name = $profileImg["tmp_name"];
                $PI_name = $profileImg["name"];
                $PI_size = $profileImg["size"];
                $ex = explode(".", $PI_name);
                $PI_ext = strtolower(end($ex));
                $avilable_extensions = array("png", "jpg", "jpeg", "gif");

                if (! in_array($PI_ext, $avilable_extensions)) {
                    array_push($errors, '"png", "jpg", "jpeg" and "gif" are the avilable extenions');
                }

                if ($PI_size > 3000000) {
                    array_push($errors, "The size of the profile image is too large");
                }
            }

            // Phase (3): Send to database
            if (empty($errors)) {

                

                if (isAlreadyInUse("users", "username", $username)) {
                    redirect("<section class='container'><section class='alert'>This username \"" . $username . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                } elseif (isAlreadyInUse("users", "email", $email)) {
                    redirect("<section class='container'><section class='alert'>This email \"" . $email . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                } else {
                    
                    // Upload profile image
                    if (isset($PI_tmp_name)) {
                        $profileImgPath = random_name(40) . "." . $PI_ext;
                        move_uploaded_file($PI_tmp_name, profileImgs . $profileImgPath);
                    } else {
                        $profileImgPath = null;
                    }

                    // Send user info to database
                    $stmt = $con->prepare("INSERT INTO users(username, password, email, firstName, lastName, profileImgPath) VALUES (:username, :password, :email, :firstName, :lastName, :profileImgPath)");
                    $stmt->bindParam("username", $username);
                    $stmt->bindParam("password", $hashedPassword);
                    $stmt->bindParam("email", $email);
                    $stmt->bindParam("firstName", $firstName);
                    $stmt->bindParam("lastName", $lastName);
                    $stmt->bindParam("profileImgPath", $profileImgPath);
                    $stmt->execute();

                    if ($stmt) {
                        // Insert notification
                        $getUserInfo = getAllRecords("userId, firstName", "users", "username = '$username'", "userId");
                        $userInfo = $getUserInfo->fetch();
                        $notification = "Welcome <span class='focus'>" . $userInfo["firstName"] . "</span> you are regestred successfully, but your accunt is not activated yet.";
                        generateNotification($notification, "not-success", $userInfo["userId"]);

                        redirect("<section class='container'><section class='success'>You are registred successfully</section></section>", "sign.php?block=login", 3);
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
            <section class="sign-up">
                <section class="container">
                    <form method="post" action="sign.php?block=sign-up" enctype="multipart/form-data">
                        <h1>sign-up</h1>
                        <section class="cont-inputs">

                            <section class="left">
                                <label class="form-label" for="firstName">first name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="firstName" id="firstName">
                                </section>
                                
                                <label class="form-label" for="username">username</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-user"></i></section><input type="text" name="username" id="username">
                                </section>

                                <label class="form-label" for="email">Email</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa fa-envelope"></i></section><input type="email" name="email" id="email">
                                </section>
                            </section>

                            <section class="right">
                                <label class="form-label" for="lastName">last name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="lastName" id="lastName">
                                </section>

                                <label class="form-label" for="password">Password</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-lock"></i></section><input type="password" name="password" id="password">
                                </section>

                                <label class="form-label" for="profileImg">profile image</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="profileImg" id="profileImg">
                                </section>
                            </section>
                        </section>
                        <button class="btn" name="sign-up" type="submit">sign-up</button>
                    </form>
                </section>
            </section>
        <?php
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
    }