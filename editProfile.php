<?php
    session_start();

    if (isset($_SESSION["userfront"])) {
        include "init.inc.php";
        $userId = intval($_SESSION["userfrontid"]); // Current user userId

        if (isset($_POST["save"])) {
            // Filter
            $firstName = filter_var($_POST["firstName"], 513);
            $username = filter_var($_POST["username"], 513);
            $lastName = filter_var($_POST["lastName"], 513);
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
            $password = $_POST["password"];

            // Validate
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
                $hashedPassword = filter_var($_POST["oldPassword"], 513);
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

            if (empty($errors)) {
                // Update user data
                $checkUsername = $con->prepare("SELECT userId FROM users WHERE username = :username AND userId != :userId");
                $checkUsername->bindParam("username", $username);
                $checkUsername->bindParam("userId", $userId);
                $checkUsername->execute();

                $checkEmail = $con->prepare("SELECT userId FROM users WHERE email = :email AND userId != :userId");
                $checkEmail->bindParam("email", $email);
                $checkEmail->bindParam("userId", $userId);
                $checkEmail->execute();

                if ($checkUsername->rowCount() > 0) {
                    redirect("<section class='container'><section class='alert'>This username \"" . $username . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                } elseif ($checkEmail->rowCount() > 0) {
                    redirect("<section class='container'><section class='alert'>This email \"" . $email . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                } else {

                    // Update profile image
                    $oldProfileImgPath = filter_var($_POST["oldProfileImgPath"], 513);
                    if (preg_match("/^\d{40}\.(png|jpe?g|gif)$/", $oldProfileImgPath) || empty($oldProfileImgPath)) {
                        if (isset($PI_tmp_name)) {
                            // Delete old profile image
                            if (! empty($oldProfileImgPath)) {
                                unlink(profileImgs . $oldProfileImgPath);
                            }
    
                            // Upload new profile image
                            $profileImgPath = random_name(40) . "." . $PI_ext;
                            move_uploaded_file($PI_tmp_name, profileImgs . $profileImgPath);
                        } else {
                            if (empty($oldProfileImgPath)) {
                                $profileImgPath = null;
                            } else {
                                $profileImgPath = $oldProfileImgPath;
                            }
                        }
                    } else {
                        $profileImgPath = null;
                    }

                    $stmt = $con->prepare("UPDATE users SET username = :username, password = :password, email = :email, firstName = :firstName, lastName = :lastName, profileImgPath = :profileImgPath WHERE userId = :userId");

                    $stmt->bindParam("userId", $userId);
                    $stmt->bindParam("username", $username);
                    $stmt->bindParam("password", $hashedPassword);
                    $stmt->bindParam("email", $email);
                    $stmt->bindParam("firstName", $firstName);
                    $stmt->bindParam("lastName", $lastName);
                    $stmt->bindParam("profileImgPath", $profileImgPath);
                    $stmt->execute();

                    if ($stmt) {
                        redirect("<section class='container'><section class='success'>you are updated your data successfully</section></section>", "profile.php", 3);
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
            $getUser = getAllRecords("*", "users", "userId = $userId", "userId");
            $userData = $getUser->fetch();

        ?>
            <section class="editProfile">
                <section class="container">
                    <form method="post" action="" enctype="multipart/form-data">
                        <h1>edit profile</h1>
                        <section class="cont-inputs">

                            <section class="left">
                                <label class="form-label" for="firstName">first name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="firstName" id="firstName" value="<?php echo $userData["firstName"] ?>">
                                </section>
                                
                                <label class="form-label" for="username">username</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-user"></i></section><input type="text" name="username" id="username" value="<?php echo $userData["username"] ?>">
                                </section>

                                <label class="form-label" for="email">Email</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa fa-envelope"></i></section><input type="email" name="email" id="email" value="<?php echo $userData["email"] ?>">
                                </section>
                            </section>

                            <section class="right">
                                <label class="form-label" for="lastName">last name</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="lastName" id="lastName" value="<?php echo $userData["lastName"] ?>">
                                </section>

                                <label class="form-label" for="password">Password</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-lock"></i></section><input type="password" name="password" id="password">
                                    <input type="hidden" name="oldPassword" value="<?php echo $userData["password"] ?>">
                                </section>

                                <label class="form-label" for="profileImg">profile image</label>
                                <section class="form-group">
                                    <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="profileImg" id="profileImg">
                                    <input type="hidden" name="oldProfileImgPath" value="<?php echo $userData["profileImgPath"] ?>">
                                </section>
                            </section>
                        </section>
                        <button class="btn" name="save" type="submit">save</button>
                    </form>
                </section>
            </section>
        <?php
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: sign.php?block=login");
        exit();
    }