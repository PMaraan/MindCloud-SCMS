<?php
// Dummy user data (replace with DB query)
//$user = [
  //  "name" => "John Doe",
  //  "profile_picture" => "#"
//];

//$message = "";

// Handle form submission
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 //   $user['name'] = $_POST['name'];

    // Password update
   // if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
     //   if ($_POST['password'] === $_POST['confirm_password']) {
       //     $message = "Password updated successfully.";
        //} else {
          //  $message = "Passwords do not match!";
        //}
    //}

    // Profile picture upload
    //if (!empty($_FILES['profile_picture']['name'])) {
      //  $targetDir = "uploads/";
        //if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

//        $fileName = basename($_FILES["profile_picture"]["name"]);
   //     $targetFilePath = $targetDir . $fileName;

//        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
  //          $user['profile_picture'] = $targetFilePath;
    //    }
    //}

 //   if (empty($message)) {
   //     $message = "Account updated successfully!";
    //}
//}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link rel="stylesheet" href="../../public/assets/css/Settings.css">
</head>
<body>
    <div class="settings-header">
        <h2>Account Settings</h2>
    </div>

    <?php if (!empty($message)): ?>
        <p class="success-msg"><?= $message ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="settings-form">
        <div class="profile-section">
            <img src="<?= $user['profile_picture'] ?>" alt="Profile Picture" class="profile-img" id="profilePreview">
            <label class="upload-btn">
                Change Picture
                <input type="file" name="profile_picture" id="profilePicture" accept="image/*">
            </label>
        </div>

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?= $user['name'] ?>" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="Enter new password">
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password">
        </div>

        <button type="submit" class="save-btn">Save Changes</button>
    </form>

    <script src="../../public/assets/js/Settings.js"></script>
</body>
</html>
