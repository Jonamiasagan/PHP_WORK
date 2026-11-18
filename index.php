<?php
/**
 * Grand Theft Auto VI - Cinematic Video Background & Transparent Countdown Timer
 * Features:
 *  - Fullscreen looping official GTA 6 Trailer background video
 *  - Frosted glassmorphism transparent countdown timer in the foreground
 *  - Live real-time ticking (Days, Hours, Minutes, Seconds)
 *  - Audio toggle to unmute trailer soundtrack / mute
 *  - Video Play/Pause toggle
 */

// Countdown target date: Autumn 2026 release target
$releaseDateTarget = '2026-11-19T00:00:00';
$releaseTitle = "GRAND THEFT AUTO VI";
$releaseSubtitle = "VICE CITY // STATE OF LEONIDA";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grand Theft Auto VI — Official Release Countdown</title>
  <meta name="description"
    content="Official Grand Theft Auto VI release countdown with full-screen background video and transparent glassmorphic timer.">

  <!-- Google Fonts: Syncopate + Space Grotesk + Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Syncopate:wght@700;800&family=Space+Grotesk:wght@600;700&display=swap"
    rel="stylesheet">

  <style>
    /* -------------------------------------------------------------
       1. Core Variables & Vice City Palette
       ------------------------------------------------------------- */
    :root {
      --neon-pink: #ff2a85;
      --neon-magenta: #e0007b;
      --neon-cyan: #00f0ff;
      --neon-teal: #00d9b8;
      --neon-amber: #ffb703;

      --glass-bg: rgba(10, 14, 30, 0.42);
      --glass-border: rgba(255, 255, 255, 0.18);
      --glass-blur: 24px;

      --text-main: #ffffff;
      --text-sub: #cbd5e1;
      --text-muted: #94a3b8;

      --font-display: 'Syncopate', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-heading: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-body: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      width: 100%;
      height: 100%;
      background-color: #05070d;
      color: var(--text-main);
      font-family: var(--font-body);
      overflow-x: hidden;
      overflow-y: auto;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    /* -------------------------------------------------------------
       2. FULLSCREEN BACKGROUND VIDEO CONTAINER
       ------------------------------------------------------------- */
    .video-background-wrap {
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
      background: #000;
    }

    .bg-video {
      position: absolute;
      top: 50%;
      left: 50%;
      min-width: 100%;
      min-height: 100%;
      width: auto;
      height: auto;
      transform: translate(-50%, -50%);
      object-fit: cover;
      filter: brightness(0.82) contrast(1.1) saturate(1.15);
    }

    /* Gradient & Vignette Overlay over video */
    .video-overlay {
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at center, rgba(5, 7, 15, 0.35) 0%, rgba(5, 7, 15, 0.85) 85%),
        linear-gradient(to bottom, rgba(5, 7, 15, 0.6) 0%, transparent 25%, transparent 75%, rgba(5, 7, 15, 0.9) 100%),
        linear-gradient(135deg, rgba(255, 42, 133, 0.15) 0%, transparent 40%, rgba(0, 240, 255, 0.15) 100%);
      pointer-events: none;
    }

    /* Subtle Animated Retro Scanline */
    .scanlines {
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom,
          rgba(255, 255, 255, 0),
          rgba(255, 255, 255, 0) 50%,
          rgba(0, 0, 0, 0.25) 50%,
          rgba(0, 0, 0, 0.25));
      background-size: 100% 4px;
      pointer-events: none;
      opacity: 0.35;
    }

    /* -------------------------------------------------------------
       3. TOP NAVIGATION / CONTROLS BAR
       ------------------------------------------------------------- */
    .gta-nav {
      position: relative;
      z-index: 20;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 2.5rem;
      background: linear-gradient(to bottom, rgba(5, 7, 15, 0.8) 0%, transparent 100%);
    }

    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 16px;
      text-decoration: none;
    }

    .logo-vi-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: 1.5px;
      color: #fff;
      padding: 5px 14px;
      background: linear-gradient(135deg, var(--neon-pink), #8b5cf6);
      border-radius: 8px;
      box-shadow: 0 0 25px rgba(255, 42, 133, 0.65), inset 0 0 10px rgba(255, 255, 255, 0.35);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .brand-title {
      font-family: var(--font-display);
      font-size: 1rem;
      letter-spacing: 2px;
      font-weight: 800;
      color: #fff;
      text-transform: uppercase;
      line-height: 1.15;
    }

    .brand-subtitle {
      font-size: 0.72rem;
      letter-spacing: 3px;
      color: var(--neon-cyan);
      text-transform: uppercase;
      font-weight: 700;
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(14, 20, 40, 0.55);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 9px 18px;
      border-radius: 9999px;
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 1px;
      cursor: pointer;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      touch-action: manipulation;
    }

    .action-btn:hover {
      background: rgba(255, 42, 133, 0.25);
      border-color: var(--neon-pink);
      color: #fff;
      box-shadow: 0 0 20px rgba(255, 42, 133, 0.5);
      transform: translateY(-2px);
    }

    .action-btn:focus-visible {
      outline: 2px solid var(--neon-cyan);
      outline-offset: 3px;
    }

    .action-btn[aria-pressed="true"] {
      border-color: var(--neon-cyan);
      background: rgba(0, 240, 255, 0.2);
      color: var(--neon-cyan);
      box-shadow: 0 0 18px rgba(0, 240, 255, 0.4);
    }

    /* -------------------------------------------------------------
       4. HERO FOREGROUND & TRANSPARENT COUNTDOWN TIMER
       ------------------------------------------------------------- */
    .hero-foreground {
      position: relative;
      z-index: 10;
      min-height: calc(100vh - 120px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem 4rem;
      text-align: center;
    }

    /* Status Pill Above Timer */
    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 42, 133, 0.16);
      border: 1px solid rgba(255, 42, 133, 0.45);
      padding: 7px 22px;
      border-radius: 9999px;
      font-size: 0.8rem;
      letter-spacing: 3px;
      font-weight: 800;
      color: #ff85be;
      text-transform: uppercase;
      margin-bottom: 1.5rem;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 0 25px rgba(255, 42, 133, 0.35);
    }

    .pulse-dot {
      width: 9px;
      height: 9px;
      background-color: var(--neon-pink);
      border-radius: 50%;
      box-shadow: 0 0 12px var(--neon-pink);
      animation: pulseBlink 1.5s infinite ease-in-out;
    }

    @keyframes pulseBlink {

      0%,
      100% {
        opacity: 1;
        transform: scale(1);
      }

      50% {
        opacity: 0.3;
        transform: scale(0.8);
      }
    }

    .hero-main-title {
      font-family: var(--font-display);
      font-size: clamp(2.5rem, 6.5vw, 5.2rem);
      font-weight: 900;
      letter-spacing: 4px;
      text-transform: uppercase;
      line-height: 1.05;
      margin-bottom: 0.75rem;
      background: linear-gradient(135deg, #ffffff 15%, #ffc6e5 55%, var(--neon-cyan) 95%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 6px 25px rgba(255, 42, 133, 0.55));
    }

    .hero-subtitle {
      font-size: clamp(1rem, 2vw, 1.3rem);
      color: var(--text-sub);
      letter-spacing: 2px;
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 2.75rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
    }

    /* -------------------------------------------------------------
       5. THE TRANSPARENT FROSTED GLASS COUNTDOWN CARD
       ------------------------------------------------------------- */
    /* .glass-timer-card {
      position: relative;
      max-width: 960px;
      width: 100%;
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 28px;
      padding: 3rem 2.5rem;
      backdrop-filter: blur(var(--glass-blur));
      -webkit-backdrop-filter: blur(var(--glass-blur));
      box-shadow:
        0 25px 60px rgba(0, 0, 0, 0.65),
        0 0 40px rgba(255, 42, 133, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
      transition: all 0.3s ease;
      overflow: hidden;
    } */

    /* Glowing neon accent bars */
    .glass-timer-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 10%;
      right: 10%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--neon-pink), var(--neon-cyan), transparent);
      box-shadow: 0 0 15px var(--neon-pink);
    }

    .glass-timer-card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 20%;
      right: 20%;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--neon-cyan), var(--neon-pink), transparent);
      box-shadow: 0 0 15px var(--neon-cyan);
    }

    /* 4 Transparent Timer Columns */
    .timer-columns-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
    }

    .transparent-unit-box {
      position: relative;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 18px;
      padding: 1.75rem 1rem;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 8px 24px rgba(0, 0, 0, 0.4);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .transparent-unit-box:hover {
      transform: translateY(-4px);
      border-color: var(--neon-pink);
      background: rgba(255, 42, 133, 0.08);
      box-shadow: 0 12px 35px rgba(255, 42, 133, 0.25), 0 0 20px rgba(0, 240, 255, 0.2);
    }

    .timer-number {
      font-family: var(--font-heading);
      font-size: clamp(2.75rem, 6.5vw, 4.75rem);
      font-weight: 700;
      line-height: 1;
      letter-spacing: 1px;
      color: #ffffff;
      font-variant-numeric: tabular-nums;
      text-shadow:
        0 0 20px rgba(255, 42, 133, 0.8),
        0 0 45px rgba(0, 240, 255, 0.5);
      margin-bottom: 0.6rem;
    }

    .timer-caption {
      font-family: var(--font-display);
      font-size: 0.75rem;
      letter-spacing: 3px;
      font-weight: 800;
      color: var(--neon-cyan);
      text-transform: uppercase;
      text-shadow: 0 0 12px rgba(0, 240, 255, 0.6);
    }

    /* Target Release Bar */
    .target-bar {
      margin-top: 2.25rem;
      padding-top: 1.75rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .target-date-text {
      font-size: 0.92rem;
      color: var(--text-sub);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .target-date-text strong {
      color: #fff;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .platform-badges {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .platform-pill {
      font-size: 0.72rem;
      font-family: var(--font-display);
      font-weight: 700;
      letter-spacing: 1.5px;
      padding: 5px 12px;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
    }

    /* -------------------------------------------------------------
       6. BOTTOM CONTROLS & SOUND INDICATOR
       ------------------------------------------------------------- */
    .bottom-bar {
      position: relative;
      z-index: 20;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 2.5rem;
      background: linear-gradient(to top, rgba(5, 7, 15, 0.9) 0%, transparent 100%);
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .video-info-pill {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      color: #cbd5e1;
    }

    .video-live-indicator {
      width: 7px;
      height: 7px;
      background-color: var(--neon-teal);
      border-radius: 50%;
      box-shadow: 0 0 8px var(--neon-teal);
    }

    /* -------------------------------------------------------------
       7. MOBILE RESPONSIVENESS
       ------------------------------------------------------------- */
    @media (max-width: 768px) {
      .gta-nav {
        padding: 1.25rem 1.25rem;
      }

      .hero-foreground {
        padding: 1.5rem 1rem 3rem;
      }

      .glass-timer-card {
        padding: 2rem 1.25rem;
        border-radius: 20px;
      }

      .timer-columns-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem;
      }

      .transparent-unit-box {
        padding: 1.25rem 0.5rem;
      }

      .target-bar {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .bottom-bar {
        flex-direction: column;
        gap: 8px;
        padding: 1rem 1.25rem;
        text-align: center;
      }
    }
  </style>
</head>

<body>

  <!-- Fullscreen Official GTA 6 Trailer Background Video -->
  <div class="video-background-wrap" aria-hidden="true">
    <video class="bg-video" id="gtaBgVideo" autoplay loop muted playsinline preload="auto">
      <source src="videos/gta6_trailer.mp4" type="video/mp4">
      <source src="https://archive.org/download/grand-theft-auto-vi-trailer-14k-2160p/Grand%20Theft%20Auto%20VI%20Trailer%2014k%202160p.ia.mp4" type="video/mp4">
      Your browser does not support HTML5 video.
    </video>
    <div class="video-overlay"></div>
    <div class="scanlines"></div>
  </div>

  <!-- Top Navigation & Media Bar -->
  <header class="gta-nav" role="banner">
    <a href="#" class="brand-logo-wrap" aria-label="Grand Theft Auto VI Home">
      <span class="logo-vi-badge">VI</span>
      <div>
        <div class="brand-title">Grand Theft Auto</div>
        <div class="brand-subtitle">State of Leonida</div>
      </div>
    </a>

    <div class="nav-actions">
      <!-- Trailer Video Audio Unmute / Mute Toggle -->
      <button type="button" class="action-btn" id="audioToggleBtn" aria-pressed="false"
        aria-label="Toggle trailer sound">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="audioIcon"
          aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
        <span id="audioBtnText">Sound: Muted</span>
      </button>

      <!-- Video Play / Pause Toggle -->
      <button type="button" class="action-btn" id="videoPlayPauseBtn" aria-pressed="false"
        aria-label="Pause background trailer video">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" id="videoPlayIcon" aria-hidden="true">
          <rect x="6" y="4" width="4" height="16" rx="1" />
          <rect x="14" y="4" width="4" height="16" rx="1" />
        </svg>
        <span id="videoPlayText">Video: Playing</span>
      </button>
    </div>
  </header>

  <!-- Main Hero Foreground with the Transparent Timer -->
  <main class="hero-foreground" role="main">

    <!-- Status Pill -->
    <div class="status-pill">
      <span class="pulse-dot" aria-hidden="true"></span>
      <span>Official Release Countdown</span>
    </div>

    <!-- Main Title -->
    <h1 class="hero-main-title"><?= $releaseTitle ?></h1>
    <p class="hero-subtitle"><?= $releaseSubtitle ?></p>

    <!-- Transparent Frosted Glass Countdown Timer Card -->
    <div class="glass-timer-card" role="timer" aria-live="off" aria-label="Grand Theft Auto VI Release Countdown">

      <div class="timer-columns-grid">
        <!-- Days -->
        <div class="transparent-unit-box">
          <div class="timer-number" id="daysVal">000</div>
          <div class="timer-caption">Days</div>
        </div>

        <!-- Hours -->
        <div class="transparent-unit-box">
          <div class="timer-number" id="hoursVal">00</div>
          <div class="timer-caption">Hours</div>
        </div>

        <!-- Minutes -->
        <div class="transparent-unit-box">
          <div class="timer-number" id="minutesVal">00</div>
          <div class="timer-caption">Minutes</div>
        </div>

        <!-- Seconds -->
        <div class="transparent-unit-box">
          <div class="timer-number" id="secondsVal">00</div>
          <div class="timer-caption">Seconds</div>
        </div>
      </div>

      <!-- Target Info Bottom Bar -->
      <div class="target-bar">
        <div class="target-date-text">
          <span>Target Launch Window:</span>
          <strong>November 19, 2026 &bull; Autumn Release</strong>
        </div>

        <div class="platform-badges">
          <span class="platform-pill">PlayStation 5</span>
          <span class="platform-pill">Xbox Series X|S</span>
        </div>
      </div>

    </div>

  </main>

  <!-- Bottom Details Bar -->
  <footer class="bottom-bar">
    <div class="video-info-pill">
      <span class="video-live-indicator"></span>
      <span>Official GTA 6 Trailer 1 Background Playback</span>
    </div>
    <div>Grand Theft Auto VI &copy; Rockstar Games &bull; Take-Two Interactive</div>
  </footer>

  <!-- ========================================================
       CLIENT-SIDE JAVASCRIPT: REAL-TIME COUNTDOWN & VIDEO CONTROLS
       ======================================================== -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {

      /* -------------------------------------------------------------
         1. LIVE REAL-TIME COUNTDOWN TIMER
         ------------------------------------------------------------- */
      const targetDate = new Date('<?= $releaseDateTarget ?>').getTime();
      const daysEl = document.getElementById('daysVal');
      const hoursEl = document.getElementById('hoursVal');
      const minutesEl = document.getElementById('minutesVal');
      const secondsEl = document.getElementById('secondsVal');

      function updateTimer() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        if (distance <= 0) {
          daysEl.textContent = '00';
          hoursEl.textContent = '00';
          minutesEl.textContent = '00';
          secondsEl.textContent = '00';
          return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        daysEl.textContent = String(days).padStart(3, '0');
        hoursEl.textContent = String(hours).padStart(2, '0');
        minutesEl.textContent = String(minutes).padStart(2, '0');
        secondsEl.textContent = String(seconds).padStart(2, '0');
      }

      updateTimer();
      setInterval(updateTimer, 1000);

      /* -------------------------------------------------------------
         2. BACKGROUND VIDEO & SOUND CONTROLS
         ------------------------------------------------------------- */
      const video = document.getElementById('gtaBgVideo');
      const audioBtn = document.getElementById('audioToggleBtn');
      const audioBtnText = document.getElementById('audioBtnText');
      const audioIcon = document.getElementById('audioIcon');
      const videoPlayBtn = document.getElementById('videoPlayPauseBtn');
      const videoPlayText = document.getElementById('videoPlayText');
      const videoPlayIcon = document.getElementById('videoPlayIcon');

      // Ensure video plays automatically on load
      if (video) {
        video.muted = true;
        video.play().catch(err => {
          console.log('Video autoplay initiated:', err);
        });
      }

      // Toggle Audio (Unmute / Mute Trailer Sound)
      audioBtn?.addEventListener('click', function () {
        if (!video) return;
        if (video.muted) {
          video.muted = false;
          video.volume = 0.85;
          audioBtn.setAttribute('aria-pressed', 'true');
          audioBtnText.textContent = 'Sound: ON';
          audioIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        `;
        } else {
          video.muted = true;
          audioBtn.setAttribute('aria-pressed', 'false');
          audioBtnText.textContent = 'Sound: Muted';
          audioIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        `;
        }
      });

      // Toggle Video Play / Pause
      videoPlayBtn?.addEventListener('click', function () {
        if (!video) return;
        if (video.paused) {
          video.play();
          videoPlayBtn.setAttribute('aria-pressed', 'false');
          videoPlayText.textContent = 'Video: Playing';
          videoPlayIcon.innerHTML = `
          <rect x="6" y="4" width="4" height="16" rx="1"/>
          <rect x="14" y="4" width="4" height="16" rx="1"/>
        `;
        } else {
          video.pause();
          videoPlayBtn.setAttribute('aria-pressed', 'true');
          videoPlayText.textContent = 'Video: Paused';
          videoPlayIcon.innerHTML = `
          <polygon points="5 3 19 12 5 21 5 3"/>
        `;
        }
      });

    });
  </script>
</body>

</html>