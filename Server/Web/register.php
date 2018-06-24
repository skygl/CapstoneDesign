<?php
$pageTitle = '등록';
include('header.php');
require("db-connect.php");
if(!isset($_COOKIE['teacher'])){
    $query = "SELECT NULL FROM user WHERE class='administrator' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $firstAccess = (mysqli_num_rows($result) == 0);
    if (!$firstAccess) {
        echo '교수님만 새로운 교수님과 학생을 등록할 수 있습니다.';
        $conn->close();
        include('footer.php');
        exit;
    }
} else {
    $firstAccess = false;
}
?>
<form method="post" action="<?php echo($_SERVER['PHP_SELF'])?>">
    <div class="form-group">
        <label for="name"> 이름 </label>
        <input type="text" name="name" class="form-control" placeholder="Firstname Lastname" required>
    </div>

    <div class="form-group">
        <label for="id"> 학번 </label>
        <input type="text" name="id" class="form-control" placeholder="2000123456" required>
    </div>

    <div class="form-group">
        <label for="email"> 이메일 </label>
        <input type="email" name="email" class="form-control" placeholder="username@gmail.com" required>
    </div>
    <?php if ($firstAccess) { ?>
        <div class="form-group">
            <label for="role">Role:</label><br>
             Administrator
            <input type="hidden" name="role" value="teacher">
            <input type="hidden" name="class" value="Administrator">
        </div>
    <?php } else { ?>
    <div class="form-group">
        <label for="class">수업 선택:</label>
        <select class="form-control" name="class">
        <?php
            // Get list of available classes.
                $query = "SELECT * FROM class ORDER BY name";
            $classes = mysqli_query($conn, $query);
            if($classes){
                while($class = $classes->fetch_assoc()){
                    echo '<option value="' . $class['name'] . '">' . $class['name'] . '</option>';
                }
            } else{
                echo '<option value="-1" disabled>No classes defined</option>';
            }
        ?>
        </select>
    </div>

    <div class="form-group">
        <label for="role">구분:</label>
        <select class="form-control" name="role">
            <option value="student">학생</option>
            <option value="teacher">교수</option>
        </select>
    </div>
    <?php } ?>
    <input type="submit" name="register" class="btn btn-primary" value="등록">
</form>
<?php
if(isset($_POST['register']) && !empty($_POST['email'])){

    $id = $_POST['id'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $class = $_POST['class'];
    $role = $_POST['role'];

    if($role == 'student'){
      ###
	$cmd = "mkdir /home/jh/aligned_image/$id";
 	echo exec($cmd);
  ###
	$cmd = "chmod 777 /home/jh/aligned_image/$id";
	echo exec($cmd);
  ###
	$cmd = "sudo python3 /home/jh/register.py $id";
	echo exec($cmd, $output, $error);
	//print_r($output);
	//print_r($error);

	echo "complete<br>";
    }


    // Prepare query to be saved to db.
    // start the single quotes, end the double quotes
    // after concatenation start double quotes again closed before concat and close the single
    // quotes too, then close the outer most double quotes
    $query = "INSERT INTO user(`id`,`fullname`, `email`, `class`, `role`) VALUES(".$id.",'".$name."', '".$email."', '".$class."', '".$role."')";
    if(mysqli_query($conn, $query)){
        if ($role == 'teacher') {
            //redirect user to login page
            header('Location: logout.php');
        } else { // $role == student
            //redirect user to login page
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
    } else {
        echo "에러: " . $query . "<br>" . mysqli_error($conn);
    }
}
$conn->close();
include('footer.php');
