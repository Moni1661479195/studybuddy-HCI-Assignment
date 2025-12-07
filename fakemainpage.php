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


/* --- 干净的泡泡 CSS --- */
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

.bubble:nth-child(1) {
    width: 40px;
    height: 40px;
    left: 10%;
    animation-duration: 8s;
}

.bubble:nth-child(2) {
    width: 20px;
    height: 20px;
    left: 20%;
    animation-duration: 5s;
    animation-delay: 1s;
}

.bubble:nth-child(3) {
    width: 50px;
    height: 50px;
    left: 35%;
    animation-duration: 7s;
    animation-delay: 2s;
}

.bubble:nth-child(4) {
    width: 80px;
    height: 80px;
    left: 50%;
    animation-duration: 11s;
    animation-delay: 0s;
}

.bubble:nth-child(5) {
    width: 35px;
    height: 35px;
    left: 55%;
    animation-duration: 6s;
    animation-delay: 1s;
}

.bubble:nth-child(6) {
    width: 45px;
    height: 45px;
    left: 65%;
    animation-duration: 8s;
    animation-delay: 3s;
}

.bubble:nth-child(7) {
    width: 25px;
    height: 25px;
    left: 80%;
    animation-duration: 6s;
    animation-delay: 2s;
}

@keyframes rise {
    0% {
        bottom: -100px;
        transform: translateX(0);
    }
    50% {
        transform: translateX(100px);
    }
    100% {
        bottom: 100%;
        transform: translateX(-200px);
    }
}


  </style>
</head>

