<nav class="navbar navbar-inverse" style="border-radius: 0px;">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">출석체크 시스템</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li <?php echo $pageTitle=="Take Attendance"||$pageTitle=="Take Attendance" ? 'class="active"' : ''; ?>><a href="index.php">홈</a></li>
                <li <?php echo $pageTitle=="Register" ? 'class="active"' : ''; ?>><a href="register.php">등록</a></li>
                <li <?php echo $pageTitle=="Classes" ? 'class="active"' : ''; ?>><a href="classes.php">수업 관리</a></li>
                <li <?php echo $pageTitle=="Report" ? 'class="active"' : ''; ?>><a href="report.php">출석 현황</a></li>
            </ul>
                <ul class="nav navbar-nav navbar-right" style="margin-right:25px;">
                <?php
                    if(isset($_COOKIE['student']) || isset($_COOKIE['teacher']) || isset($_COOKIE['login'])){
                        echo '<li><a href="logout.php" id="loginout" class="btn btn-default">로그아웃</a></li>';
                    } else {
                        echo '<li><a href="login.php" id="loginout" class="btn btn-default">로그인</a></li>';
                    }
                ?>
            </ul>

        </div><!--/.navbar-collapse -->
    </div>
</nav>
