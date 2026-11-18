<?php
/**
 * Grand Theft Auto VI - Animated Carousel & Release Countdown
 * Features:
 *  - Prominent Live Countdown Timer (Days, Hours, Minutes, Seconds) with neon flip cards
 *  - Animated Full-Feature Carousel of Official GTA 6 Artworks (Leonida, Vice City, Lucia & Jason)
 *  - Native CSS Scroll Snap with modern-web-guidance accessible controls & markers
 *  - Vice City Neon Aesthetic with animated gradients, palm shadows & glassmorphism
 *  - Interactive Soundwave / Ambient Synthwave Audio via Web Audio API
 */

// Countdown target date: Autumn 2026 release target
$releaseDateTarget = '2026-11-19T00:00:00';
$releaseTitle = "GRAND THEFT AUTO VI";
$releaseSubtitle = "STATE OF LEONIDA // VICE CITY";

// Curated GTA 6 Image Slides with titles, tags, and local paths (with CDN fallbacks)
$slides = [
    [
        'id' => 1,
        'title' => 'Lucia & Jason: Trust',
        'subtitle' => 'The only way we’re gonna get through this is by sticking together.',
        'tag' => 'OFFICIAL KEY ART',
        'location' => 'Vice City, Leonida',
        'local' => 'images/gta6_keyart.jpg',
        'fallback' => 'https://images.wallpapersden.com/image/download/gta-6-official-artwork_bWdpZ26UmZqaraWkpJRmbmdlrWZlbWU.jpg',
        'description' => 'The official Grand Theft Auto VI reveal artwork featuring protagonists Lucia and Jason against the sun-drenched Miami-inspired skyline of Vice City.'
    ],
    [
        'id' => 2,
        'title' => 'Ocean Drive Sunset',
        'subtitle' => 'Golden hour over the neon strip, palm boulevards, and roaring supercars.',
        'tag' => 'VICE BEACH',
        'location' => 'Ocean Drive // South Beach',
        'local' => 'images/gta6_vice_city_beach.jpg',
        'fallback' => 'https://picfiles.alphacoders.com/604/604084.jpg',
        'description' => 'The iconic Vice City beach strip alive with pastel art deco hotels, swaying palms, high-end exotics, and radiant neon reflections.'
    ],
    [
        'id' => 3,
        'title' => 'Lucia: Second Chances',
        'subtitle' => 'Bad luck, I guess. Vice City’s skyline seen through the lens of ambition.',
        'tag' => 'PROTAGONIST',
        'location' => 'Leonida State Penitentiary & Downtown',
        'local' => 'images/gta6_lucia_skyline.jpg',
        'fallback' => 'https://picfiles.alphacoders.com/604/604087.jpg',
        'description' => 'Lucia looking out over the sprawling metropolitan expanse of Vice City, ready to claim everything the city denied her.'
    ],
    [
        'id' => 4,
        'title' => 'The Heist Duo',
        'subtitle' => 'Armed, dangerous, and unapologetic. Modern outlaws carving their legacy.',
        'tag' => 'ACTION & HEISTS',
        'location' => 'Port Gellhorn // Vice City Metro',
        'local' => 'images/gta6_jason_lucia.jpg',
        'fallback' => 'https://picfiles.alphacoders.com/604/604085.jpg',
        'description' => 'Jason and Lucia pulling off high-stakes scores across diners, bank vaults, and neon-lit back alleys of the Sunshine State.'
    ],
    [
        'id' => 5,
        'title' => 'Leonida Keys & Wetlands',
        'subtitle' => 'Airboats skim through alligator-infested waters beneath subtropical storms.',
        'tag' => 'WILDLIFE & REGIONS',
        'location' => 'Grassrivers // Florida Keys Expanse',
        'local' => 'images/gta6_leonida_keys.jpg',
        'fallback' => 'https://picfiles.alphacoders.com/604/604086.jpg',
        'description' => 'Explore the sprawling Everglades-inspired Grassrivers and Florida Keys archipelago packed with exotic wildlife and smugglers’ hideouts.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grand Theft Auto VI — Official Countdown & Artwork Gallery</title>
  <meta name="description" content="Live animated countdown to Grand Theft Auto VI (GTA 6) with an interactive artwork carousel featuring official screenshots, Lucia, Jason, and Vice City.">

  <!-- Google Fonts: Space Grotesk + Syncopate for GTA Vice City aesthetics -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Syncopate:wght@700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

  <style>
    /* -------------------------------------------------------------
       1. CSS Custom Properties & Vice City Neon Palette
       ------------------------------------------------------------- */
    :root {
      --neon-pink: #ff2a85;
      --neon-magenta: #e0007b;
      --neon-cyan: #00f0ff;
      --neon-teal: #00d9b8;
      --neon-amber: #ffb703;
      --neon-purple: #8b5cf6;
      
      --bg-dark: #070913;
      --bg-surface: #0e1124;
      --bg-card: rgba(18, 23, 48, 0.75);
      --border-glow: rgba(255, 42, 133, 0.35);
      --border-cyan: rgba(0, 240, 255, 0.3);

      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      
      --radius-sm: 8px;
      --radius-md: 16px;
      --radius-lg: 24px;
      --font-display: 'Syncopate', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-heading: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-body: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      min-height: 100%;
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body);
      overflow-x: hidden;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    /* -------------------------------------------------------------
       2. Animated Synthwave Horizon Background
       ------------------------------------------------------------- */
    .gta-ambient-bg {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      overflow: hidden;
    }

    .ambient-glow-pink {
      position: absolute;
      top: -15%;
      left: 15%;
      width: 650px;
      height: 650px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 42, 133, 0.18) 0%, rgba(224, 0, 123, 0.05) 50%, transparent 70%);
      filter: blur(80px);
      animation: pulseGlow 10s ease-in-out infinite alternate;
    }

    .ambient-glow-cyan {
      position: absolute;
      top: 25%;
      right: 10%;
      width: 700px;
      height: 700px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(0, 240, 255, 0.15) 0%, rgba(0, 217, 184, 0.04) 50%, transparent 70%);
      filter: blur(90px);
      animation: pulseGlow 12s ease-in-out infinite alternate-reverse;
    }

    .retro-grid {
      position: absolute;
      bottom: 0;
      left: -50%;
      right: -50%;
      height: 40vh;
      background-image: 
        linear-gradient(rgba(255, 42, 133, 0.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 240, 255, 0.1) 1px, transparent 1px);
      background-size: 60px 60px;
      transform: perspective(350px) rotateX(65deg);
      transform-origin: bottom center;
      mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
      -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 80%);
      animation: gridScroll 18s linear infinite;
    }

    @keyframes gridScroll {
      0% { background-position: 0 0; }
      100% { background-position: 0 60px; }
    }

    @keyframes pulseGlow {
      0% { transform: scale(1) translate(0, 0); opacity: 0.7; }
      100% { transform: scale(1.15) translate(25px, -20px); opacity: 1; }
    }

    /* -------------------------------------------------------------
       3. Page Header & Navigation Bar
       ------------------------------------------------------------- */
    .gta-nav {
      position: relative;
      z-index: 20;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(7, 9, 19, 0.75);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }

    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
    }

    .logo-vi-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-display);
      font-size: 1.35rem;
      font-weight: 900;
      letter-spacing: 1px;
      color: #fff;
      padding: 4px 12px;
      background: linear-gradient(135deg, #ff2a85, #8b5cf6);
      border-radius: var(--radius-sm);
      box-shadow: 0 0 16px rgba(255, 42, 133, 0.6), inset 0 0 8px rgba(255, 255, 255, 0.3);
      text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }

    .brand-title {
      font-family: var(--font-display);
      font-size: 0.95rem;
      letter-spacing: 2px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .brand-subtitle {
      font-size: 0.75rem;
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

    .audio-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(0, 240, 255, 0.3);
      color: var(--neon-cyan);
      padding: 8px 16px;
      border-radius: 9999px;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.25s ease;
      touch-action: manipulation;
    }

    .audio-toggle-btn:hover, .audio-toggle-btn:focus-visible {
      background: rgba(0, 240, 255, 0.15);
      border-color: var(--neon-cyan);
      box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
      outline: none;
    }

    /* -------------------------------------------------------------
       4. Main Content Wrapper
       ------------------------------------------------------------- */
    .gta-container {
      position: relative;
      z-index: 10;
      max-width: 1320px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem 5rem;
    }

    /* -------------------------------------------------------------
       5. HERO COUNTDOWN TIMER (Placed Prominently ABOVE Carousel)
       ------------------------------------------------------------- */
    .countdown-hero-section {
      text-align: center;
      margin-bottom: 3.5rem;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 42, 133, 0.12);
      border: 1px solid var(--border-glow);
      padding: 6px 18px;
      border-radius: 9999px;
      font-size: 0.8rem;
      letter-spacing: 2.5px;
      font-weight: 800;
      color: #ff7ebb;
      text-transform: uppercase;
      margin-bottom: 1.25rem;
      box-shadow: 0 0 20px rgba(255, 42, 133, 0.25);
    }

    .pulse-dot {
      width: 8px;
      height: 8px;
      background-color: var(--neon-pink);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--neon-pink);
      animation: blinkDot 1.5s infinite ease-in-out;
    }

    @keyframes blinkDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.3; transform: scale(0.85); }
    }

    .hero-heading {
      font-family: var(--font-display);
      font-size: clamp(2rem, 5vw, 3.8rem);
      font-weight: 900;
      letter-spacing: 3px;
      text-transform: uppercase;
      line-height: 1.1;
      margin-bottom: 0.75rem;
      background: linear-gradient(135deg, #ffffff 10%, #ffc6e5 50%, var(--neon-cyan) 95%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 4px 18px rgba(255, 42, 133, 0.4));
    }

    .hero-subheading {
      font-size: clamp(0.95rem, 1.8vw, 1.15rem);
      color: var(--text-muted);
      max-width: 680px;
      margin: 0 auto 2.25rem;
      letter-spacing: 0.5px;
    }

    /* The Neon Countdown Grid */
    .countdown-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      max-width: 840px;
      margin: 0 auto;
    }

    .timer-card {
      position: relative;
      background: var(--bg-card);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: var(--radius-md);
      padding: 1.75rem 1rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .timer-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--neon-pink), var(--neon-cyan));
      opacity: 0.8;
    }

    .timer-card:hover {
      transform: translateY(-4px);
      border-color: var(--neon-pink);
      box-shadow: 0 14px 40px rgba(255, 42, 133, 0.25), 0 0 20px rgba(0, 240, 255, 0.2);
    }

    .timer-digits {
      font-family: var(--font-heading);
      font-size: clamp(2.5rem, 6vw, 4.25rem);
      font-weight: 700;
      line-height: 1;
      letter-spacing: 1px;
      color: #fff;
      text-shadow: 0 0 25px rgba(255, 42, 133, 0.7), 0 0 45px rgba(0, 240, 255, 0.4);
      margin-bottom: 0.5rem;
      font-variant-numeric: tabular-nums;
    }

    .timer-label {
      font-family: var(--font-display);
      font-size: 0.75rem;
      letter-spacing: 3px;
      font-weight: 700;
      color: var(--neon-cyan);
      text-transform: uppercase;
    }

    .target-date-pill {
      margin-top: 1.75rem;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 0.85rem;
      color: var(--text-muted);
      background: rgba(255, 255, 255, 0.04);
      padding: 6px 18px;
      border-radius: 9999px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .target-date-pill strong {
      color: #fff;
    }

    /* -------------------------------------------------------------
       6. ACCESSIBLE MODERN CAROUSEL (modern-web-guidance compliant)
       ------------------------------------------------------------- */
    .carousel-section {
      position: relative;
      margin-top: 2rem;
    }

    .carousel-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .section-eyebrow {
      font-family: var(--font-display);
      font-size: 0.78rem;
      letter-spacing: 3px;
      color: var(--neon-pink);
      text-transform: uppercase;
      font-weight: 800;
      margin-bottom: 4px;
    }

    .section-heading {
      font-family: var(--font-heading);
      font-size: 1.85rem;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: 0.5px;
    }

    /* Carousel Toolbar Controls */
    .carousel-top-controls {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .ctrl-btn {
      min-width: 44px;
      min-height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: var(--radius-sm);
      color: #fff;
      cursor: pointer;
      touch-action: manipulation;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .ctrl-btn:hover {
      background: rgba(255, 42, 133, 0.2);
      border-color: var(--neon-pink);
      color: #fff;
      box-shadow: 0 0 15px rgba(255, 42, 133, 0.4);
    }

    .ctrl-btn:focus-visible {
      outline: 2px solid var(--neon-cyan);
      outline-offset: 3px;
    }

    .autoplay-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-muted);
      padding: 8px 14px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: var(--radius-sm);
      cursor: pointer;
      touch-action: manipulation;
      transition: all 0.2s ease;
    }

    .autoplay-badge[aria-pressed="true"] {
      color: var(--neon-teal);
      border-color: rgba(0, 217, 184, 0.4);
      background: rgba(0, 217, 184, 0.1);
    }

    /* The Carousel Stage */
    .carousel-stage {
      position: relative;
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1);
      background: #000;
    }

    /* Floating Arrow Buttons over Stage */
    .stage-arrow-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 15;
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(14, 17, 36, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      touch-action: manipulation;
    }

    .stage-arrow-btn:hover {
      background: var(--neon-pink);
      border-color: var(--neon-pink);
      box-shadow: 0 0 25px rgba(255, 42, 133, 0.6);
      transform: translateY(-50%) scale(1.08);
    }

    .stage-arrow-btn:focus-visible {
      outline: 2px solid var(--neon-cyan);
      outline-offset: 3px;
    }

    .stage-arrow-btn.prev {
      left: 1.25rem;
    }

    .stage-arrow-btn.next {
      right: 1.25rem;
    }

    /* Track & Scroll Snap (Mandatory & Accessible) */
    .carousel-track {
      display: flex;
      gap: 0;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      overscroll-behavior-x: contain;
      scrollbar-width: none;
      -ms-overflow-style: none;
      touch-action: manipulation;
      cursor: grab;
      user-select: none;
    }

    .carousel-track::-webkit-scrollbar {
      display: none;
    }

    .carousel-track:active {
      cursor: grabbing;
    }

    @media (prefers-reduced-motion: no-preference) {
      .carousel-track {
        scroll-behavior: smooth;
      }
    }

    /* Single Slide Item */
    .carousel-slide {
      flex: 0 0 100%;
      width: 100%;
      scroll-snap-align: center;
      scroll-snap-stop: always;
      position: relative;
      aspect-ratio: 16 / 9;
      min-height: 480px;
      max-height: 720px;
      background-color: #05070d;
      overflow: hidden;
    }

    .slide-media-wrap {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .slide-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
      filter: brightness(0.92) contrast(1.05);
    }

    .carousel-slide:hover .slide-img {
      transform: scale(1.03);
    }

    /* Dark vignette and neon gradients over slides */
    .slide-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to top,
        rgba(7, 9, 19, 0.95) 0%,
        rgba(7, 9, 19, 0.4) 40%,
        rgba(0, 0, 0, 0.1) 70%,
        rgba(0, 0, 0, 0.5) 100%
      );
      pointer-events: none;
    }

    /* Slide Caption Card */
    .slide-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 3rem 3rem 2.5rem;
      z-index: 10;
      max-width: 900px;
    }

    .slide-badge-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 0.75rem;
    }

    .slide-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: var(--radius-sm);
      font-size: 0.72rem;
      font-family: var(--font-display);
      font-weight: 800;
      letter-spacing: 2px;
      text-transform: uppercase;
      background: linear-gradient(90deg, var(--neon-pink), var(--neon-magenta));
      color: #fff;
      box-shadow: 0 0 14px rgba(255, 42, 133, 0.5);
    }

    .slide-location {
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--neon-cyan);
      text-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
    }

    .slide-title {
      font-family: var(--font-heading);
      font-size: clamp(1.6rem, 3.5vw, 2.75rem);
      font-weight: 700;
      line-height: 1.15;
      color: #fff;
      margin-bottom: 0.5rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
    }

    .slide-desc {
      font-size: clamp(0.9rem, 1.4vw, 1.05rem);
      color: #cbd5e1;
      max-width: 720px;
      line-height: 1.6;
      text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
    }

    /* -------------------------------------------------------------
       7. Carousel Navigation Markers (Dots + Thumbnails)
       ------------------------------------------------------------- */
    .carousel-nav-markers {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 14px;
      margin-top: 1.5rem;
      padding: 0.5rem;
    }

    .marker-dot {
      /* WCAG Target Size >= 24x24px hit area with 12px visible dot */
      position: relative;
      min-width: 32px;
      min-height: 32px;
      background: transparent;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      touch-action: manipulation;
    }

    .marker-dot::before {
      content: '';
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.4);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .marker-dot:hover::before {
      background: rgba(255, 42, 133, 0.6);
      transform: scale(1.2);
    }

    .marker-dot[aria-current="true"]::before {
      width: 38px;
      border-radius: 9999px;
      background: linear-gradient(90deg, var(--neon-pink), var(--neon-cyan));
      border-color: transparent;
      box-shadow: 0 0 16px rgba(255, 42, 133, 0.8), 0 0 12px rgba(0, 240, 255, 0.5);
    }

    .marker-dot:focus-visible {
      outline: 2px solid var(--neon-cyan);
      outline-offset: 4px;
      border-radius: 50%;
    }

    /* -------------------------------------------------------------
       8. Game Feature Highlights Row
       ------------------------------------------------------------- */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-top: 4.5rem;
    }

    .feature-card {
      background: var(--bg-card);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: var(--radius-md);
      padding: 1.75rem;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: all 0.3s ease;
    }

    .feature-card:hover {
      border-color: rgba(0, 240, 255, 0.4);
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0, 240, 255, 0.15);
    }

    .feature-icon-wrap {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-sm);
      background: rgba(255, 42, 133, 0.12);
      border: 1px solid var(--border-glow);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--neon-pink);
      margin-bottom: 1.25rem;
    }

    .feature-title {
      font-family: var(--font-heading);
      font-size: 1.25rem;
      color: #fff;
      margin-bottom: 0.5rem;
    }

    .feature-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* -------------------------------------------------------------
       9. Footer
       ------------------------------------------------------------- */
    .gta-footer {
      position: relative;
      z-index: 10;
      margin-top: 5rem;
      padding: 2.5rem 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      text-align: center;
      background: rgba(7, 9, 19, 0.9);
    }

    .footer-text {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 0.5rem;
    }

    .footer-disclaimer {
      font-size: 0.75rem;
      color: #64748b;
    }

    /* -------------------------------------------------------------
       10. Accessibility & Utility Classes
       ------------------------------------------------------------- */
    .visually-hidden {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    @media (max-width: 768px) {
      .countdown-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem;
      }
      .timer-card {
        padding: 1.25rem 0.75rem;
      }
      .carousel-slide {
        aspect-ratio: 4 / 3;
        min-height: 380px;
      }
      .slide-content {
        padding: 1.5rem 1.25rem;
      }
      .stage-arrow-btn {
        width: 42px;
        height: 42px;
      }
      .stage-arrow-btn.prev { left: 0.5rem; }
      .stage-arrow-btn.next { right: 0.5rem; }
    }
  </style>
