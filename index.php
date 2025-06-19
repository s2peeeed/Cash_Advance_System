<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Liquidation System - Municipality of Loon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #2563eb;
            --accent-color: #60a5fa;
            --dark-blue: #0f172a;
            --navy-blue: #1e293b;
            --light-blue: #e0f2fe;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --light-text: #6b7280;
            --white: #ffffff;
            --gradient-primary: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #60a5fa 100%);
            --gradient-secondary: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --gradient-gold: linear-gradient(45deg, #fbbf24, #f59e0b, #d97706);
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 12px 24px rgba(0, 0, 0, 0.25);
            --shadow-2xl: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gradient-secondary);
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--dark-text);
            line-height: 1.6;
            position: relative;
        }

        /* Enhanced Scroll Progress Bar */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 2000;
            backdrop-filter: blur(10px);
        }

        .scroll-progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            width: 0;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
            will-change: width;
        }

        .scroll-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Enhanced Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--gradient-primary);
            border-radius: 50%;
            animation: float 10s ease-in-out infinite;
            box-shadow: 0 0 15px rgba(96, 165, 250, 0.6);
            will-change: transform, opacity;
        }

        .particle:nth-child(odd) {
            background: var(--gradient-gold);
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.8);
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0) rotate(0deg) scale(1); 
                opacity: 0.6; 
            }
            25% {
                transform: translateY(-20px) rotate(90deg) scale(1.1);
                opacity: 0.8;
            }
            50% { 
                transform: translateY(-40px) rotate(180deg) scale(1.3); 
                opacity: 0.9; 
            }
            75% {
                transform: translateY(-30px) rotate(270deg) scale(1.2);
                opacity: 0.7;
            }
        }

        /* Enhanced Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.4s ease;
            border-bottom: 1px solid rgba(96, 165, 250, 0.2);
            will-change: transform, background;
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.98);
            box-shadow: var(--shadow-xl);
            transform: translateY(0);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            font-size: clamp(1.5rem, 2.5vw, 1.8rem);
            letter-spacing: -0.5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .navbar-brand:hover {
            color: var(--accent-color) !important;
            transform: scale(1.05);
        }

        .navbar-brand::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .navbar-brand:hover::after {
            width: 100%;
        }

        .navbar-brand i {
            color: var(--accent-color);
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover i {
            transform: rotate(20deg) scale(1.2);
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        .navbar-toggler:hover {
            transform: scale(1.1);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            transition: all 0.3s ease;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.8rem 1.5rem !important;
            border-radius: 10px;
            margin: 0 0.3rem;
            overflow: hidden;
            will-change: transform, background;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-link:hover::before, .nav-link.active::before {
            left: 100%;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--white) !important;
            background: rgba(96, 165, 250, 0.15);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(96, 165, 250, 0.3);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 4px;
            background: var(--gradient-primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 90%;
        }

        /* Enhanced Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 9rem 2rem 3rem;
            position: relative;
            background: var(--gradient-primary);
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(96, 165, 250, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 8s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(1deg); }
        }

        .logo-container {
            position: relative;
            margin-bottom: 3.5rem;
            animation: logoFloat 6s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .logo-img {
            width: 240px;
            height: 240px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: var(--shadow-xl);
            transition: all 0.5s ease;
            background: var(--white);
            padding: 15px;
            border: 5px solid rgba(255, 255, 255, 0.25);
            position: relative;
            z-index: 2;
            will-change: transform, box-shadow;
        }

        .logo-img:hover {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.5);
            border-color: rgba(96, 165, 250, 0.5);
        }

        .logo-img::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            background: var(--gradient-primary);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logo-img:hover::before {
            opacity: 0.3;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.5) 0%, transparent 70%);
            animation: pulse-glow 3.5s ease-in-out infinite;
            will-change: transform, opacity;
        }

        @keyframes pulse-glow {
            0%, 100% { 
                transform: translate(-50%, -50%) scale(1); 
                opacity: 0.7; 
            }
            50% { 
                transform: translate(-50%, -50%) scale(1.4); 
                opacity: 0.3; 
            }
        }

        .hero-title {
            color: var(--white);
            font-size: clamp(2.8rem, 5.5vw, 5rem);
            font-weight: 900;
            margin-bottom: 1.8rem;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
            letter-spacing: -2.5px;
            line-height: 1.2;
            position: relative;
            will-change: transform, opacity;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 4px;
            background: var(--gradient-gold);
            border-radius: 2px;
            animation: titleUnderline 2s ease-out 1s forwards;
        }

        @keyframes titleUnderline {
            to { width: 200px; }
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: clamp(1.3rem, 2.8vw, 2rem);
            margin-bottom: 2.5rem;
            font-weight: 500;
            letter-spacing: -0.8px;
            animation: fadeInUp 1s ease-out 0.5s both;
            will-change: transform, opacity;
        }

        .hero-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: clamp(1.1rem, 2.2vw, 1.4rem);
            max-width: 750px;
            margin: 0 auto 4.5rem;
            line-height: 1.8;
            font-weight: 300;
            animation: fadeInUp 1s ease-out 1s both;
            will-change: transform, opacity;
        }

        @keyframes fadeInUp {
            0% { transform: translateY(30px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        /* Enhanced Buttons */
        .btn-primary-custom {
            background: var(--gradient-primary);
            border: none;
            color: var(--white);
            padding: 1.3rem 4rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin: 0.75rem;
            animation: buttonFloat 1s ease-out 1.5s both;
            will-change: transform, box-shadow;
        }

        @keyframes buttonFloat {
            0% { transform: translateY(30px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .btn-primary-custom:hover {
            transform: translateY(-6px) scale(1.05);
            box-shadow: 0 25px 50px rgba(96, 165, 250, 0.5);
            background: linear-gradient(135deg, #2563eb 0%, #1e3a8a 50%, #1e293b 100%);
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: 0.5s;
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.6s ease;
        }

        .btn-primary-custom:hover::after {
            width: 300px;
            height: 300px;
        }

        .btn-secondary-custom {
            background: transparent;
            border: 3px solid var(--white);
            color: var(--white);
            padding: 1.2rem 3.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.4s ease;
            margin: 0.75rem;
            position: relative;
            overflow: hidden;
            animation: buttonFloat 1s ease-out 1.7s both;
            will-change: transform, box-shadow;
        }

        .btn-secondary-custom:hover {
            background: var(--white);
            color: var(--primary-color);
            transform: translateY(-6px) scale(1.05);
            box-shadow: 0 20px 40px rgba(255, 255, 255, 0.4);
            border-color: var(--accent-color);
        }

        .btn-secondary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: 0.5s;
        }

        .btn-secondary-custom:hover::before {
            left: 100%;
        }

        /* Enhanced Features Section */
        .features {
            background: var(--light-bg);
            padding: 9rem 0;
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 10% 20%, rgba(96, 165, 250, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .section-title {
            text-align: center;
            margin-bottom: 6rem;
            position: relative;
        }

        .section-title h2 {
            font-size: clamp(2.8rem, 4.5vw, 4rem);
            color: var(--dark-text);
            font-weight: 800;
            margin-bottom: 1.8rem;
            letter-spacing: -1.2px;
            position: relative;
            will-change: transform, opacity;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
            animation: titleUnderline 2s ease-out 0.5s forwards;
        }

        .section-title p {
            font-size: clamp(1.2rem, 2.2vw, 1.5rem);
            color: var(--light-text);
            max-width: 750px;
            margin: 0 auto;
            font-weight: 400;
            animation: fadeInUp 1s ease-out 0.3s both;
            will-change: transform, opacity;
        }

        .feature-card {
            background: var(--white);
            border-radius: 24px;
            padding: 3.5rem 2.5rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: all 0.5s ease;
            border: 1px solid rgba(96, 165, 250, 0.15);
            height: 100%;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            will-change: transform, box-shadow;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.1), transparent);
            transition: 0.5s;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.05) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::after {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-25px) rotateY(5deg) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-color);
        }

        .feature-icon {
            width: 100px;
            height: 100px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2.5rem;
            font-size: 2.5rem;
            color: var(--white);
            transition: all 0.4s ease;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 2;
            will-change: transform, box-shadow;
        }

        .feature-icon::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: var(--gradient-primary);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.2) rotate(20deg);
            box-shadow: var(--shadow-lg);
        }

        .feature-card:hover .feature-icon::before {
            opacity: 0.3;
        }

        .feature-card h5 {
            color: var(--dark-text);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.8rem;
            letter-spacing: -0.8px;
            transition: color 0.3s ease;
        }

        .feature-card:hover h5 {
            color: var(--primary-color);
        }

        .feature-card p {
            color: var(--light-text);
            line-height: 1.8;
            font-size: 1.1rem;
            font-weight: 400;
            transition: color 0.3s ease;
        }

        .feature-card:hover p {
            color: var(--dark-text);
        }

        /* Enhanced How It Works Section */
        .how-it-works {
            padding: 9rem 0;
            background: var(--white);
            position: relative;
        }

        .step-card {
            text-align: center;
            padding: 2.5rem;
            position: relative;
            margin-bottom: 2.5rem;
            will-change: transform;
        }

        .step-number {
            width: 90px;
            height: 90px;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 2.5rem;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-md);
            transition: all 0.4s ease;
            will-change: transform, box-shadow;
        }

        .step-card:hover .step-number {
            transform: scale(1.15) rotate(10deg);
            box-shadow: var(--shadow-lg);
        }

        .step-connector {
            position: absolute;
            top: 45px;
            left: calc(50% + 45px);
            width: calc(100% - 90px);
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), transparent);
            z-index: 1;
        }

        .step-card:last-child .step-connector {
            display: none;
        }

        .step-card h5 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--dark-text);
        }

        .step-card p {
            color: var(--light-text);
            line-height: 1.7;
            font-size: 1.1rem;
        }

        /* Enhanced Footer */
        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 6rem 0 2.5rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3.5rem;
            margin-bottom: 3.5rem;
        }

        .footer-section h5 {
            color: var(--accent-color);
            margin-bottom: 1.8rem;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.8px;
        }

        .footer-section p, .footer-section li {
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.9;
            font-weight: 400;
            font-size: 1.1rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            padding: 0.6rem 0;
            border-radius: 8px;
        }

        .footer-section ul li:hover {
            color: var(--accent-color);
            cursor: pointer;
            transform: translateX(8px);
            background: rgba(96, 165, 250, 0.15);
            padding-left: 1.2rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(96, 165, 250, 0.25);
            padding-top: 2.5rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
            font-size: 1rem;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1400px) {
            .hero-title {
                font-size: clamp(2.6rem, 5vw, 4.8rem);
            }
            
            .section-title h2 {
                font-size: clamp(2.6rem, 4.2vw, 3.8rem);
            }
        }

        @media (max-width: 1200px) {
            .hero-title {
                font-size: clamp(2.5rem, 4.5vw, 4.2rem);
            }
            
            .section-title h2 {
                font-size: clamp(2.5rem, 4vw, 3.2rem);
            }
            
            .logo-img {
                width: 220px;
                height: 220px;
                padding: 12px;
            }
            
            .logo-glow {
                width: 280px;
                height: 280px;
            }
        }

        @media (max-width: 992px) {
            .container {
                padding-left: 2.5rem;
                padding-right: 2.5rem;
            }
            
            .hero {
                padding: 8rem 2rem 3rem;
            }
            
            .hero-title {
                font-size: clamp(2.3rem, 4.2vw, 3.8rem);
            }
            
            .hero-subtitle {
                font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            }
            
            .hero-description {
                font-size: clamp(1rem, 2vw, 1.3rem);
            }
            
            .logo-img {
                width: 200px;
                height: 200px;
                padding: 10px;
            }
            
            .logo-glow {
                width: 260px;
                height: 260px;
            }
            
            .btn-secondary-custom {
                margin-top: 1.2rem;
                display: inline-block;
            }
            
            .step-connector {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: clamp(1.3rem, 2.2vw, 1.5rem);
            }
            
            .nav-link {
                padding: 0.6rem 1rem !important;
                margin: 0.2rem 0;
            }
            
            .hero {
                padding: 7rem 1.5rem 2.5rem;
            }
            
            .hero-title {
                font-size: clamp(2.2rem, 4vw, 3rem);
            }
            
            .hero-subtitle {
                font-size: clamp(1.2rem, 2.2vw, 1.5rem);
            }
            
            .hero-description {
                font-size: clamp(1rem, 2vw, 1.2rem);
                margin-bottom: 3.5rem;
            }
            
            .logo-img {
                width: 160px;
                height: 160px;
                padding: 12px;
            }
            
            .logo-glow {
                width: 220px;
                height: 220px;
            }
            
            .btn-primary-custom, .btn-secondary-custom {
                padding: 1.1rem 3rem;
                font-size: 1.1rem;
                margin: 0.6rem 0;
                width: 100%;
                max-width: 320px;
            }
            
            .section-title {
                margin-bottom: 4rem;
            }
            
            .section-title h2 {
                font-size: clamp(2.2rem, 3.5vw, 2.8rem);
            }
            
            .section-title p {
                font-size: clamp(1.1rem, 2vw, 1.3rem);
            }
            
            .feature-card {
                padding: 2.5rem 2rem;
                margin-bottom: 2rem;
            }
            
            .feature-icon {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .feature-card h5 {
                font-size: 1.5rem;
            }
            
            .step-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }
            
            .step-number {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .footer {
                padding: 4rem 0 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
            
            .hero {
                padding: 6rem 1rem 2rem;
            }
            
            .hero-title {
                font-size: clamp(2rem, 3.5vw, 2.5rem);
            }
            
            .hero-subtitle {
                font-size: clamp(1.1rem, 2vw, 1.3rem);
            }
            
            .hero-description {
                font-size: clamp(0.95rem, 1.8vw, 1.1rem);
                margin-bottom: 3rem;
            }
            
            .logo-img {
                width: 140px;
                height: 140px;
                padding: 10px;
            }
            
            .logo-glow {
                width: 180px;
                height: 180px;
            }
            
            .btn-primary-custom, .btn-secondary-custom {
                padding: 1rem 2.5rem;
                font-size: 1rem;
            }
            
            .feature-card {
                padding: 2rem 1.5rem;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .feature-card h5 {
                font-size: 1.3rem;
            }
            
            .step-number {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .step-card h5 {
                font-size: 1.3rem;
            }
        }

        /* Enhanced Scroll Animations */
        .scroll-animate {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease-out;
            will-change: transform, opacity;
        }

        .scroll-animate.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .loading.hidden {
            opacity: 0;
            transform: scale(0.8);
            pointer-events: none;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255, 255, 255, 0.4);
            border-top: 5px solid var(--white);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            will-change: transform;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Accessibility Enhancements */
        .nav-link:focus, .btn-primary-custom:focus, .btn-secondary-custom:focus {
            outline: 3px solid var(--accent-color);
            outline-offset: 2px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
    </style>
</head>
<body>
    <!-- Scroll Progress -->
    <div class="scroll-progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
        <div class="scroll-progress-bar"></div>
    </div>

    <!-- Loading Screen -->
    <div class="loading" id="loading" aria-hidden="true">
        <div class="spinner"></div>
    </div>

    <!-- Floating Particles -->
    <div class="particles" id="particles" aria-hidden="true"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-landmark me-2" aria-hidden="true"></i><span>LGU Loon</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#home" aria-current="page">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero" role="banner">
        <div class="container">
            <div class="logo-container animate__animated animate__zoomIn">
                <div class="logo-glow" aria-hidden="true"></div>
                <img src="logo.png" alt="Municipality of Loon Logo" class="logo-img">
            </div>
            <h1 class="hero-title">LGU Liquidation System</h1>
            <h2 class="hero-subtitle">Municipality of Loon, Province of Bohol</h2>
            <p class="hero-description">
                Revolutionize your financial management with our cutting-edge digital solution tailored for local government units. Optimize processes, ensure compliance, and boost transparency.
            </p>
            <div class="animate__animated animate__fadeInUp animate__delay-1s">
                <a href="login.php" class="btn btn-primary-custom"><i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>Access System</a>
                <a href="#features" class="btn btn-secondary-custom"><i class="fas fa-info-circle me-2" aria-hidden="true"></i>Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-title scroll-animate">
                <h2>Powerful Features</h2>
                <p>Advanced tools designed to optimize your financial operations and ensure unparalleled transparency.</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
                        <h5>Cash Advance Management</h5>
                        <p>Streamline cash advance requests with automated workflows, real-time tracking, and comprehensive documentation.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                        <h5>Liquidation Tracking</h5>
                        <p>Monitor liquidation activities with intelligent reminders, deadline tracking, and automated compliance checks.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></div>
                        <h5>Audit Trail</h5>
                        <p>Ensure transparency with detailed audit logs, user activity tracking, and robust reporting capabilities.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-bell" aria-hidden="true"></i></div>
                        <h5>Smart Notifications</h5>
                        <p>Stay updated with smart notifications for pending approvals, deadlines, and critical system updates.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-mobile-alt" aria-hidden="true"></i></div>
                        <h5>Mobile Responsive</h5>
                        <p>Access the system anytime, anywhere with a fully responsive design optimized for all devices.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card scroll-animate">
                        <div class="feature-icon"><i class="fas fa-download" aria-hidden="true"></i></div>
                        <h5>Report Generation</h5>
                        <p>Create detailed reports for compliance, auditing, and analysis with customizable templates and export options.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-title scroll-animate">
                <h2>How It Works</h2>
                <p>Effortless steps to streamline your financial processes</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 step-card scroll-animate">
                    <div class="step-number">1</div>
                    <div class="step-connector" aria-hidden="true"></div>
                    <h5>Submit Request</h5>
                    <p>Department heads submit cash advance requests via the secure online portal with all necessary documentation.</p>
                </div>
                <div class="col-lg-3 col-md-6 step-card scroll-animate">
                    <div class="step-number">2</div>
                    <div class="step-connector" aria-hidden="true"></div>
                    <h5>Review & Approve</h5>
                    <p>Authorized personnel review and approve requests through an automated workflow system.</p>
                </div>
                <div class="col-lg-3 col-md-6 step-card scroll-animate">
                    <div class="step-number">3</div>
                    <div class="step-connector" aria-hidden="true"></div>
                    <h5>Track & Monitor</h5>
                    <p>Real-time tracking of transactions with automated reminders for pending liquidations and deadlines.</p>
                </div>
                <div class="col-lg-3 col-md-6 step-card scroll-animate">
                    <div class="step-number">4</div>
                    <h5>Generate Reports</h5>
                    <p>Produce comprehensive reports for compliance, auditing, and financial analysis with export options.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h5>Municipality of Loon</h5>
                    <p>Dedicated to delivering efficient and transparent financial management solutions for enhanced governance and public service.</p>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#" class="text-decoration-none">Cash Advance Guidelines</a></li>
                        <li><a href="#" class="text-decoration-none">Liquidation Procedures</a></li>
                        <li><a href="#" class="text-decoration-none">User Manual</a></li>
                        <li><a href="#" class="text-decoration-none">Support Center</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>Contact Information</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i>Municipality of Loon, Bohol</li>
                        <li><i class="fas fa-phone me-2" aria-hidden="true"></i>+63 38 xxx-xxxx</li>
                        <li><i class="fas fa-envelope me-2" aria-hidden="true"></i>info@loon.gov.ph</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>System Status</h5>
                    <p><i class="fas fa-circle text-success me-2" aria-hidden="true"></i>All systems operational</p>
                    <p><i class="fas fa-clock me-2" aria-hidden="true"></i>24/7 availability</p>
                    <p><i class="fas fa-shield-alt me-2" aria-hidden="true"></i>Secure & compliant</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Municipality of Loon, Bohol. All rights reserved. | Powered by LGU Digital Solutions</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loading screen with enhanced animation
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loading = document.getElementById('loading');
                loading.style.opacity = '0';
                loading.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    loading.classList.add('hidden');
                }, 600);
            }, 800);
        });

        // Enhanced Scroll Progress with smooth animation
        let scrollProgress = 0;
        window.addEventListener('scroll', () => {
            const scrollProgressBar = document.querySelector('.scroll-progress-bar');
            const scrollTop = window.scrollY;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const newProgress = (scrollTop / scrollHeight) * 100;
            
            // Smooth progress animation
            const progressDiff = newProgress - scrollProgress;
            scrollProgress += progressDiff * 0.1;
            
            scrollProgressBar.style.width = `${scrollProgress}%`;
            scrollProgressBar.parentElement.setAttribute('aria-valuenow', Math.round(scrollProgress));
        });

        // Enhanced floating particles animation with more variety
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 100;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 8 + 6) + 's';
                particle.style.opacity = Math.random() * 0.6 + 0.3;
                particle.style.transform = `scale(${Math.random() * 0.8 + 0.4})`;
                
                // Add different sizes and colors
                if (i % 3 === 0) {
                    particle.style.width = '6px';
                    particle.style.height = '6px';
                    particle.style.background = 'var(--gradient-gold)';
                } else if (i % 2 === 0) {
                    particle.style.width = '10px';
                    particle.style.height = '10px';
                    particle.style.background = 'var(--gradient-primary)';
                }
                
                particles.appendChild(particle);
            }
        }

        // Enhanced navbar scroll effect with smooth transitions
        let lastScrollY = 0;
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 50) {
                navbar.classList.add('scrolled');
                if (currentScrollY > lastScrollY && currentScrollY > 100) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
            } else {
                navbar.classList.remove('scrolled');
                navbar.style.transform = 'translateY(0)';
            }
            
            lastScrollY = currentScrollY;
        });

        // Enhanced scroll animations with staggered effects
        function animateOnScroll() {
            const elements = document.querySelectorAll('.scroll-animate');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('active');
                            entry.target.style.transitionDelay = `${index * 0.1}s`;
                        }, index * 50);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -100px 0px'
            });

            elements.forEach(element => {
                observer.observe(element);
            });
        }

        // Enhanced smooth scrolling with easing
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 90;
                    const startPosition = window.pageYOffset;
                    const distance = offsetTop - startPosition;
                    const duration = 1000;
                    let start = null;

                    function animation(currentTime) {
                        if (start === null) start = currentTime;
                        const timeElapsed = currentTime - start;
                        const run = ease(timeElapsed, startPosition, distance, duration);
                        window.scrollTo(0, run);
                        if (timeElapsed < duration) requestAnimationFrame(animation);
                    }

                    function ease(t, b, c, d) {
                        t /= d / 2;
                        if (t < 1) return c / 2 * t * t + b;
                        t--;
                        return -c / 2 * (t * (t - 2) - 1) + b;
                    }

                    requestAnimationFrame(animation);
                }
            });
        });

        // Enhanced feature card hover effects with 3D transforms
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.addEventListener('mouseenter', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `translateY(-25px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                this.style.transition = 'all 0.5s ease';
                
                // Add glow effect
                this.style.boxShadow = `0 30px 60px rgba(96, 165, 250, 0.3), 0 0 30px rgba(96, 165, 250, 0.2)`;
            });
            
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `translateY(-25px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) rotateX(0) rotateY(0) scale(1)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
        });

        // Enhanced step card animations with ripple effect
        document.querySelectorAll('.step-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                const stepNumber = this.querySelector('.step-number');
                stepNumber.style.transform = 'scale(1.15) rotate(10deg)';
                stepNumber.style.boxShadow = '0 15px 30px rgba(96, 165, 250, 0.4)';
                
                // Add ripple effect
                const ripple = document.createElement('div');
                ripple.style.position = 'absolute';
                ripple.style.width = '100%';
                ripple.style.height = '100%';
                ripple.style.background = 'radial-gradient(circle, rgba(96, 165, 250, 0.1) 0%, transparent 70%)';
                ripple.style.borderRadius = '50%';
                ripple.style.top = '50%';
                ripple.style.left = '50%';
                ripple.style.transform = 'translate(-50%, -50%) scale(0)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                ripple.style.pointerEvents = 'none';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            card.addEventListener('mouseleave', function() {
                const stepNumber = this.querySelector('.step-number');
                stepNumber.style.transform = 'scale(1) rotate(0)';
                stepNumber.style.boxShadow = 'var(--shadow-md)';
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                0% { transform: translate(-50%, -50%) scale(0); opacity: 1; }
                100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Enhanced button click effects
        document.querySelectorAll('.btn-primary-custom, .btn-secondary-custom').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.position = 'absolute';
                ripple.style.width = '20px';
                ripple.style.height = '20px';
                ripple.style.background = 'rgba(255, 255, 255, 0.6)';
                ripple.style.borderRadius = '50%';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.transform = 'translate(-50%, -50%) scale(0)';
                ripple.style.animation = 'buttonRipple 0.6s ease-out';
                ripple.style.pointerEvents = 'none';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add button ripple animation
        const buttonStyle = document.createElement('style');
        buttonStyle.textContent = `
            @keyframes buttonRipple {
                0% { transform: translate(-50%, -50%) scale(0); opacity: 1; }
                100% { transform: translate(-50%, -50%) scale(20); opacity: 0; }
            }
        `;
        document.head.appendChild(buttonStyle);

        // Initialize everything with enhanced timing
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            animateOnScroll();

            // Enhanced active navigation with smooth transitions
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('section[id]');

            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (scrollY >= (sectionTop - 200)) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            });

            // Add parallax effect to hero section
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const hero = document.querySelector('.hero');
                const logoContainer = document.querySelector('.logo-container');
                
                if (hero && logoContainer) {
                    const rate = scrolled * -0.3;
                    logoContainer.style.transform = `translateY(${rate}px)`;
                }
            });

            // Add keyboard navigation support
            document.querySelectorAll('.nav-link, .btn-primary-custom, .btn-secondary-custom').forEach(element => {
                element.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        element.click();
                    }
                });
            });
        });

        // Enhanced touch support for mobile devices
        if ('ontouchstart' in window) {
            document.querySelectorAll('.feature-card, .step-card').forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                card.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }

        // Enhanced typing effect to hero title with cursor
        function typeWriter(element, text, speed = 80) {
            let i = 0;
            element.innerHTML = '<span class="typing-text"></span><span class="cursor">|</span>';
            const typingText = element.querySelector('.typing-text');
            const cursor = element.querySelector('.cursor');
            
            function type() {
                if (i < text.length) {
                    typingText.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                } else {
                    cursor.style.animation = 'blink 0.7s step-end infinite';
                }
            }
            type();
        }

        // Add cursor blink animation
        const cursorStyle = document.createElement('style');
        cursorStyle.textContent = `
            .cursor {
                display: inline-block;
                width: 2px;
                height: 1em;
                background: var(--white);
                margin-left: 2px;
                vertical-align: bottom;
            }
            @keyframes blink {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0; }
            }
        `;
        document.head.appendChild(cursorStyle);

        // Initialize typing effect after page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                const heroTitle = document.querySelector('.hero-title');
                if (heroTitle) {
                    const originalText = heroTitle.textContent;
                    typeWriter(heroTitle, originalText, 80);
                }
            }, 2000);
        });
    </script>
</body>
</html>