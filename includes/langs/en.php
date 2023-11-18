<?php
    function lang($phrase) {
        static $phrases = array(
            // index page "login page"
            "admin_login" => "Admin login",
            "user" => "Username",
            "PHuser" => "Type your username",
            "pass" => "Password",
            "PHpass" => "Type your password",
            "login" => "Login",
            "login_failed" => "Login failed: Uername or password incorrect",

            // Dashboard page
            "dashboard" => "Dashboard",
            "home" => "Home",
            "members" => "Members",
            "categories" => "Categories",
            "items" => "Items",
            "comments" => "Comments",
            "statistics" => "Statistics",
            "logs" => "logs",
            "edit profile" => "Edit profile",
            "settings" => "Settings",
            "logout" => "Logout",

            // Members page
            
                // Edit block
                "edit" => "Eidt",
                "update" => "Update",

                // Add block
                "add" => "Add",
                "insert" => "Insert",

                // Delete block
                "delete" => "Delete",

                // Update block
                "activate" => "Activate"

        );
        return $phrases[$phrase];
    }
