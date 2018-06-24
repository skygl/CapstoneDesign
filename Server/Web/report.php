<?php
$pageTitle = '출석 현황';
include('header.php');
require("db-connect.php");
if (isset($_COOKIE['teacher']) && $_COOKIE['teacher'] == 1 && isset($_COOKIE['login'])) {
    // Nothing to do - just keep on truckin'.
} else { // Not a teacher? You're out of here.
    $conn->close();
    echo '교수님만 출석현황을 관리할 수 있습니다.';
    include('footer.php');
    exit;
}

$currentUser = $_COOKIE['currentUser'];
echo "로그인 된 계정 : ".$currentUser."";
echo "\n 담당 수업 : ";
$currentclassQuery = "select class from user where email = '".$currentUser."'";
$classresult = $conn->query($currentclassQuery);
if(mysqli_num_rows($classresult) > 0){
    while($row = $classresult->fetch_assoc()){
        echo $row['class'];
    }
}
$currentUserId = (isset($_COOKIE['loginId']))? $_COOKIE['loginId'] : 1;


$action = '';
if(isset($_POST['action']))$action = $_POST['action'];
if($action == 'form_submit') {
    if(($_POST['isPresent'] == "0")){
    $query4 = 'update attendance set isPresent = 0 where session = '.$_POST['session'].' and studentid = '.$_POST['user'].' and classid in (select id from class where name = (select class from user where email = "'.$currentUser.'"))';
    $conn->query($query4);
    } else if (($_POST['isPresent'] == "1")){
    $query5 = 'update attendance set isPresent = 1 where session = '.$_POST['session'].' and studentid = '.$_POST['user'].' and classid in (select id from class where name = (select class from user where email = "'.$currentUser.'"))';
    $conn->query($query5);
    }
}


$studentAttendanceQuery = "SELECT fullname,studentid,email,class,session,isPresent FROM attendance join user on attendance.studentid = user.id join class on attendance.classid = class.id where user.role='student' and class in (select class from user where email = '".$currentUser."')";
$totalAttendance = (mysqli_num_rows($conn->query($studentAttendanceQuery)));

$studentAttendanceQuery = "SELECT isPresent FROM attendance join user on attendance.studentid = user.id join class on attendance.classid = class.id where user.role='student' and class in (select class from user where email = '".$currentUser."') and isPresent=1";
$studentAttendance =  (mysqli_num_rows($conn->query($studentAttendanceQuery)));

$query = "SELECT fullname,studentid,email,class,session,isPresent FROM attendance join user on attendance.studentid = user.id join class on attendance.classid = class.id where user.role='student' and class in (select class from user where email = '".$currentUser."')";
$result = $conn->query($query);

?>
<table class="table table-striped">
    <thead>
    <tr>
        <th>이름</th>
        <th>학번</th>
        <th>이메일</th>
        <th>수업</th>
        <th>수업 차수</th>
        <th>출석</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if(mysqli_num_rows($result) > 0){
        while($row = $result->fetch_assoc()){ ?>
            <tr>
                <td><?php echo $row['fullname'];?></td>
                <td><?php echo $row['studentid'];?></td>
                <td><?php echo $row['email'];?></td>
                <td><?php echo $row['class'];?></td>
                <td><?php echo $row['session'];?></td>
                <td><?php echo (($row['isPresent']==1)?"O":"X");?></td>
            </tr>
        <?php }
    }
    ?>

    </tbody>
    <div class="bg-success"><h2><?php echo (empty($totalAttendance)?"100":($studentAttendance/$totalAttendance*100))."%";?></h2></div>

</table>


<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
    <input type="hidden" name="action" value="form_submit" />
    <div class="form-group">
        <label for="user">학생 선택:</label>
        <select class="form-control" name="user">
<?php
 $query2 = "select distinct user.id from user join attendance on attendance.studentid=user.id join class on attendance.classid = class.id where user.role = 'student' and class.id in (select class.id from attendance join user on attendance.studentid = user.id join class on attendance.classid = class.id where user.role = 'student' and class in (select class from user where email = '".$currentUser."'))";
            $users = mysqli_query($conn, $query2);
            if($users){ 
                while($user = $users->fetch_assoc()){
                    echo '<option value="' . $user['id'] . '">' . $user['id'] . '</option>';
                }
            } else{
                echo '<option value="-1" disabled>No users defined</option>';
            }
?>
        </select>
    </div>
    <div class="form-group">
        <label for="session">수업 차수 선택:</label>
        <select class="form-control" name="session">
        <?php
            // Get list of available classes.
                $query3 = "select distinct session from user join attendance on attendance.studentid=user.id join class on attendance.classid = class.id where user.role = 'student' and class.id in (select class.id from attendance join user on attendance.studentid = user.id join class on attendance.classid = class.id where user.role = 'student' and class in (select class from user where email = '".$currentUser."'))";
            $sessions = mysqli_query($conn, $query3);
            if($sessions){ 
                while($session = $sessions->fetch_assoc()){
                    echo '<option value="' . $session['session'] . '">' . $session['session'] . '</option>';
                }
            } else{
                echo '<option value="-1" disabled>No sessions defined</option>';
            }
        ?>
        </select>
    </div>
    <div class="form-group">
        <label for="isPresent">구분 : </label>
        <label><input type="radio" name ="isPresent" value="1" />출석  </label>
        <label><input type="radio" name ="isPresent" value="0" />결석  </label>
        
    </div>
    <input type="submit" name="modify" class="btn btn-primary" value="수정">
</form>

<?php
$conn->close();
include('footer.php');
