<?php
$pageTitle = "수업 관리";
include('header.php');
require("db-connect.php");
if(!isset($_COOKIE['teacher'])){
    $query = "SELECT NULL FROM user WHERE class='administrator' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $firstAccess = (mysqli_num_rows($result) == 0);
    if (!$firstAccess) {
        echo 'Only teachers can create new classes.';
        $conn->close();
        include('footer.php');
        exit;
    }
} else {
    $firstAccess = false;
}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin-bottom: 25px;">
    <div class="form-group">
        <label for="id"> 수업번호 </label>
        <input type="text" id="id" name="id" class="form-control" placeholder="2000" required>
    </div>
    <div class="form-group">
        <label for="name"> 과목명 </label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Computer Network" required>
    </div>
    <input type="submit" name="classes" class="btn btn-primary" value="저장">
</form>
<?php
if(isset($_POST['classes']) && !empty($_POST['name']) && !empty($_POST['id'])){
    $name = $_POST['name'];
    $id = $_POST['id'];
    // Ensure it doesn't already exist.
    $query = "SELECT 1 FROM class WHERE UPPER(`name`) = UPPER('$name') LIMIT 1";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result)  == 1) {
        echo '<div class="alert alert-danger" role="alert">
          <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
          <span class="sr-only">Error:</span>
          수업 "' . $name . '"이(가) 이미 존재 합니다.
        </div>';
    } else {
        // Save class to database.
        if (strtolower($name) == 'administrator' && !$firstAccess) {
            echo '<div class="alert alert-danger" role="alert">
              <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
              <span class="sr-only">에러:</span>
              해당 수업을 추가할 수 없습니다. "' . $name . '".
            </div>';
        } else {
            $query = "INSERT IGNORE INTO class(`id`,`name`) VALUES('".$id."', '".$name."')";

            if(mysqli_query($conn, $query)){
                header('Location: ' . $_SERVER['PHP_SELF']);
            }
            else{
                echo "에러: " . $query . "<br>" . mysqli_error($conn);
            }
        }
    }
}

// Generate list of classes.
$query = "SELECT * FROM class ORDER BY name;";
$classes = $classes = mysqli_query($conn, $query);
if($classes && mysqli_num_rows($classes)){ ?>
    <div class="panel panel-success">
       <div class = "panel-heading">
           <h2 class = "panel-title">Current classes</h2>
       </div>
       <div class = "panel-body">
           <ul style="list-style: none;">
           <?php
             // Get list of available classes.
             while($class = $classes->fetch_assoc()){
                echo '<li><a href="?delete=' . $class['id'] . '" class="btn btn-danger btn-sm" style="margin:2px;"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span><span class="sr-only">Delete class</span></a> ' . $class['name'] . '</li>';
             }
           ?>
           </ul>
       </div>
   </div>
<?php
} else {
?>
    <div class = "panel panel-warning">
       <div class = "panel-heading">
          <h2 class = "panel-title">Warning</h2>
       </div>
       <div class = "panel-body">
          No classes defined.
       </div>
    </div>
<?php
}

// Delete class name from class table.
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    // Prepare query to be saved to db.
    $query = "DELETE FROM class WHERE `id`='".$id."';";
    if(mysqli_query($conn, $query)){
        $query = "DELETE FROM attendance WHERE `classid`='".$id."';";
        mysqli_query($conn, $query);
        header('Location: ' . $_SERVER['PHP_SELF']);
    } else {
        echo "에러: " . $query . "<br>" . mysqli_error($conn);
    }
}

$conn->close();
include('footer.php');
