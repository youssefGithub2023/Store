<?php
    session_start();
    if (isset($_SESSION["username"])) {
        $titleKey = "statistics";
        include "init.inc.php";
    ?>
    <section class="statistics">
        <section class="top">
            <section class="container">

                <section class="top-box">
                    <h2>top views item</h2>
                <?php
                    $topItems = $con->prepare("SELECT views.itemId, itemName, itemPrice, itemImg, COUNT(itemId) AS count FROM views JOIN items USING (itemId) GROUP BY (items.itemId) ORDER BY count DESC LIMIT 7");
                    $topItems->execute();

                    while ($topItem = $topItems->fetch()) {
                ?>
                    <section class="row">
                        <figure>
                            <img src="<?php echo itemImgs . $topItem['itemImg'] ?>">
                        </figure>

                        <section class="info name">
                            <section><a href="items.php?block=details&itemId=<?php echo $topItem['itemId'] ?>"><?php echo $topItem['itemName'] ?></a></section>
                            <p><?php echo $theCurrency . $topItem['itemPrice'] ?></p>
                        </section>

                        <section class="info">
                            <section><i class="fa-solid fa-eye"></i></section>
                            <p><?php echo $topItem['count'] ?></p>
                        </section>

                        <section class="info">
                            <section><i class="fa-solid fa-eye"></i></section>
                            <p>2333</p>
                        </section>

                        <section class="info">
                            <section><i class="fa-solid fa-eye"></i></section>
                            <p>2333</p>
                        </section>
                    </section>
                <?php
                    }
                ?>
                </section>

                <section class="top-box">
                    <h2>top views users</h2>
                    <?php
                        $topUsers = $con->prepare("SELECT items.userId, COUNT(items.userId) AS count, username, profileImgPath FROM `views` JOIN items USING (itemId) JOIN users ON items.userId = users.userId GROUP BY (users.userId) ORDER BY count DESC LIMIT 7");
                        $topUsers->execute();

                        while ($topUser = $topUsers->fetch()) {
                    ?>
                        <section class="row">
                            <figure>
                                <img src="<?php echo profileImgs . proImgPath($topUser['profileImgPath'], $topUser['username']) ?>">
                            </figure>

                            <section class="info name">
                                <section>name</section>
                                <p><a href="members.php?only=<?php echo $topUser['userId'] ?>"><?php echo $topUser['username'] ?></a></p>
                            </section>

                            <section class="info">
                                <section><i class="fa-solid fa-eye"></i></section>
                                <p><?php echo $topUser['count'] ?></p>
                            </section>
                        </section>
                    <?php
                        }
                    ?>
                </section>
            </section>
        </section>

        <section class="charts">
            <section class="container">
                <!-- Start categories chart -->
                <section class="chart-stat">
                <?php
                    $inputYearValue = "";
                    $inputMonthValue = "";
                    $inputDayValue = "";

                    if (isset($_GET["filterCats"])) {
                        $year = filter_var($_GET["year"], FILTER_SANITIZE_NUMBER_INT);
                        $month = filter_var($_GET["month"], FILTER_SANITIZE_NUMBER_INT);
                        $day = filter_var($_GET["day"], FILTER_SANITIZE_NUMBER_INT);

                        if (!empty($year)) {
                            $yearCond = "year(watchDate) = $year";
                            $inputYearValue = $year;
                        } elseif (empty($year) && (!empty($month) || !empty($day))) {
                            $currentYear = date("Y");
                            $yearCond = "year(watchDate) = $currentYear";
                            $inputYearValue = $currentYear;
                        } else {
                            // If is all inputs is empty
                            $yearCond = 1;
                            $monthCond = 1;
                            $dayCond = 1;
                        }

                        if ($yearCond != 1) {
                            if (empty($month) && !empty($day)) {
                                $currentMonth = date("m");

                                $monthCond = "month(watchDate) = $currentMonth";
                                $dayCond = "dayofmonth(watchDate) = $day";

                                $inputMonthValue = $currentMonth;
                                $inputDayValue = $day;
                            } elseif (!empty($month) && empty($day)) {
                                $monthCond = "month(watchDate) = $month";
                                $dayCond = 1;

                                $inputMonthValue = $month;
                            } elseif (empty($month) && empty($day)) {
                                $monthCond = 1;
                                $dayCond = 1;
                            } elseif (!empty($month) && !empty($day)) {
                                $monthCond = "month(watchDate) = $month";
                                $dayCond = "dayofmonth(watchDate) = $day";

                                $inputMonthValue = $month;
                                $inputDayValue = $day;
                            }
                        }

                    } else {
                        $yearCond = 1;
                        $monthCond = 1;
                        $dayCond = 1;
                    }

                    $statCats = $con->prepare("SELECT categories.catId, catName, COUNT(catName) AS count FROM `views` JOIN items USING (itemId) JOIN categories USING (catId) WHERE $yearCond AND $monthCond AND $dayCond GROUP BY categories.catId ORDER BY count DESC");
                    $statCats->execute();
                ?>
                    <form id="filter-cats" class="filter-stat" method="get">
                        <input type="number" name="year" placeholder="year" value="<?php echo $inputYearValue ?>">
                        <input type="number" name="month" placeholder="Month" value="<?php echo $inputMonthValue ?>">
                        <input type="number" name="day" placeholder="Day" value="<?php echo $inputDayValue ?>">
                        <button class="btn" type="submit" name="filterCats"><i class="fa-solid fa-filter"></i> filter</button>
                    </form>
                    <!-- <section class="totalSec">
                        <span class="totalText">Total views = </span> <span class="totalResult"><?php // echo totalRecords("views", "itemId") ?></span>
                    </section> -->

                    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

                    <script type="text/javascript">
                        google.charts.load('current', {'packages':['corechart']});
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {

                            var data = google.visualization.arrayToDataTable([
                                ['Task', 'statistics of categories']
                                <?php
                                while ($statCat = $statCats->fetch()) {
                                    echo ", ['" . $statCat['catName'] . "', " . $statCat['count'] . "]";
                                }
                                ?>
                            ]);

                            var options = {
                                title: 'Statistics of categories',
                                fontSize: 14,
                                fontName: "arial",
                                backgroundColor: "transparent"
                            };

                            var chart = new google.visualization.PieChart(document.getElementById('piechart'));

                            chart.draw(data, options);
                        }
                    </script>
                    <div id="piechart"></div>
                </section>
                <!-- End categories chart -->

                <!-- Start views chart -->
                <section class="chart-stat">
                <?php
                    if (isset($_GET["filterViews"]) && $_GET["viewsBy"] != "0") {

                        $opValue = $_GET["viewsBy"];

                        if ($opValue == "1") {
                            $viewsChart = $con->prepare("SELECT CONCAT(DAYOFMONTH(watchDate), '/', MONTH(watchDate)) AS `date`, COUNT(watchDate) AS count FROM `views` WHERE watchDate >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) GROUP BY watchDate ORDER BY watchDate");
                            $op = $opValue;
                        } elseif ($opValue == "2") {
                            $viewsChart = $con->prepare("SELECT CONCAT(MONTH(watchDate), '/' , SUBSTR(YEAR(watchDate), -2)) AS `date`, COUNT(watchDate) AS count FROM `views` GROUP BY YEAR(watchDate), MONTH(watchDate) ORDER BY watchDate");
                            $op = $opValue;
                        } elseif ($opValue == "3") {
                            $viewsChart = $con->prepare("SELECT YEAR(watchDate) AS `date`, COUNT(watchDate) AS count FROM views GROUP BY YEAR(watchDate) ORDER BY watchDate");
                            $op = $opValue;
                        }

                    } else {
                        $viewsChart = $con->prepare("SELECT CONCAT(DAYOFMONTH(watchDate), '/', MONTH(watchDate)) AS `date`, COUNT(watchDate) AS count FROM `views` WHERE watchDate >= DATE_SUB(CURRENT_DATE(), INTERVAL 10 DAY) GROUP BY watchDate ORDER BY watchDate");
                        $op = "0";
                    }

                    $viewsChart->execute();
                ?>
                    <form class="filter-stat" method="get">
                        <select name="viewsBy">
                            <option value="0" <?php echo $op == "0" ? "selected" : "" ?>>Last 10 days:</option>
                            <option value="1" <?php echo $op == "1" ? "selected" : "" ?>>Last 30 days</option>
                            <option value="2" <?php echo $op == "2" ? "selected" : "" ?>>Months</option>
                            <option value="3" <?php echo $op == "3" ? "selected" : "" ?>>Years</option>
                        </select>
                        <button class="btn" type="submit" name="filterViews"><i class="fa-solid fa-filter"></i> filter</button>
                    </form>

                    <script type="text/javascript">
                        google.charts.load('current', {'packages':['corechart']});
                        google.charts.setOnLoadCallback(drawChart);
                
                        function drawChart() {
                            var data = google.visualization.arrayToDataTable([
                                ['Date', 'Views']
                                <?php
                                while ($viewChart = $viewsChart->fetch()) {
                                    echo ", ['" . $viewChart['date'] . "', " . $viewChart['count'] . "]";
                                }
                                ?>
                            ]);

                            var options = {
                                title: 'Views',
                                curveType: 'none',
                                colors: ["#ff00ff"],
                                fontSize: 14,
                                fontName: "arial",
                                backgroundColor: "transparent",
                                legend: { position: 'bottom' }
                            };
                    
                            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
                    
                            chart.draw(data, options);
                        }
                    </script>
                    <div id="curve_chart"></div>
                </section>
                <!-- End views chart -->
            </section>
        </section>
    </section>
    <?php
        include tpl . "footer.inc.php";
    } else {
        header("location: index.php");
        exit();
    }