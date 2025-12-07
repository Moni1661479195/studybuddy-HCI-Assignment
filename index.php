<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Study Buddy</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/modern_auth.css?v=1.0.3"> 
  <link rel="stylesheet" href="assets/css/modal.css?v=1.0.3">
  <link rel="stylesheet" href="assets/css/footer.css?v=1.0.1">

  <style>

    body {
      font-family: 'Inter', sans-serif;
    }
    html {
      scroll-behavior: smooth;
    }
    .feature-card {
      transition: transform 0.4s ease, box-shadow 0.4s ease;
    }
    .feature-card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    #stats-section {
      background-image: linear-gradient(rgba(29, 78, 216, 0.85), rgba(29, 78, 216, 0.85)), 
                        url('https://images.unsplash.com/photo-1519389950473-47ba0277781c');
      background-attachment: fixed;
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;
    }
    
    .faq-answer {
      transition: max-height 0.3s ease-out;
    }

 
    .bubbles-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }
    .bubble {
        position: absolute;
        bottom: -100px;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        animation: rise 15s infinite ease-in;
    }
    .bubble:nth-child(1) { width: 40px; height: 40px; left: 10%; animation-duration: 8s; }
    .bubble:nth-child(2) { width: 20px; height: 20px; left: 20%; animation-duration: 5s; animation-delay: 1s; }
    .bubble:nth-child(3) { width: 50px; height: 50px; left: 35%; animation-duration: 7s; animation-delay: 2s; }
    .bubble:nth-child(4) { width: 80px; height: 80px; left: 50%; animation-duration: 11s; animation-delay: 0s; }
    .bubble:nth-child(5) { width: 35px; height: 35px; left: 55%; animation-duration: 6s; animation-delay: 1s; }
    .bubble:nth-child(6) { width: 45px; height: 45px; left: 65%; animation-duration: 8s; animation-delay: 3s; }
    .bubble:nth-child(7) { width: 25px; height: 25px; left: 80%; animation-duration: 6s; animation-delay: 2s; }

    @keyframes rise {
        0% { bottom: -100px; transform: translateX(0); }
        50% { transform: translateX(100px); }
        100% { bottom: 100%; transform: translateX(-200px); }
    }



/* --- 1. Add this CSS to fix the modal position --- */
.modal-overlay {
    position: fixed; /* Key: Makes it float on top of all content */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black background */
    
    /* display: none; <-- This is the default, JS will override it */
    
    /* We removed align-items: center and added overflow-y: auto */
    overflow-y: auto;
    padding: 4rem 0; /* Add some space at the top and bottom */
    justify-content: center;
    display: none;
    z-index: 1000; /* Ensure it's on the very top layer */
    backdrop-filter: blur(5px); /* Blurs the background (optional) */
    animation: fadeIn 0.3s ease-out; /* Animates the black background fade-in */
}

/* This is the container for the form loaded by JS */
.modal-content {
    position: relative;
    width: 90%; /* On small screens, use 90% width */
    max-width: 460px; /* Max width of the modal box */
    animation: scaleUp 0.3s ease-out; /* Animates the white card scaling up */
}

/* When modal is active, prevent the background page from scrolling */
body.modal-active {
    overflow: hidden;
}

/* Styling for the modal close button */
.modal-close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 1.8rem; /* Make it larger */
    color: #6c757d; /* A subtle dark grey */
    cursor: pointer;
    padding: 5px;
    line-height: 1;
    z-index: 10; /* Ensure it's above other content */
    transition: color 0.2s ease;
}

.modal-close-btn:hover {
    color: #343a40; /* Darker on hover */
}


