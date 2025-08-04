<?php
// Example notifications array
//$notifications = [
  //  [
    //    "id" => 1,
      //  "type" => "unread",
        //"image" => "#",
        //"title" => "VPAA New Template",
        //"message" => "Laborum aliqua do mollit nostrud irure sit nulla duis nisi nulla est...",
        //"time" => "1 minute ago"
    //],
    //[
     //   "id" => 2,
     //   "type" => "unread",
     //   "image" => "#",
     //   "title" => "Professor Prepared Syllabus",
      //  "message" => "Laborum aliqua do mollit nostrud irure sit nulla duis nisi nulla est...",
       // "time" => "1 hour ago"
    //],
    //[
      //  "id" => 3,
       // "type" => "read",
       // "image" => "#",
       // "title" => "Professor sent Revised Syllabus",
       // "message" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
       // "time" => "A few seconds ago"
    //],
    //[
     //   "id" => 4,
     //   "type" => "read",
     //   "image" => "#",
     //   "title" => "Syllabus Approved",
     //   "message" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
     //   "time" => "A few seconds ago"
   // ]
//];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LPU-SCMS Notifications</title>
    <link rel="stylesheet" href="../../public/assets/css/Notification.css">
</head>
<body>

    <div class="notification-container">
        <div class="header">
            <h2>Notifications</h2>
            <div class="tabs">
                <button class="tab-btn active" data-tab="all">All</button>
                <button class="tab-btn" data-tab="unread">Unread</button>
            </div>
        </div>
        <div class="notification-list">
            <?php foreach ($notifications as $note): ?>
                <div class="notification-card <?= $note['type'] ?>" data-id="<?= $note['id'] ?>">
                    <img src="<?= $note['image'] ?>" alt="User Avatar" class="avatar">
                    <div class="notification-content">
                        <strong><?= $note['title'] ?></strong>
                        <p><?= $note['message'] ?></p>
                        <small><?= $note['time'] ?></small>
                    </div>
                    <?php if ($note['type'] === 'unread'): ?>
                        <span class="unread-dot"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="../../public/assets/js/Notification.js"></script>
</body>
</html>
