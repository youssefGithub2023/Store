<?php
    session_start();

    if (isset($_SESSION["username"])) {
        $titleKey = "members";
        include "init.inc.php";
        $sessionId = $_SESSION["userId"];
        
        if (isset($_GET["block"])) {
            if ($_GET["block"] == "edit") { // Edit block
                if (isset($_POST['save'])) {
        
                    $errors = array();
        
                    if (empty($_POST["firstName"])) {
                        array_push($errors, "First name input cant be empty");
                    }
        
                    if (empty($_POST["lastName"])) {
                        array_push($errors, "Last name input cant be empty");
                    }
        
                    if (empty($_POST["username"])) {
                        array_push($errors, "Username input cant be empty");
                    } elseif (strlen($_POST["username"]) < 5) {
                        array_push($errors, "Username cant be less than 5 caracters");
                    }
        
                    if (empty($_POST["email"])) {
                        array_push($errors, "Email input cant be empty");
                    }

                    if (!empty($_POST["password"]) && strlen($_POST["password"]) < 4) {
                        array_push($errors, "Password input cant be less than 4 caracters");
                    }

                    // Start upload profile image
                    $profileImg = $_FILES["profileImg"];

                    if ($profileImg["error"] != 4) {
                        $PI_tmp_name = $profileImg["tmp_name"];
                        $PI_name = $profileImg["name"];
                        $PI_size = $profileImg["size"];
                        $ex = explode(".", $PI_name);
                        $PI_ext = strtolower(end($ex));
                        $avilable_extensions = array("png", "jpg", "jpeg", "gif");

                        if ($PI_size > 3000000) {
                            array_push($errors, 'The size of the profile image is too large');
                        }

                        if (! in_array($PI_ext, $avilable_extensions)) {
                            array_push($errors, '"png", "jpg", "jpeg" and "gif" are the avilable extenions');
                        }
                    }
                    // End upload profile image
        
                    if (empty($errors)) {
                        include "connection.php";
                        $checkUsername = $con->prepare("SELECT userId FROM users WHERE username = :username AND userId != :userId");
                        $checkUsername->bindParam("username", $_POST["username"]);
                        $checkUsername->bindParam("userId", $_POST["userId"]);
                        $checkUsername->execute();

                        $checkEmail = $con->prepare("SELECT userId FROM users WHERE email = :email AND userId != :userId");
                        $checkEmail->bindParam("email", $_POST["email"]);
                        $checkEmail->bindParam("userId", $_POST["userId"]);
                        $checkEmail->execute();

                        if ($checkUsername->rowCount() > 0) {
                            redirect("<section class='container'><section class='alert'>This username \"" . $_POST["username"] . "\" is already in use</section></section>", linkWithoutEdited(), 3);
                        } elseif ($checkEmail->rowCount() > 0) {
                            redirect("<section class='container'><section class='alert'>This email \"" . $_POST["email"] . "\" is already in use</section></section>", linkWithoutEdited(), 3);
                        } else {

                            if (isset($PI_tmp_name)) {
                                // Remove old profile image if not a default image
                                if (! empty($_POST["oldProfileImgPath"])) {
                                    unlink(profileImgs . $_POST["oldProfileImgPath"]);
                                }

                                // Upload new profile image
                                $profileImgPath = random_name(40) . "." . $PI_ext;
                                move_uploaded_file($PI_tmp_name, profileImgs . $profileImgPath);
                            } else {
                                if (empty($_POST["oldProfileImgPath"])) {
                                    $profileImgPath = null;
                                } else {
                                    $profileImgPath = $_POST["oldProfileImgPath"];
                                }
                            }

                            if (empty($_POST["password"])) {
                                $stmt = $con->prepare("UPDATE users SET username = :username, email = :email, firstName = :firstName, lastName = :lastName, profileImgPath = :profileImgPath WHERE userId = :userId AND (type = 0 OR userId = :sessionId)");
                            } else {
                                $stmt = $con->prepare("UPDATE users SET username = :username, password = :password, email = :email, firstName = :firstName, lastName = :lastName, profileImgPath = :profileImgPath WHERE userId = :userId AND (type = 0 OR userId = :sessionId)");
                                $hashedPass = password_hash($_POST["password"], PASSWORD_DEFAULT);
                                $stmt->bindParam("password", $hashedPass);
                            }
            
                            $stmt->bindParam("userId", $_POST["userId"]);
                            $stmt->bindParam("username", $_POST["username"]);
                            $stmt->bindParam("email", $_POST["email"]);
                            $stmt->bindParam("firstName", $_POST["firstName"]);
                            $stmt->bindParam("lastName", $_POST["lastName"]);
                            $stmt->bindParam("profileImgPath", $profileImgPath);
                            $stmt->bindParam("sessionId", $sessionId);
                            $stmt->execute();

                            $link = strpos($_SERVER["HTTP_REFERER"], "&edited") ? $_SERVER["HTTP_REFERER"] : $_SERVER["HTTP_REFERER"] . "&edited";

                            header("location: $link");
                            exit();
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
                    if (isset($_GET["userId"]) && is_numeric($_GET["userId"])) {

                        $userId = intval($_GET["userId"]);

                        $stmt = $con->prepare("SELECT * FROM users WHERE userId = :userId AND (type = 0 OR userId = :sessionId)");
                        $stmt->bindParam("userId", $userId);
                        $stmt->bindParam("sessionId", $sessionId);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            $row = $stmt->fetch();
                            ?>
                            <section class="get-info">
                                <form class="container" method="post" action="members.php?block=edit" enctype="multipart/form-data">
                                    <input type="hidden" name="userId" value='<?php echo $_GET["userId"]; ?>'>
                                    <h1>Edit membere</h1>
                                    <?php
                                        if (isset($_GET["edited"])) {
                                            echo "<section class='success'>Edited successfully</section>";
                                        }
                                    ?>
                                    <section class="cont-inputs">

                                        <section class="left">
                                            <label class="form-label" for="firstName">first name</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" value="<?php echo $row['firstName']?>" name="firstName" id="firstName">
                                            </section>
                                            
                                            <label class="form-label" for="username">username</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-user"></i></section><input type="text" name="username" value="<?php echo $row['username']?>" id="username">
                                            </section>

                                            <label class="form-label" for="email">Email</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa fa-envelope"></i></section><input type="email" name="email" value="<?php echo $row['email']?>" id="email">
                                            </section>
                                        </section>

                                        <section class="right">
                                            <label class="form-label" for="lastName">last name</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-id-card"></i></section><input type="text" name="lastName" value="<?php echo $row['lastName']?>" id="lastName">
                                            </section>

                                            <label class="form-label" for="password">Password</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-lock"></i></section><input type="password" name="password" id="password">
                                            </section>

                                            <label class="form-label" for="profileImg">profile image</label>
                                            <section class="form-group">
                                                <section class="icon"><i class="fa-solid fa-image" aria-hidden="true"></i></section><input type="file" name="profileImg" id="profileImg">
                                                <input type="hidden" name="oldProfileImgPath" value="<?php echo $row['profileImgPath']?>">
                                            </section>
                                        </section>

                                    </section>
                                    <button class="btn" name="save" type="submit">save</button>
                                </form>
                            </section>
                        <?php
                        } else {
                            redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "members.php", 3);
                        }
                    } else {
                        redirect("<section class='container'><section class='alert'>You cannot visite this page directly</section></section>", "members.php", 3);
                    }
                }
            } elseif ($_GET["block"] == "add") {
                if (isset($_POST['add'])) {

                    $errors = array();

                    if (empty($_POST["username"])) {
                        array_push($errors, "Username input cant be empty");
                    } elseif (strlen($_POST["username"]) < 5) {
                        array_push($errors, "Username cant be less than 5 caracters");
                    }
                    
                    if (empty($_POST["password"])) {
                        array_push($errors, "Password input cant be empty");
                    } elseif (strlen($_POST["password"]) < 4) {
                        array_push($errors, "Password input cant be less than 4 caracters");
                    }

                    if (empty($_POST["email"])) {
                        array_push($errors, "Email input cant be empty");
                    }

                    if (empty($_POST["firstName"])) {
                        array_push($errors, "First name input cant be empty");
                    }

                    if (empty($_POST["lastName"])) {
                        array_push($errors, "Last name input cant be empty");
                    }

                    // Start upload profile image
                    $profileImg = $_FILES["profileImg"];
                    
                    if ($profileImg["error"] != 4) {
                        $PI_tmp_name = $profileImg["tmp_name"];
                        $PI_name = $profileImg["name"];
                        $PI_size = $profileImg["size"];
                        $ex = explode(".",  $PI_name);
                        $PI_ext = strtolower(end($ex));
                        $avilable_extensions = array("png", "jpg", "jpeg", "gif");

                        if (! in_array($PI_ext, $avilable_extensions)) {
                            array_push($errors, '"png", "jpg", "jpeg" and "gif" are the avilable extensions');
                        }

                        if ($PI_size > 3000000) {
                            array_push($errors, 'The size of the profile image is too large');
                        }
                    }
                    // End upload profile image

                    if (empty($errors)) {
                        if (isAlreadyInUse("users", "username", $_POST["username"])) {
                            redirect("<section class='container'><section class='alert'>This username \"" . $_POST["username"] . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        } elseif (isAlreadyInuse("users", "email", $_POST["email"])) {
                            redirect("<section class='container'><section class='alert'>This email \"" . $_POST["email"] . "\" is already in use</section></section>", $_SERVER["HTTP_REFERER"], 3);
                        } else {

                            if (isset($PI_tmp_name)) {
                                $profileImgPath = random_name(40) . "." . $PI_ext;
                                move_uploaded_file($PI_tmp_name, profileImgs . $profileImgPath);
                            } else {
                                $profileImgPath = null;
                            }

                            $stmt = $con->prepare("INSERT INTO users(username, password, email, firstName, lastName, profileImgPath, regStatus) VALUES (:username, :password, :email, :firstName, :lastName, :profileImgPath, 1);");
                            $stmt->bindParam("username", $_POST["username"]);
                            $hashedPass = password_hash($_POST["password"], PASSWORD_DEFAULT);
                            $stmt->bindParam("password", $hashedPass);
                            $stmt->bindParam("email", $_POST["email"]);
                            $stmt->bindParam("firstName", $_POST["firstName"]);
                            $stmt->bindParam("lastName", $_POST["lastName"]);
                            $stmt->bindParam("profileImgPath", $profileImgPath);
                            $stmt->execute();

                            if ($stmt) {
                                // Insert notification
                                $getUserInfo = getAllRecords("userId, firstName", "users", "username = '" . $_POST["username"] . "'", "userId");
                                $userInfo = $getUserInfo->fetch();
                                $notification = "Welcome <span class='focus'>" . $userInfo["firstName"] . "</span> you are added by admin and your account is activated";
                                generateNotification($notification, "not-normal", $userInfo["userId"]);

                                redirect("<section class='container'><section class='success'>One user added successfully</section></section>", "members.php", 3);
                            }
                        }
                    } else {
                        echo "<section class='container'>";
                        foreach ($errors as $error) {
                            echo "<section class='alert'>" . $error . "</section>";
                        }
                        echo "</section>";

                        header("refresh: 5; URL=" . $_SERVER["HTTP_REFERER"]);
                    }
                } else {
                    ?>
                    <section class="get-info">
                        <form class="container" method="post" action="members.php?block=add" enctype="multipart/form-data">
                            <h1>Add new membere</h1>
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
                            <button class="btn" name="add" type="submit">add</button>
                        </form>
                    </section>
                    <?php
                }
            } elseif ($_GET["block"] == "delete") {

                if (isset($_GET["userId"]) && is_numeric($_GET["userId"])) {
                    $userId = intval($_GET["userId"]);
                    $stmt = $con->prepare("DELETE FROM users WHERE userId = :userId AND (type = 0 OR userId = :sessionId)");
                    $stmt->bindParam("userId", $userId);
                    $stmt->bindParam("sessionId", $sessionId);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        redirect("<section class='container'><section class='success'>The membere has \"ID = $userId\" deleted successfully</section></section>", isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != "" ? $_SERVER["HTTP_REFERER"] : "members.php", 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "members.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visit this page directly</section></section>", "members.php", 3);
                }

            } elseif ($_GET["block"] == "activate") {
                if (isset($_GET["userId"]) && is_numeric($_GET["userId"])) {
                    $userId = intVal($_GET["userId"]);
                    $stmt = $con->prepare("UPDATE users SET regStatus = 1 WHERE userId = :userId");
                    $stmt->bindParam("userId", $userId);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {

                        $getUserName = getAllRecords("firstName", "users", "userId = $userId", "userId");
                        $notification = "Welcome " . $getUserName->fetchColumn() . " your account is activated";
                        generateNotification($notification, "not-success", $userId);

                        redirect("<section class='container'><section class='success'>The user has \"ID=$userId\" activated successfully</section></section>", isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != "" ? $_SERVER["HTTP_REFERER"] : "members.php", 3);
                    } else {
                        redirect("<section class='container'><section class='worning'>There is no match ?</section></section>", "members.php", 3);
                    }
                } else {
                    redirect("<section class='container'><section class='alert'>You cannot visit this page directly</section></section>", "members.php", 3);
                }
            } else {
                header("location: members.php");
                exit();
            }
        } else { // Manage block 'Default'
            // Show only pending members or all members or only one latest membere
            $only = "";
            $h1Text = "Show all members";
            $totalText = "Total members:";

            if (isset($_GET["only"])) {
                if ($_GET["only"] == "pending") {
                    $only = "AND regStatus = 0";
                    $h1Text = "Show all pending members";
                    $totalText = "Total pending members:";
                } elseif (is_numeric($_GET["only"])) {
                    $userId = intVal($_GET["only"]);
                    $only = "AND userId = :userId";
                    $h1Text = "Show latest members has \"ID=$userId\"";
                }
            }

            $orderBy = isset($_GET["orderBy"]) ? $_GET["orderBy"] : "userId DESC";

            $stmt = $con->prepare("SELECT userId, username, email, CONCAT(firstName, ' ', lastName) AS fullName, profileImgPath, regStatus, dateOfRegistration FROM users WHERE (type = 0 OR userId = :sessionId) $only ORDER BY $orderBy"); // Not return admins
            $stmt->bindParam("sessionId", $_SESSION["userId"]);
            if (isset($userId)) {
                $stmt->bindParam("userId", $userId);
            }
            $stmt->execute();

            ?>
            <section class="members-cont">
                <section class="container">
                    <section class="head">
                        <h1><?php echo $h1Text ?></h1>
                        <section class="totalSec <?php echo isset($userId) ? 'hide' : '' ?>">
                            <span class="totalText"><?php echo $totalText ?></span> <span class="totalResult"><?php echo $stmt->rowCount() ?></span>
                        </section>
                    </section>
                    <table class="main-tb">
                        <thead>
                            <tr>
                                <th><a href="<?php echo orderByHref('orderBy=userId')?>">#id</a></th>
                                <th>avatar</th>
                                <th><a href="<?php echo orderByHref('orderBy=username')?>"><i class="fa-solid fa-user"></i>username</a></th>
                                <th><a href="<?php echo orderByHref('orderBy=email')?>"><i class="fa fa-envelope"></i>email</a></th>
                                <th><a href="<?php echo orderByHref('orderBy=fullName')?>"><i class="fa-solid fa-id-card"></i>full name</a></th>
                                <th><a href="<?php echo orderByHref('orderBy=dateOfRegistration')?>"><i class="fa-solid fa-calendar-days"></i>registred date</a></th>
                                <th><i class="fa-solid fa-gear"></i>operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = $stmt->fetch()) {
                                echo "
                                    <tr>
                                        <td>" . $row["userId"] . "</td>
                                        <td><figure><img src='" . profileImgs . proImgPath($row["profileImgPath"], $row["username"]) . "'></figure></td>
                                        <td>" . $row["username"] . "</td>
                                        <td>" . $row["email"] . "</td>
                                        <td>" . $row["fullName"] . "</td>
                                        <td>" . myDate($row["dateOfRegistration"]) . "</td>
                                        <td>";
                                        
                                        if ($row["regStatus"] == 0) {
                                            echo "<a href='members.php?block=activate&userId=" . $row["userId"] . "' class='activate'><i class='fa-solid fa-user-check'></i>activate</a>";
                                        }

                                        echo " <a href='members.php?block=edit&userId=" . $row["userId"] . "' class='edit'><i class='fa-solid fa-user-pen'></i>edit</a> <a href='members.php?block=delete&userId=" . $row["userId"] . "' class='delete'><i class='fa-solid fa-user-xmark'></i>delete</a>";
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <a href="members.php?block=add" class="add"><i class="fa-solid fa-user-plus"></i>Add new member</a>
                </section>
            </section>
            <?php
        }

        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
        exit();
    }