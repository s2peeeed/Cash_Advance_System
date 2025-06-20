<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Advance Monitoring</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-navy: #0f1629;
            --secondary-navy: #1a2238;
            --accent-blue: #2563eb;
            --light-blue: #3b82f6;
            --pure-white: #ffffff;
            --off-white: #f8fafc;
            --light-gray: #e2e8f0;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --shadow-light: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-large: 0 10px 25px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--pure-white);
            overflow-x: hidden;
        }

        /* Enhanced Navigation */
        .navbar {
            background: var(--primary-navy);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .navbar.scrolled {
            box-shadow: var(--shadow-large);
            background: rgba(15, 22, 41, 0.95);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--pure-white) !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            color: var(--light-blue) !important;
            transform: translateY(-1px);
        }

        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.75rem 1.5rem !important;
            margin: 0 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--pure-white) !important;
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--light-blue);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 80%;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: linear-gradient(45deg, transparent 0%, rgba(59, 130, 246, 0.1) 100%);
            clip-path: polygon(20% 0%, 100% 0%, 100% 100%, 0% 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.2);
            color: var(--light-blue);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            color: var(--pure-white);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: clamp(1.125rem, 2vw, 1.5rem);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
            line-height: 1.6;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: var(--accent-blue);
            border: none;
            color: var(--pure-white);
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-medium);
        }

        .btn-primary-custom:hover {
            background: var(--light-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
            color: var(--pure-white);
        }

        .btn-secondary-custom {
            background: transparent;
            border: 2px solid var(--pure-white);
            color: var(--pure-white);
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: var(--pure-white);
            color: var(--primary-navy);
            transform: translateY(-2px);
        }

        .hero-image {
            position: relative;
            z-index: 2;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            transition: transform 0.3s ease;
        }

        .hero-image img:hover {
            transform: scale(1.05);
        }

        /* Features Section */
        .features {
            padding: 6rem 0;
            background: var(--off-white);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
            line-height: 1.3;
            letter-spacing: -0.02em;
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .feature-card {
            background: var(--pure-white);
            border-radius: 12px;
            padding: 2.5rem;
            height: 100%;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-large);
            border-color: var(--light-blue);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-blue), var(--light-blue));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--pure-white);
            font-size: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 6rem 0;
            background: var(--pure-white);
        }

        .process-step {
            text-align: center;
            padding: 2rem 1rem;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--accent-blue);
            color: var(--pure-white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .step-connector {
            position: absolute;
            top: 30px;
            left: calc(50% + 30px);
            width: calc(100% - 60px);
            height: 2px;
            background: var(--light-gray);
            z-index: 1;
        }

        .process-step:last-child .step-connector {
            display: none;
        }

        .step-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .step-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: var(--primary-navy);
            color: var(--pure-white);
            text-align: center;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .cta-description {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--secondary-navy);
            color: var(--pure-white);
            padding: 3rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h5 {
            color: var(--light-blue);
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .footer-section p, .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            line-height: 1.6;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: var(--light-blue);
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero {
                text-align: center;
                padding: 4rem 0;
            }

            .hero-buttons {
                justify-content: center;
            }

            .btn-primary-custom,
            .btn-secondary-custom {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .feature-card {
                margin-bottom: 2rem;
            }

            .step-connector {
                display: none;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Scroll Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Loading States */
        .loading {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading.active {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-cube me-2"></i>
                Municipal Government of Loon, Bohol
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#process">Process</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <div class="hero-badge fade-in">
                            <i class="fas fa-star me-1"></i>
                            Trusted by LGU Loon
                        </div>
                        <h1 class="hero-title fade-in">
                            Cash Advance Monitoring
                        </h1>
                        <p class="hero-subtitle fade-in">
                           Transform LGU financial processes with our smart Cash Advance Monitoring System — built to streamline requests, track liquidations, ensure compliance, and empower transparent, efficient fund management.       
                        </p>
                        <div class="hero-buttons fade-in">
                            <a href="#contact" class="btn-primary-custom">
                                <i class="fas fa-rocket me-2"></i>
                                Get Started
                            </a>
                            <a href="#features" class="btn-secondary-custom">
                                <i class="fas fa-play me-2"></i>
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image fade-in">
                        <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 1.2rem; font-weight: 600;">
                            <i class="fas fa-chart-line" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-background-shape"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge fade-in">Features</div>
                <h2 class="section-title fade-in">Why Choose Our Platform</h2>
                <p class="section-description fade-in">
                    Advanced tools designed to optimize your financial operations and ensure unparalleled transparency.
                </p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h5 class="feature-title">Cash Advance Management</h5>
                        <p class="feature-description">
                            Streamline cash advance requests with automated workflows, real-time tracking, and comprehensive documentation.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="feature-title">Liquidation Tracking</h5>
                        <p class="feature-description">
                            Monitor liquidation activities with intelligent reminders, deadline tracking, and automated compliance checks.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="feature-title">Audit Trail</h5>
                        <p class="feature-description">
                            Ensure transparency with detailed audit logs, user activity tracking, and robust reporting capabilities.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h5 class="feature-title">Smart Notifications</h5>
                        <p class="feature-description">
                            Stay updated with smart notifications for pending approvals, deadlines, and critical system updates.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5 class="feature-title">Mobile Responsive</h5>
                        <p class="feature-description">
                            Access the system anytime, anywhere with a fully responsive design optimized for all devices.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <h5 class="feature-title">Report Generation</h5>
                        <p class="feature-description">
                            Create detailed reports for compliance, auditing, and analysis with customizable templates and export options.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="process" class="how-it-works">
        <div class="container">
            <div class="section-header">
                <div class="section-badge fade-in">Process</div>
                <h2 class="section-title fade-in">How It Works</h2>
                <p class="section-description fade-in">
                    Effortless steps to streamline your financial processes.
                </p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in">
                        <div class="step-number">1</div>
                        <div class="step-connector"></div>
                        <h5 class="step-title">Submit Request</h5>
                        <p class="step-description">
                            Department heads submit cash advance requests via the secure online portal with all necessary documentation.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in">
                        <div class="step-number">2</div>
                        <div class="step-connector"></div>
                        <h5 class="step-title">Review & Approve</h5>
                        <p class="step-description">
                            Authorized personnel review and approve requests through an automated workflow system.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in">
                        <div class="step-number">3</div>
                        <div class="step-connector"></div>
                        <h5 class="step-title">Track & Monitor</h5>
                        <p class="step-description">
                            Real-time tracking of transactions with automated reminders for pending liquidations and deadlines.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in">
                        <div class="step-number">4</div>
                        <h5 class="step-title">Generate Reports</h5>
                        <p class="step-description">
                            Produce comprehensive reports for compliance, auditing, and financial analysis with export options.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="fade-in">
                <h2 class="cta-title">Ready to Enhance Financial Transparency?</h2>
                <p class="cta-description">
                    Access the portal to streamline cash advance requests, monitor liquidations, and ensure full compliance.
                </p>
                <a href="login.php" class="btn-primary-custom">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Start Your Free Trial
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h5>Municipality of Loon</h5>
                    <p>Dedicated to delivering efficient and transparent financial management solutions for enhanced governance and public service.</p>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#">Cash Advance Guidelines</a></li>
                        <li><a href="#">Liquidation Procedures</a></li>
                        <li><a href="#">User Manual</a></li>
                        <li><a href="#">Support Center</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>Contact Information</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Municipality of Loon, Bohol</li>
                        <li><i class="fas fa-phone me-2"></i> +63 38 xxx-xxxx</li>
                        <li><i class="fas fa-envelope me-2"></i> info@loon.gov.ph</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h5>System Status</h5>
                    <ul>
                        <li><i class="fas fa-check-circle me-2" style="color: #22c55e;"></i> All systems operational</li>
                        <li><i class="fas fa-clock me-2"></i> 24/7 availability</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Municipal Government of Loon, Bohol. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Active navigation link
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Initialize animations on load
        window.addEventListener('load', function() {
            document.querySelectorAll('.fade-in').forEach((el, index) => {
                setTimeout(() => {
                    el.style.transitionDelay = `${index * 0.1}s`;
                }, 100);
            });
        });
    </script>
</body>
</html>