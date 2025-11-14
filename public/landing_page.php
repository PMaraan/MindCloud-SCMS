<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LPU-SCMS Landing Page</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="landing_page.css">
</head>
<body>

<div class="slide-container">

    <!-- Slides wrapper -->
    <div class="slides" id="slidesWrapper">
        <div class="slide" style="background-image: url('assets/images/lpu-building-2.jpg');">
            <h1>LPU-SCMS</h1>
            <p>The Lyceum of the Philippines University – Syllabus Content Management System streamlines the creation, review, and approval of academic syllabi.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/coecsa-building.jpg');">
            <h1>Efficient. Centralized. Collaborative.</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sit amet dapibus justo. Sed dignissim.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-building.jpg');">
            <h1>Redefining Syllabus Management.</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi auctor nulla urna, at pulvinar est porttitor non.</p>
        </div>
    </div>

    <!-- Fixed button -->
    <a href="https://your-lpu-scms-link.com" class="btn btn-danger btn-lg shadow-lg px-4 py-2 fixed-btn">Enter LPU-SCMS</a>

    <!-- Arrows -->
    <button class="slide-arrow left" id="prevSlide">&#10094;</button>
    <button class="slide-arrow right" id="nextSlide">&#10095;</button>

    <!-- Indicators -->
    <div class="slide-indicators" id="indicatorContainer"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="landing_page.js"></script>
</body>
</html>
