<?php

    if (isset($_GET["inpVal"])) {

        $inpVal = "%" . filter_var($_GET["inpVal"], 513) . "%";

        require_once "admin/connection.php";

        $getSuggestions = $con->prepare("SELECT itemName FROM items WHERE itemName LIKE :inpVal LIMIT 10");
        $getSuggestions->bindParam(":inpVal", $inpVal);
        $getSuggestions->execute();

        echo json_encode($getSuggestions->fetchAll(PDO::FETCH_COLUMN));

    }