/* --- 1. Add these new @keyframes rules (at the end of your <style> block) --- */
@keyframes fadeIn {
    /* Animation for the black background */
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes scaleUp {
    /* Animation for the white login card */
    from {
        opacity: 0;
        transform: scale(0.95); /* Start slightly smaller and transparent */
    }
    to {
        opacity: 1;
        transform: scale(1); /* End at full size and visible */
    }
}

  </style>
</head>
<body class="bg-gray-50 text-gray-800">

<div id="main-content">
    <?php include 'header.php'; ?>

    <section class="relative h-[45vh] flex items-center justify-center text-center bg-blue-700 text-white mt-16">
        <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover brightness-50">
          <source src="https://cdn.pixabay.com/vimeo/331381734/education-22437.mp4?width=1280&hash=0f17bb8b7b6928ab281c5d4b01f567d73388f6cd" type="video/mp4">
        </video>

        <div class="bubbles-container">
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
        </div>

        <div class="relative z-10 px-6">
          <h1 class="text-4xl sm:text-5xl font-bold mb-4" data-aos="fade-up">Your Personalized Learning Companion</h1>
          <span id="typing-text" class="text-lg sm:text-xl font-medium block mb-6 text-blue-200"></span>
          <div class="hero-ctas">
                <?php if (!isset($_SESSION['user_id'])): ?>
                  <a href="#features" class="bg-white text-blue-700 font-semibold px-6 py-3 rounded-full hover:bg-blue-100 transition">Explore Features</a>

                <?php else: ?>
                     <a href="dashboard.php" class="bg-white text-blue-700 font-semibold px-6 py-3 rounded-full hover:bg-blue-100 transition">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="features" class="text-center py-20 px-4 bg-white">
        <h2 class="text-3xl font-bold mb-6 text-gray-800" data-aos="fade-up">Why Choose Study Buddy?</h2>
        <p class="text-gray-600 mb-12 max-w-xl mx-auto" data-aos="fade-up" data-aos-delay="200">
          We provide everything you need to succeed in your academic journey, all in one place.
        </p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
          <div class="bg-gradient-to-br from-blue-50 to-purple-100 rounded-2xl shadow feature-card overflow-hidden text-left" data-aos="zoom-in">
            <div class="relative">
              <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Study Planning" class="w-full h-48 object-cover">
              <div class="absolute -bottom-5 -right-5 w-20 h-20 bg-blue-200 rounded-full opacity-50"></div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold mb-2 text-blue-800">Personalized Study Plans</h3>
              <p class="text-gray-700">Create custom study schedules tailored to your goals and learning style.</p>
            </div>
          </div>
          <div class="bg-gradient-to-br from-green-50 to-blue-100 rounded-2xl shadow feature-card overflow-hidden text-left" data-aos="zoom-in" data-aos-delay="200">
            <div class="relative">
              <img src="https://images.unsplash.com/photo-1516321497487-e288fb19713f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Interactive Quizzes" class="w-full h-48 object-cover">
              <div class="absolute -bottom-5 -right-5 w-20 h-20 bg-green-200 rounded-full opacity-50"></div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold mb-2 text-green-800">Interactive Quizzes</h3>
              <p class="text-gray-700">Test your knowledge and reinforce learning with engaging, custom-built quizzes.</p>
            </div>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-pink-100 rounded-2xl shadow feature-card overflow-hidden text-left" data-aos="zoom-in" data-aos-delay="400">
            <div class="relative">
              <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Collaborative Groups" class="w-full h-48 object-cover">
              <div class="absolute -bottom-5 -right-5 w-20 h-20 bg-purple-200 rounded-full opacity-50"></div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold mb-2 text-purple-800">Collaborative Groups</h3>
              <p class="text-gray-700">Connect with peers, share knowledge, and learn together effectively in study groups.</p>
            </div>
          </div>
        </div>
    </section>

    <section id="app-features" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-3xl font-bold text-center mb-12 text-gray-800" data-aos="fade-up">Powerful Tools, Simple Interface</h2>
          <div class="flex flex-col md:flex-row items-center gap-12 mb-16" data-aos="fade-right">
            <div class="md:w-1/2">
              <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Progress Analytics Dashboard" class="rounded-lg shadow-xl w-full">
            </div>
            <div class="md:w-1/2">
              <span class="text-blue-600 font-semibold">ANALYTICS</span>
              <h3 class="text-2xl font-bold text-gray-800 mb-4 mt-2">Track Your Progress Visually</h3>
              <p class="text-gray-600 mb-4">
                Visualize your learning journey with our intuitive analytics dashboard. See your quiz scores, study time, and subject mastery all in one place.
              </p>
              <ul class="space-y-2 text-gray-600">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Detailed charts for scores and time
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Identify strengths and weaknesses
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Set and track weekly goals
                </li>
              </ul>
            </div>
          </div>
          <div class="flex flex-col md:flex-row-reverse items-center gap-12" data-aos="fade-left">
            <div class="md:w-1/2">
              <img src="https://images.unsplash.com/photo-1614332287897-cdc485fa562d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Smart Notifications on Mobile" class="rounded-lg shadow-xl w-full">
            </div>
            <div class="md:w-1/2">
              <span class="text-purple-600 font-semibold">STAY ON TRACK</span>
              <h3 class="text-2xl font-bold text-gray-800 mb-4 mt-2">Smart Notifications</h3>
              <p class="text-gray-600 mb-4">
                Never miss a deadline or study session. Our smart reminders keep you accountable and motivated, delivering the right info at the right time.
              </p>
              <ul class="space-y-2 text-gray-600">
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Upcoming quiz and assignment alerts
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Reminders for scheduled study blocks
                </li>
                <li class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  Motivational quotes and study tips
                </li>
              </ul>
            </div>
          </div>
        </div>
    </section>

    <section id="stats-section" class="py-20 text-white text-center">
        <div class="grid sm:grid-cols-3 gap-10 max-w-5xl mx-auto">
          <div>
            <h3 class="text-4xl font-bold" data-count="10000">0</h3>
            <p>Active Students</p>
          </div>
          <div>
            <h3 class="text-4xl font-bold" data-count="500">0</h3>
            <p>Universities</p>
          </div>
          <div>
            <h3 class="text-4xl font-bold" data-count="95">0</h3>
            <p>Satisfaction Rate (%)</p>
          </div>
        </div>
    </section>

    <section class="bg-gray-100 py-20">
        <h2 class="text-3xl font-bold text-center mb-10 text-gray-800" data-aos="fade-up">What Students Say</h2>
        <div class="mx-auto">
            <div class="swiper mySwiper">
              <div class="swiper-wrapper">
                <div class="swiper-slide bg-white p-8 rounded-2xl shadow-md text-center">
              <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400" alt="Headshot of Aisha" class="w-24 h-24 rounded-full mx-auto mb-5 object-cover shadow-md" />
              <p class="italic text-gray-700">"Study Buddy helped me manage my time and study more efficiently!"</p>
              <h4 class="font-semibold mt-4 text-blue-700">– Aisha, Biotechnology Student</h4>
            </div>
            <div class="swiper-slide bg-white p-8 rounded-2xl shadow-md text-center">
              <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400" alt="Headshot of Amir" class="w-24 h-24 rounded-full mx-auto mb-5 object-cover shadow-md" />
              <p class="italic text-gray-700">"The quizzes are so engaging — it makes learning actually fun!"</p>
              <h4 class="font-semibold mt-4 text-blue-700">– Amir, Computer Science Student</h4>
            </div>
            <div class="swiper-slide bg-white p-8 rounded-2xl shadow-md text-center">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400" alt="Headshot of Mei Ling" class="w-24 h-24 rounded-full mx-auto mb-5 object-cover shadow-md" />
              <p class="italic text-gray-700">"I met new friends through the group feature — it’s awesome!"</p>
              <h4 class="font-semibold mt-4 text-blue-700">– Mei Ling, Psychology Student</h4>
            </div>
          </div>
          <div class="swiper-pagination mt-6"></div>
        </div>
    </section>

    <section id="gallery" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-3xl font-bold text-center mb-12 text-gray-800" data-aos="fade-up">Find Your Focus</h2>
          <p class="text-gray-600 mb-12 max-w-xl mx-auto text-center" data-aos="fade-up" data-aos-delay="100">
            Study Buddy works wherever you do. See how students learn in different environments.
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="overflow-hidden rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="0">
              <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Library" class="w-full h-80 object-cover transition-transform duration-500 ease-in-out hover:scale-110">
            </div>
            <div class="overflow-hidden rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="150">
              <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Collaborative Space" class="w-full h-80 object-cover transition-transform duration-500 ease-in-out hover:scale-110">
            </div>
            <div class="overflow-hidden rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="300">
              <img src="https://images.unsplash.com/photo-1517842645767-c639042777db?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Home Desk" class="w-full h-80 object-cover transition-transform duration-500 ease-in-out hover:scale-110">
            </div>
            <div class="overflow-hidden rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="450">
              <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Cafe Study" class="w-full h-80 object-cover transition-transform duration-500 ease-in-out hover:scale-110">
            </div>
          </div>
        </div>
    </section>

    <section id="faq" class="py-20 bg-gray-50"> <div class="max-w-3xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-10 text-gray-800" data-aos="fade-up">Frequently Asked Questions</h2>
        <div class="space-y-4" data-aos="fade-up" data-aos-delay="200">
          <div class="bg-white rounded-lg shadow-sm">
            <button class="faq-toggle w-full flex justify-between items-center text-left p-5 font-semibold text-gray-700 focus:outline-none">
              <span>How does the smart searching algorithm work?</span>
              <svg class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div class="faq-answer hidden p-5 pt-0 text-gray-600">
              <p>Our smart search uses AI to understand your queries. It doesn't just look for keywords; it understands the concept you're looking for. This allows it to connect you with the most relevant study guides, quiz questions, and group discussions from across the platform, helping you find what you need instantly.</p>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-sm">
            <button class="faq-toggle w-full flex justify-between items-center text-left p-5 font-semibold text-gray-700 focus:outline-none">
              <span>Can I create quizzes for my study group?</span>
              <svg class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div class="faq-answer hidden p-5 pt-0 text-gray-600">
              <p>Yes! You can create custom quizzes and share them directly with your collaborative study groups. You can also access quizzes made by other students in your courses.</p>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-sm">
            <button class="faq-toggle w-full flex justify-between items-center text-left p-5 font-semibold text-gray-700 focus:outline-none">
              <span>Is Study Buddy free to use?</span>
              <svg class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div class="faq-answer hidden p-5 pt-0 text-gray-600">
              <p>Yes! Study Buddy is completely free and open-source. All features, including study plans, collaborative groups, and unlimited quizzes, are available to everyone.</p>
            </div>
          </div>
          </div>
      </div>
    </section>

    <section id="demo-video" class="py-20 bg-gray-100">
        <div class="max-w-4xl mx-auto px-4 text-center">
          <h2 class="text-3xl font-bold mb-6 text-gray-800" data-aos="fade-up">See Study Buddy in Action</h2>
          <p class="text-gray-600 mb-8 max-w-xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Watch this short demo to see how you can organize your semester,
            ace your exams, and collaborate with peers.
          </p>
          <div class="aspect-w-16 aspect-h-9 rounded-lg shadow-xl overflow-hidden" data-aos="zoom-in" data-aos-delay="200">
            <iframe 
              class="w-full h-full"
              src="https://www.youtube.com/embed/i-h-Sjpx-2E" title="YouTube video player" 
              frameborder="0" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowfullscreen>
            </iframe>
          </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</div>

<div id="universal-modal" class="modal-overlay">
    <div class="modal-content" id="modal-content-container">
        </div>
</div>

<script>
    
    window.addEventListener("scroll", () => {
      if (window.scrollY > 50) {
        navbar.classList.add("shadow-lg");
      } else {
        navbar.classList.remove("shadow-lg");
      }
    });

    // Mobile menu toggle
    const menuBtn = document.getElementById("menu-btn");
    const mobileMenu = document.getElementById("mobile-menu");
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener("click", () => mobileMenu.classList.toggle("hidden"));
    }

    // Typing animation
    const textArray = ["Plan smarter.", "Learn faster.", "Collaborate better."];
    let index = 0;
    const typingElement = document.getElementById("typing-text");
    function typeText() {
      const text = textArray[index];
      let i = 0;
      typingElement.textContent = "";
      const interval = setInterval(() => {
        typingElement.textContent += text[i];
        i++;
        if (i === text.length) {
          clearInterval(interval);
          setTimeout(() => {
            index = (index + 1) % textArray.length;
            typeText();
          }, 2000);
        }
      }, 100);
    }
    if (typingElement) {
        typeText();
    }

    // Animated counters
    document.querySelectorAll("[data-count]").forEach(el => {
      const update = () => {
        const target = +el.getAttribute("data-count");
        const current = +el.innerText;
        const increment = target / 80;
        if (current < target) {
          el.innerText = Math.ceil(current + increment);
          requestAnimationFrame(update);
        } else el.innerText = target;
      };
      update();
    });

    // Swiper carousel
    new Swiper(".mySwiper", {
      loop: true,
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      slidesPerView: 1,
      pagination: {
        el: ".swiper-pagination",
        clickable: true
      },
    });
    new Swiper(".success-swiper", { 
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".success-pagination",
        clickable: true,
      },
    });

    // AOS animations
    AOS.init({ duration: 1000, once: true });

    
    // --- For FAQ Accordion (来自 fakemainpage.php) ---
    document.querySelectorAll('.faq-toggle').forEach(button => {
      button.addEventListener('click', () => {
        const answer = button.nextElementSibling;
        const allAnswers = document.querySelectorAll('.faq-answer');
        
        allAnswers.forEach(ans => {
          if (ans !== answer && !ans.classList.contains('hidden')) {
            ans.classList.add('hidden');
            ans.previousElementSibling.querySelector('svg').classList.remove('rotate-180');
          }
        });

        answer.classList.toggle('hidden');
        const icon = button.querySelector('svg');
        icon.classList.toggle('rotate-180');
      });
    });


    document.addEventListener('DOMContentLoaded', () => {
        const body = document.body;
        const modalContainer = document.getElementById('universal-modal');
        const modalContentContainer = document.getElementById('modal-content-container');

        const openModal = async (url) => {
            if (!modalContainer || !modalContentContainer) return;
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Failed to load content.');
                const html = await response.text();
                modalContentContainer.innerHTML = html;
                body.classList.add('modal-active');
                modalContainer.style.display = 'flex';
                initializeModalScripts(modalContentContainer);
            } catch (error) {
                console.error('Error loading modal content:', error);
                modalContentContainer.innerHTML = '<div class="auth-card"><p>Error loading content. Please try again.</p></div>';
            }
        };

        const closeModal = (callback) => {
            if (!modalContainer) return;
            body.classList.remove('modal-active');
            setTimeout(() => {
                modalContainer.style.display = 'none';
                modalContentContainer.innerHTML = ''; // Clear content
                if (typeof callback === 'function') {
                    callback();
                }
            }, 400);
        };

        const initializeModalScripts = (container) => {
            container.querySelector('.modal-close-btn')?.addEventListener('click', () => closeModal());
            container.querySelector('.modal-switch')?.addEventListener('click', (e) => {
                e.preventDefault();
                const targetUrl = e.target.dataset.target;
                if (targetUrl) {
                    closeModal(() => openModal(targetUrl));
                }
            });

            const loginForm = container.querySelector('#modalLoginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(loginForm);
                    // 确保 action URL 指向 api 文件夹
                    const response = await fetch('api/ajax_login.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = result.redirect || 'dashboard.php';
                    } else {
                        showMessage('login-modal', result.message);
                    }
                });
            }

            const signupForm = container.querySelector('#modalSignupForm');
            if (signupForm) {
                signupForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(signupForm);
                    const response = await fetch('api/ajax_signup.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        showMessage('signup-modal', result.message, true);
                        // Check for a redirect URL in the response for auto-login
                        if (result.redirect) {
                            setTimeout(() => {
                                window.location.href = result.redirect;
                            }, 1000); // Wait 1 second to show success message before redirecting
                        } else {
                            // Fallback to old behavior if no redirect URL is provided
                            setTimeout(() => {
                                closeModal(() => {
                                    openModal('ajax/login_form.php');
                                });
                            }, 1000);
                        }
                    } else {
                        showMessage('signup-modal', result.message);
                    }
                });
            }

            const modalGetCodeBtn = container.querySelector('#modal-get-code-btn');
            if (modalGetCodeBtn) {
                modalGetCodeBtn.addEventListener('click', async function() {
                    const emailField = container.querySelector('#signup-email');
                    if (!emailField || !emailField.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                        showMessage('signup-modal', 'Please enter a valid email address.');
                        return;
                    }
                    this.disabled = true;
                    this.textContent = 'Sending...';
                    try {
                        const response = await fetch('api/send_verification_code.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: emailField.value })
                        });
                        const data = await response.json();
                        if (data.success) {
                            showMessage('signup-modal', 'Verification code sent! Please check your email.', true);
                            let countdown = 60;
                            this.textContent = `Wait ${countdown}s`;
                            const interval = setInterval(() => {
                                countdown--;
                                this.textContent = `Wait ${countdown}s`;
                                if (countdown <= 0) {
                                    clearInterval(interval);
                                    this.textContent = 'Get Code';
                                    this.disabled = false;
                                }
                            }, 1000);
                        } else {
                            showMessage('signup-modal', data.message || 'Failed to send code.');
                            this.disabled = false;
                            this.textContent = 'Get Code';
                        }
                    } catch (error) {
                        showMessage('signup-modal', 'An error occurred. Please try again.');
                        this.disabled = false;
                        this.textContent = 'Get Code';
                    }
                });
            }
        };

        const showMessage = (modalId, message, isSuccess = false) => {
            const messageDiv = modalContentContainer.querySelector(`#${modalId}-message`);
            if (messageDiv) {
                messageDiv.textContent = message;
                messageDiv.className = isSuccess ? 'success-message' : 'error-message';
                messageDiv.style.display = 'flex';
            }
        };

        // --- 绑定 Modal 按钮 ---
        document.getElementById('nav-signin-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/login_form.php'); });
        document.getElementById('hero-signin-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/login_form.php'); });
        document.getElementById('nav-signup-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/signup_form.php'); });
        document.getElementById('hero-signup-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/signup_form.php'); });
        document.getElementById('mobile-signin-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/login_form.php'); });
        document.getElementById('mobile-signup-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/signup_form.php'); });
        
        document.getElementById('mobile-signin-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/login_form.php'); });
        document.getElementById('mobile-signup-btn')?.addEventListener('click', (e) => { e.preventDefault(); openModal('ajax/signup_form.php'); });


        modalContainer?.addEventListener('click', (e) => {
            if (e.target === modalContainer) closeModal();
        });
        
      
    });
</script>

</body>
</html>