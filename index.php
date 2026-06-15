<?php

$is_hero = true;

$page_title = 'Home';

$body_class = 'page-hero';

include 'includes/header.php';

?>



<div class="hero">



    <video autoplay muted loop playsinline>

        <source src="https://res.cloudinary.com/dfonotyfb/video/upload/v1775585556/dds3_1_rqhg7x.mp4" type="video/mp4">

    </video>



    <div class="overlay"></div>



    <div class="hero-content">

        <div class="eyebrow anim-in">Learnify · Tutor Platform</div>



        <h1 class="title anim-in anim-delay-1">

            Learn <span id="hero-rotate" class="hero-rotate">Without Limits</span>

        </h1>



        <p class="subtitle anim-in anim-delay-2">

            A cinematic platform connecting students with expert tutors — book sessions, track progress, grow without boundaries.

        </p>



        <a href="<?php echo page_url('auth/login.php'); ?>" class="btn btn-shimmer anim-in anim-delay-3">Enter Platform</a>



        <p class="hero-meta anim-in anim-delay-3">SYS.LEARNIFY · 48.8566°N · EST. 2026</p>

    </div>

</div>



<?php include 'includes/footer.php'; ?>