<body class="bg-gray-50 text-gray-800">

  <nav class="bg-blue-600 fixed w-full top-0 z-50 transition-all duration-300" id="navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-8 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <svg class="w-9 h-9" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="logoGradientNav" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#60A5FA" /> <stop offset="100%" stop-color="#E0E7FF" /> </linearGradient>
          </defs>
          <path d="M10 45 L10 5 L30 2 L50 5 L50 45 L30 48 Z" fill="url(#logoGradientNav)" />
          <path d="M10 5 L30 2 L30 48 L10 45 Z" fill="#1D4ED8" />
          <path d="M5 15 L25 5 L45 15 L25 25 Z" fill="#374151" />
          <path d="M20 32 Q 30 38, 40 32" stroke="#1D4ED8" stroke-width="2.5" fill="none" stroke-linecap="round" />
        </svg>
        <h1 class="text-lg sm:text-xl font-bold text-white">Study Buddy</h1>
      </div>

      <div class="hidden md:flex items-center space-x-6">
        <a href="#" class="text-blue-100 hover:text-white transition">Sign In</a>
        <button class="bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition">Sign Up</button>
      </div>

      <button id="menu-btn" class="block md:hidden text-white focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
          viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>

    <div id="mobile-menu" class="hidden md:hidden bg-blue-600 shadow-md">
      <div class="px-4 py-3 space-y-3">
        <a href="#" class="block text-blue-100 hover:text-white transition text-center">Sign In</a>
        <button class="w-full bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition">Sign Up</button>
      </div>
    </div>
  </nav>

  <section class="relative h-[90vh] flex items-center justify-center text-center bg-blue-700 text-white mt-16">    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover brightness-50">
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
      <a href="#features" class="bg-white text-blue-700 font-semibold px-6 py-3 rounded-full hover:bg-blue-100 transition">Explore Features</a>
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

    <div class="swiper mySwiper max-w-4xl mx-auto">
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


  <footer class="bg-gradient-to-r from-indigo-700 to-blue-700 text-white text-center py-12 px-4">
    <h3 class="font-bold text-2xl mb-3">Join Our Learning Community</h3>
    <p class="mb-6 text-base opacity-90">Subscribe to receive study tips and updates from Study Buddy.</p>
    
    <form class="flex flex-col sm:flex-row items-center sm:items-start sm:justify-center gap-3 sm:gap-2 mb-6 max-w-lg mx-auto">
      <input type="email" placeholder="Enter your email" class="p-2 rounded w-full sm:w-2/3 text-black" required />
      <button type="submit" class="bg-white text-blue-700 px-5 py-2 rounded font-semibold hover:bg-blue-100 transition w-full sm:w-auto">
        Subscribe
      </button>
    </form>

    <div class="flex justify-center gap-6 mb-8"> <a href="#" class="opacity-75 hover:opacity-100 transition-transform duration-300 hover:scale-110">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24h-1.918c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.294h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
      </a>
      <a href="#" class="opacity-75 hover:opacity-100 transition-transform duration-300 hover:scale-110">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63a9.935 9.935 0 002.46-2.548z"/></svg>
      </a>
      <a href="#" class="opacity-75 hover:opacity-100 transition-transform duration-300 hover:scale-110">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.784-1.75-1.75s.784-1.75 1.75-1.75 1.75.784 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
      </a>
      <a href="#" class="opacity-75 hover:opacity-100 transition-transform duration-300 hover:scale-110">
         <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.07 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.225-.148-4.771-1.664-4.919-4.919-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919.058-1.265.07-1.644.07-4.849zm0 1.441c-3.116 0-3.486.011-4.71.068-2.783.127-4.013 1.3-4.142 4.142-.057 1.225-.068 1.595-.068 4.71s.011 3.486.068 4.71c.127 2.783 1.3 4.013 4.142 4.142 1.225.057 1.595.068 4.71.068s3.486-.011 4.71-.068c2.783-.127 4.013-1.3 4.142-4.142.057-1.225.068 1.595.068-4.71s-.011-3.486-.068-4.71c-.127-2.783-1.3-4.013-4.142-4.142-1.225-.057-1.595-.068-4.71-.068zm0 3.196c-2.705 0-4.887 2.182-4.887 4.887s2.182 4.887 4.887 4.887 4.887-2.182 4.887-4.887-2.182-4.887-4.887-4.887zm0 7.98c-1.713 0-3.103-1.39-3.103-3.103s1.39-3.103 3.103-3.103 3.103 1.39 3.103 3.103-1.39 3.103-3.103 3.103zm4.616-7.817c-.69 0-1.25.56-1.25 1.25s.56 1.25 1.25 1.25 1.25-.56 1.25-1.25-.56-1.25-1.25-1.25z"/></svg>
      </a>
    </div>

    <div class="flex justify-center items-center gap-2 mb-4">
      <svg class="w-8 h-8" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="logoGradientFooter" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#FFFFFF" />
            <stop offset="100%" stop-color="#E0E7FF" /> </linearGradient>
        </defs>
        <path d="M10 45 L10 5 L30 2 L50 5 L50 45 L30 48 Z" fill="url(#logoGradientFooter)" />
        <path d="M10 5 L30 2 L30 48 L10 45 Z" fill="#60A5FA" /> <path d="M5 15 L25 5 L45 15 L25 25 Z" fill="#E0E7FF" />
        <path d="M20 32 Q 30 38, 40 32" stroke="#60A5FA" stroke-width="2.5" fill="none" stroke-linecap="round" />
      </svg>
      <span class="font-semibold text-lg opacity-90">Study Buddy</span>
    </div>

    <p class="text-xs opacity-70">© 2025 Study Buddy. All rights reserved.</p>
  </footer>

  <script>
    // Navbar scroll effect
    const navbar = document.getElementById("navbar");
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
    menuBtn.addEventListener("click", () => mobileMenu.classList.toggle("hidden"));

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
    typeText();

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
      autoplay: { delay: 2500 },
      pagination: { el: ".swiper-pagination", clickable: true },
    });

    // AOS animations
    AOS.init({ duration: 1000, once: true });

    
    // --- For FAQ Accordion ---
    document.querySelectorAll('.faq-toggle').forEach(button => {
      button.addEventListener('click', () => {
        const answer = button.nextElementSibling;
        const allAnswers = document.querySelectorAll('.faq-answer');
        
        // Close all other answers
        allAnswers.forEach(ans => {
          if (ans !== answer && !ans.classList.contains('hidden')) {
            ans.classList.add('hidden');
            ans.previousElementSibling.querySelector('svg').classList.remove('rotate-180');
          }
        });

        // Toggle the clicked one
        answer.classList.toggle('hidden');
        const icon = button.querySelector('svg');
        icon.classList.toggle('rotate-180');
      });
    });

  </script>

</body>
</html>