</head>
<body>

  <!-- Ambient Glow Background -->
  <div class="gta-ambient-bg" aria-hidden="true">
    <div class="ambient-glow-pink"></div>
    <div class="ambient-glow-cyan"></div>
    <div class="retro-grid"></div>
  </div>

  <!-- Top Navigation -->
  <header class="gta-nav" role="banner">
    <a href="#" class="brand-logo-wrap" aria-label="Grand Theft Auto VI Home">
      <span class="logo-vi-badge">VI</span>
      <div>
        <div class="brand-title">Grand Theft Auto</div>
        <div class="brand-subtitle">State of Leonida</div>
      </div>
    </a>

    <div class="nav-actions">
      <!-- Synthwave Ambient Audio Synthesizer (Zero external file dependencies) -->
      <button type="button" class="audio-toggle-btn" id="synthAudioBtn" aria-pressed="false" aria-label="Toggle Vice City synthwave ambient audio">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        </svg>
        <span id="audioBtnText">Synthwave Ambient: Off</span>
      </button>
    </div>
  </header>

  <main class="gta-container" role="main">

    <!-- ========================================================
         COUNTDOWN TIMER SECTION (Prominently Above Carousel)
         ======================================================== -->
    <section class="countdown-hero-section" aria-labelledby="countdown-title">
      <div class="status-pill">
        <span class="pulse-dot" aria-hidden="true"></span>
        <span>Official Release Countdown</span>
      </div>

      <h1 class="hero-heading" id="countdown-title">Grand Theft Auto VI</h1>
      <p class="hero-subheading">
        Counting down every second until our return to Vice City and the wild expanse of the State of Leonida on PlayStation 5 and Xbox Series X|S.
      </p>

      <!-- The 4-Card Live Timer -->
      <div class="countdown-grid" id="timerGrid" role="timer" aria-live="off">
        <div class="timer-card">
          <div class="timer-digits" id="daysDisplay">000</div>
          <div class="timer-label">Days</div>
        </div>

        <div class="timer-card">
          <div class="timer-digits" id="hoursDisplay">00</div>
          <div class="timer-label">Hours</div>
        </div>

        <div class="timer-card">
          <div class="timer-digits" id="minutesDisplay">00</div>
          <div class="timer-label">Minutes</div>
        </div>

        <div class="timer-card">
          <div class="timer-digits" id="secondsDisplay">00</div>
          <div class="timer-label">Seconds</div>
        </div>
      </div>

      <div class="target-date-pill">
        <span>Target Release Window:</span>
        <strong id="targetDateLabel">November 19, 2026 &bull; Autumn Launch</strong>
      </div>
    </section>

    <!-- ========================================================
         OFFICIAL GTA 6 IMAGE CAROUSEL SECTION
         ======================================================== -->
    <section class="carousel-section" aria-roledescription="carousel" aria-label="Grand Theft Auto VI Official Artwork">
      
      <div class="carousel-header">
        <div>
          <div class="section-eyebrow">Leonida Media Archive</div>
          <h2 class="section-heading">Official Artwork &amp; Screenshots</h2>
        </div>

        <!-- Carousel Toolbar Controls -->
        <div class="carousel-top-controls">
          <button type="button" class="autoplay-badge" id="autoplayToggle" aria-pressed="true" aria-label="Toggle carousel auto-play">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" id="playPauseIcon" aria-hidden="true">
              <rect x="6" y="4" width="4" height="16" rx="1"/>
              <rect x="14" y="4" width="4" height="16" rx="1"/>
            </svg>
            <span id="autoplayText">Auto-Play: ON</span>
          </button>

          <button type="button" class="ctrl-btn" id="prevSlideTop" aria-label="Previous slide">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <button type="button" class="ctrl-btn" id="nextSlideTop" aria-label="Next slide">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Stage with floating side controls and scroll snap track -->
      <div class="carousel-stage">
        
        <!-- Accessible Previous Button -->
        <button type="button" class="stage-arrow-btn prev" id="stagePrevBtn" aria-label="Previous slide">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Accessible Next Button -->
        <button type="button" class="stage-arrow-btn next" id="stageNextBtn" aria-label="Next slide">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Carousel Track with Scroll Snap -->
        <div class="carousel-track" id="gtaTrack" tabindex="0" role="group" aria-label="GTA 6 Slides">
          <?php foreach ($slides as $index => $slide): ?>
            <article 
              class="carousel-slide" 
              role="group" 
              aria-roledescription="slide" 
              aria-label="<?= ($index + 1) . ' of ' . count($slides) ?>"
              data-slide-index="<?= $index ?>"
              data-marker-name="Go to slide <?= ($index + 1) ?>"
            >
              <div class="slide-media-wrap">
                <img 
                  class="slide-img" 
                  src="<?= htmlspecialchars($slide['local']) ?>" 
                  alt="<?= htmlspecialchars($slide['title']) ?>"
                  loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                  onerror="if(this.src !== '<?= htmlspecialchars($slide['fallback']) ?>'){ this.src='<?= htmlspecialchars($slide['fallback']) ?>'; }"
                >
                <div class="slide-overlay" aria-hidden="true"></div>
              </div>

              <div class="slide-content">
                <div class="slide-badge-row">
                  <span class="slide-badge"><?= htmlspecialchars($slide['tag']) ?></span>
                  <span class="slide-location">&bull; <?= htmlspecialchars($slide['location']) ?></span>
                </div>
                <h3 class="slide-title"><?= htmlspecialchars($slide['title']) ?></h3>
                <p class="slide-desc"><?= htmlspecialchars($slide['description']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

      </div>

      <!-- Live polite screen-reader announcement region -->
      <p class="visually-hidden" id="carouselStatus" role="status" aria-live="polite"></p>

      <!-- Dot Navigation Markers -->
      <nav class="carousel-nav-markers" id="carouselMarkers" aria-label="Choose a GTA 6 slide">
        <?php foreach ($slides as $index => $slide): ?>
          <button 
            type="button" 
            class="marker-dot" 
            data-target-index="<?= $index ?>" 
            aria-label="Go to slide <?= ($index + 1) ?>: <?= htmlspecialchars($slide['title']) ?>"
            aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
          ></button>
        <?php endforeach; ?>
      </nav>

    </section>

    <!-- ========================================================
         GAME FEATURES & HIGHLIGHTS
         ======================================================== -->
    <section class="features-grid" aria-label="Grand Theft Auto VI Features">
      
      <div class="feature-card">
        <div class="feature-icon-wrap" aria-hidden="true">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <h3 class="feature-title">Dual Protagonists</h3>
        <p class="feature-text">Experience the criminal underworld through Lucia and Jason, an ambitious pair navigating high-risk robberies and shifting loyalties across Vice City.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrap" style="background: rgba(0,240,255,0.12); border-color: var(--border-cyan); color: var(--neon-cyan);" aria-hidden="true">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        </div>
        <h3 class="feature-title">State of Leonida</h3>
        <p class="feature-text">The biggest, most immersive evolution of the Grand Theft Auto series yet, spanning urban Vice City, the Keys, and untamed swamp waterways.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrap" style="background: rgba(255,183,3,0.12); border-color: rgba(255,183,3,0.3); color: var(--neon-amber);" aria-hidden="true">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="feature-title">Next-Gen Power</h3>
        <p class="feature-text">Built natively for PlayStation 5 and Xbox Series X|S with real-time global illumination, ultra-dense NPC simulations, and photorealistic water physics.</p>
      </div>

    </section>

  </main>

  <!-- Page Footer -->
  <footer class="gta-footer">
    <p class="footer-text">Grand Theft Auto VI &copy; Rockstar Games. Developed for PlayStation 5 and Xbox Series X|S.</p>
    <p class="footer-disclaimer">This interactive showcase is a fan countdown and media gallery. All trademarks and images belong to Take-Two Interactive and Rockstar Games.</p>
  </footer>

  <!-- ========================================================
       CLIENT-SIDE JAVASCRIPT: COUNTDOWN, CAROUSEL & WEB AUDIO
       ======================================================== -->
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    
    /* -------------------------------------------------------------
       1. LIVE COUNTDOWN TIMER (Days, Hours, Minutes, Seconds)
       ------------------------------------------------------------- */
    const targetDate = new Date('<?= $releaseDateTarget ?>').getTime();
    const daysEl = document.getElementById('daysDisplay');
    const hoursEl = document.getElementById('hoursDisplay');
    const minutesEl = document.getElementById('minutesDisplay');
    const secondsEl = document.getElementById('secondsDisplay');

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = targetDate - now;

      if (distance < 0) {
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

    updateCountdown();
    setInterval(updateCountdown, 1000);

    /* -------------------------------------------------------------
       2. MODERN ACCESSIBLE CAROUSEL IMPLEMENTATION
       ------------------------------------------------------------- */
    const track = document.getElementById('gtaTrack');
    const slides = Array.from(track.querySelectorAll('.carousel-slide'));
    const markerButtons = Array.from(document.querySelectorAll('.marker-dot'));
    const statusRegion = document.getElementById('carouselStatus');
    const prevBtns = [document.getElementById('stagePrevBtn'), document.getElementById('prevSlideTop')];
    const nextBtns = [document.getElementById('stageNextBtn'), document.getElementById('nextSlideTop')];
    const autoplayToggle = document.getElementById('autoplayToggle');
    const autoplayText = document.getElementById('autoplayText');
    const playPauseIcon = document.getElementById('playPauseIcon');

    let currentIndex = 0;
    let isAutoplayActive = true;
    let autoplayInterval = null;
    let isUserInteracting = false;
    let isRtl = document.dir === 'rtl';

    // Update active marker and announcement
    function updateActiveState(index, announce = false) {
      if (index < 0 || index >= slides.length) return;
      currentIndex = index;

      markerButtons.forEach((btn, i) => {
        const isActive = (i === index);
        btn.setAttribute('aria-current', isActive ? 'true' : 'false');
      });

      if (announce && statusRegion) {
        statusRegion.textContent = `Slide ${index + 1} of ${slides.length}: ${slides[index].querySelector('.slide-title')?.textContent || ''}`;
      }
    }

    // Scroll directly to a specific slide index
    function scrollToSlide(index, smooth = true) {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;

      const targetSlide = slides[index];
      if (!targetSlide) return;

      const targetLeft = targetSlide.offsetLeft;
      track.scrollTo({
        left: targetLeft,
        behavior: smooth ? 'smooth' : 'auto'
      });

      updateActiveState(index, true);
    }

    // Next / Previous helpers
    function nextSlide() {
      scrollToSlide(currentIndex + 1);
    }

    function prevSlide() {
      scrollToSlide(currentIndex - 1);
    }

    // Attach button listeners
    nextBtns.forEach(btn => btn?.addEventListener('click', () => {
      pauseAutoplayTemporary();
      nextSlide();
    }));

    prevBtns.forEach(btn => btn?.addEventListener('click', () => {
      pauseAutoplayTemporary();
      prevSlide();
    }));

    markerButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetIdx = parseInt(btn.getAttribute('data-target-index'), 10);
        pauseAutoplayTemporary();
        scrollToSlide(targetIdx);
      });
    });

    // Keyboard navigation (ArrowLeft & ArrowRight)
    track.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        pauseAutoplayTemporary();
        isRtl ? prevSlide() : nextSlide();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        pauseAutoplayTemporary();
        isRtl ? nextSlide() : prevSlide();
      }
    });

    // Sync active marker while user scrolls / swipes (throttled with requestAnimationFrame)
    let isScrolling = false;
    track.addEventListener('scroll', () => {
      if (!isScrolling) {
        window.requestAnimationFrame(() => {
          const scrollPos = track.scrollLeft;
          const trackWidth = track.clientWidth || 1;
          const approxIndex = Math.round(scrollPos / trackWidth);
          if (approxIndex !== currentIndex && approxIndex >= 0 && approxIndex < slides.length) {
            updateActiveState(approxIndex, false);
          }
          isScrolling = false;
        });
        isScrolling = true;
      }
    }, { passive: true });

    // Autoplay Loop
    function startAutoplay() {
      if (autoplayInterval) clearInterval(autoplayInterval);
      autoplayInterval = setInterval(() => {
        if (!isUserInteracting) {
          nextSlide();
        }
      }, 5500);
    }

    function stopAutoplay() {
      if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
      }
    }

    function pauseAutoplayTemporary() {
      stopAutoplay();
      if (isAutoplayActive) {
        clearTimeout(track._resumeTimeout);
        track._resumeTimeout = setTimeout(() => {
          if (isAutoplayActive) startAutoplay();
        }, 9000);
      }
    }

    // Pause on hover or focus for accessibility
    track.addEventListener('mouseenter', () => { isUserInteracting = true; });
    track.addEventListener('mouseleave', () => { isUserInteracting = false; });
    track.addEventListener('focusin', () => { isUserInteracting = true; });
    track.addEventListener('focusout', () => { isUserInteracting = false; });

    // Autoplay toggle button
    autoplayToggle?.addEventListener('click', () => {
      isAutoplayActive = !isAutoplayActive;
      autoplayToggle.setAttribute('aria-pressed', isAutoplayActive ? 'true' : 'false');
      if (isAutoplayActive) {
        autoplayText.textContent = 'Auto-Play: ON';
        playPauseIcon.innerHTML = '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>';
        startAutoplay();
      } else {
        autoplayText.textContent = 'Auto-Play: PAUSED';
        playPauseIcon.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
        stopAutoplay();
      }
    });

    startAutoplay();

    /* -------------------------------------------------------------
       3. AMBIENT SYNTHWAVE AUDIO (Web Audio API Synthesizer)
       Zero external audio files needed - generates pure 80s synth chords!
       ------------------------------------------------------------- */
    const synthAudioBtn = document.getElementById('synthAudioBtn');
    const audioBtnText = document.getElementById('audioBtnText');
    let audioCtx = null;
    let isAudioPlaying = false;
    let ambientGainNode = null;
    let synthInterval = null;

    function initSynthwaveAudio() {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      audioCtx = new AudioContext();

      // Master Gain
      ambientGainNode = audioCtx.createGain();
      ambientGainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);
      ambientGainNode.connect(audioCtx.destination);

      // Warm low-pass filter for vintage 80s analog warmth
      const filter = audioCtx.createBiquadFilter();
      filter.type = 'lowpass';
      filter.frequency.setValueAtTime(650, audioCtx.currentTime);
      filter.connect(ambientGainNode);

      // Chords sequence: Em9 -> Cmaj7 -> Dadd9 -> Bm7 (Classic Vice City synth chords)
      const chordNotes = [
        [164.81, 196.00, 246.94, 293.66], // E3, G3, B3, D4
        [130.81, 164.81, 196.00, 246.94], // C3, E3, G3, B3
        [146.83, 220.00, 293.66, 369.99], // D3, A3, D4, F#4
        [123.47, 146.83, 220.00, 293.66]  // B2, D3, A3, D4
      ];
      let chordStep = 0;

      function playPadChord() {
        if (!isAudioPlaying || !audioCtx) return;
        const currentChord = chordNotes[chordStep];
        chordStep = (chordStep + 1) % chordNotes.length;

        currentChord.forEach((freq, idx) => {
          const osc = audioCtx.createOscillator();
          const noteGain = audioCtx.createGain();

          osc.type = idx % 2 === 0 ? 'sawtooth' : 'triangle';
          osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
          // slight detune for lush 80s chorus width
          osc.detune.setValueAtTime((idx - 1.5) * 8, audioCtx.currentTime);

          noteGain.gain.setValueAtTime(0, audioCtx.currentTime);
          noteGain.gain.linearRampToValueAtTime(0.02, audioCtx.currentTime + 1.2);
          noteGain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 4.5);

          osc.connect(noteGain);
          noteGain.connect(filter);

          osc.start();
          osc.stop(audioCtx.currentTime + 4.6);
        });
      }

      playPadChord();
      synthInterval = setInterval(playPadChord, 4200);
    }

    synthAudioBtn?.addEventListener('click', () => {
      if (!isAudioPlaying) {
        if (!audioCtx) initSynthwaveAudio();
        if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
        isAudioPlaying = true;
        synthAudioBtn.setAttribute('aria-pressed', 'true');
        synthAudioBtn.style.background = 'rgba(0, 240, 255, 0.2)';
        audioBtnText.textContent = 'Synthwave Ambient: ON';
      } else {
        isAudioPlaying = false;
        synthAudioBtn.setAttribute('aria-pressed', 'false');
        synthAudioBtn.style.background = 'rgba(255, 255, 255, 0.06)';
        audioBtnText.textContent = 'Synthwave Ambient: OFF';
        if (synthInterval) clearInterval(synthInterval);
        if (audioCtx) audioCtx.suspend();
      }
    });

  });
  </script>
</body>
</html>
