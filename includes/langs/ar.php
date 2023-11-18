<script >
    window.addEventListener("load", function () {
        document.body.style.direction = "rtl";
        document.body.style.textAlign = "right";
    });
</script>

<?php
    function lang($phrase) {
        static $phrases = array(
            // index page "login page"
            "admin_login" => "تسجيل دخول المشرف",
            "user" => "إسم المستخدم",
            "PHuser" => "أدخل إسم المستخدم",
            "pass" => "كلمة السر",
            "PHpass" => "أدخل كلمة السر",
            "login_failed" => "فشل تسجيل الدخول: إسم المستخدم أو كلمة المرور غير صحيحة",

            // Dashboard page
            "dashboard" => "لوحة التحكم",
            "home" => "الرئيسية",
            "members" => "الأعضاء",
            "categories" => "الأقسام",
            "items" => "العناصر",
            "comments" => "0",
            "statistics" => "الإحصائيات",
            "logs" => "السجل",
            "edit profile" => "تعديل الملف الشخصي",
            "settings" => "الإعدادات",
            "logout" => "تسجيل الخروج",

            // Edit page
            "edit" = "0"
        );
        return $phrases[$phrase];
    }
