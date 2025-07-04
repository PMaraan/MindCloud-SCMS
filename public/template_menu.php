<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS | Design A Template</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/template_menu.css">
</head>
<body>

  <?php include 'nav-bar.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php include 'side-bar.php'; ?>

      <div class="workspace">
        <div class="container py-5">
          <h4 class="mb-4 fw-bold">Choose a Template</h4>
          <div class="d-flex justify-content-start gap-4 flex-wrap">

            <div class="template-card d-flex flex-column justify-content-center align-items-center">
              <img src="icons/template1.png" class="template-thumbnail mb-2">
              <span class="template-title">ABET</span>
            </div>

            <div class="template-card d-flex flex-column justify-content-center align-items-center">
              <img src="icons/template2.png" class="template-thumbnail mb-2">
              <span class="template-title">PTC</span>
            </div>

            <div class="template-card add-template d-flex justify-content-center align-items-center">
              <span class="display-4 text-muted">+</span>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>

</body>
</html>
