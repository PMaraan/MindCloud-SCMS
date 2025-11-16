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

<!-- Slideshow Container -->
<div class="slide-container">
    <div class="slides" id="slidesWrapper">
        <div class="slide" style="background-image: url('assets/images/lpu-building-2.jpg');">
            <h1>Welcome to the LPU-SCMS</h1>
            <p>The Lyceum of the Philippines University's Syllabus Content Management System</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-1.jpg');">
            <h1>Centralize.</h1>
            <p>Manage and create all syllabi seamlessly in one unified system.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-2.jpg');">
            <h1>Standardize.</h1>
            <p>Ensure consistency across all courses with ready-to-use, university-approved syllabus templates.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-3.jpg');">
            <h1>Create.</h1>
            <p>Easily build syllabi for your courses using an intuitive, guided interface.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-4.jpg');">
            <h1>Review and Annotate.</h1>
            <p>Collaborate with faculty and provide feedback directly on the syllabus for improved accuracy and quality.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-5.jpg');">
            <h1>Automate Approvals.</h1>
            <p>Each step moves the syllabus along the approval chain, ensuring timely completion.</p>
        </div>
        <div class="slide" style="background-image: url('assets/images/lpu-6.jpg');">
            <h1>Monitor and Track.</h1>
            <p>Stay informed with real-time status updates and generate reports on all syllabi across the university.</p>
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

<!-- Alternating Text Full-Screen Sections -->
<div class="screens">

    <section class="screen" style="background-image: url(assets/images/type.png);">
        <div class="content-wrapper">
            <div class="text-block">
                <h2>Academic Affairs Office</h2>
                <p>Create templates and issue them to the colleges.</p>
            </div>
        </div>
    </section>

    <section class="screen" style="background-image: url(assets/images/collaboration.png);">
        <div class="content-wrapper">
            <div class="text-block">
                <h2>Colleges</h2>
                <p>Work with the syllabus collaborators and create high-quality standardized syllabus.</p>
            </div>
        </div>
    </section>

    <section class="screen" style="background-image: url(assets/images/review-1.png);">
        <div class="content-wrapper">
            <div class="text-block">
                <h2>Academic Resource Center</h2>
                <p>Review the Syllabus References.</p>
            </div>
        </div>
    </section>

    <section class="screen" style="background-image: url(assets/images/review-2.png);">
        <div class="content-wrapper">
            <div class="text-block">
                <h2>Industry Advisory Board</h2>
                <p>Review to ensure that all syllabus are up to industry standards.</p>
            </div>
        </div>
    </section>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="landing_page.js"></script>
</body>
</html>
