<?php
require_once 'includes/config.php';

// Allow access if logged in as customer OR staff
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Determine which header to use
if (isset($_SESSION['user_id'])) {
    // Staff
    require_once 'includes/header.php';
} else {
    // Customer
    include 'includes/customer_header.php';
}
?>

<style>
    /* ===== RESET & BASE STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .container {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }

    @media (min-width: 576px) {
        .container {
            max-width: 540px;
        }
    }

    @media (min-width: 768px) {
        .container {
            max-width: 720px;
        }
    }

    @media (min-width: 992px) {
        .container {
            max-width: 960px;
        }
    }

    @media (min-width: 1200px) {
        .container {
            max-width: 1140px;
        }
    }

    /* ===== ABOUT CARD ===== */
    .about-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: none;
        overflow: hidden;
        margin: 20px 0;
        animation: fadeInUp 0.8s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card-header {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    @media (min-width: 768px) {
        .card-header {
            padding: 25px 30px;
        }
    }

    .owner-logo {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 3px solid white;
        object-fit: cover;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        animation: pulse 2s infinite;
    }

    @media (min-width: 768px) {
        .owner-logo {
            width: 60px;
            height: 60px;
        }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .card-header h2 {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
        flex: 1;
    }

    @media (min-width: 768px) {
        .card-header h2 {
            font-size: 1.8rem;
        }
    }

    .card-header h2 i {
        margin-right: 8px;
    }

    .card-body {
        padding: 20px;
    }

    @media (min-width: 768px) {
        .card-body {
            padding: 40px;
        }
    }

    .lead {
        font-size: 1rem;
        color: #666;
        margin-bottom: 20px;
        font-weight: 400;
    }

    @media (min-width: 768px) {
        .lead {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
    }

    /* ===== SECTION STYLES ===== */
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #008080;
        margin: 25px 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    @media (min-width: 768px) {
        .section-title {
            font-size: 1.3rem;
            margin: 30px 0 15px;
        }
    }

    .section-title i {
        font-size: 1.2rem;
    }

    .section-text {
        font-size: 0.9rem;
        color: #555;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    @media (min-width: 768px) {
        .section-text {
            font-size: 1rem;
            line-height: 1.8;
        }
    }

    hr {
        margin: 25px 0;
        border: 0;
        border-top: 1px solid #eee;
    }

    /* ===== TEAM GRID - 4x4 on Mobile ===== */
    .team-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 20px;
    }

    @media (max-width: 480px) {
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
    }

    @media (min-width: 768px) and (max-width: 991px) {
        .team-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .team-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #eee;
        animation: fadeIn 0.6s ease backwards;
    }

    .team-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,128,128,0.1);
        border-color: #008080;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Stagger team cards */
    .team-card:nth-child(1) { animation-delay: 0.1s; }
    .team-card:nth-child(2) { animation-delay: 0.15s; }
    .team-card:nth-child(3) { animation-delay: 0.2s; }
    .team-card:nth-child(4) { animation-delay: 0.25s; }
    .team-card:nth-child(5) { animation-delay: 0.3s; }

    .team-card h5 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    @media (min-width: 768px) {
        .team-card h5 {
            font-size: 1rem;
        }
    }

    .team-card p {
        font-size: 0.7rem;
        color: #666;
        margin: 0;
        line-height: 1.3;
    }

    @media (min-width: 768px) {
        .team-card p {
            font-size: 0.8rem;
        }
    }

    .team-card i {
        font-size: 1.5rem;
        color: #008080;
        margin-bottom: 8px;
    }

    @media (min-width: 768px) {
        .team-card i {
            font-size: 2rem;
        }
    }

    /* ===== BLOCKQUOTE ===== */
    .blockquote {
        background: #f8f9fa;
        border-left: 4px solid #008080;
        padding: 15px;
        border-radius: 10px;
        margin: 20px 0;
        font-style: italic;
        color: #555;
        font-size: 0.9rem;
    }

    @media (min-width: 768px) {
        .blockquote {
            padding: 20px;
            font-size: 1rem;
        }
    }

    .blockquote i {
        color: #008080;
        margin-right: 8px;
    }

    /* ===== SOCIAL LINKS ===== */
    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        justify-content: center;
    }

    .social-link {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.3s;
        text-decoration: none;
    }

    .social-link:hover {
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 15px rgba(0,128,128,0.3);
        color: white;
    }

    @media (min-width: 768px) {
        .social-link {
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
        }
    }

    /* ===== STATS SECTION ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin: 30px 0;
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
    }

    .stat-item {
        text-align: center;
        padding: 15px 10px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 12px;
        transition: all 0.3s;
    }

    .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }

    .stat-number {
        font-size: 1.3rem;
        font-weight: 700;
        color: #008080;
        margin-bottom: 5px;
    }

    @media (min-width: 768px) {
        .stat-number {
            font-size: 2rem;
        }
    }

    .stat-label {
        font-size: 0.7rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (min-width: 768px) {
        .stat-label {
            font-size: 0.8rem;
        }
    }

    /* ===== TIMELINE ===== */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 25px;
        margin-bottom: 25px;
        border-left: 2px solid #008080;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #008080;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .timeline-year {
        font-size: 0.9rem;
        font-weight: 600;
        color: #008080;
        margin-bottom: 5px;
    }

    @media (min-width: 768px) {
        .timeline-year {
            font-size: 1rem;
        }
    }

    .timeline-text {
        font-size: 0.85rem;
        color: #555;
        line-height: 1.5;
    }

    @media (min-width: 768px) {
        .timeline-text {
            font-size: 0.95rem;
        }
    }

    /* ===== VALUES SECTION ===== */
    .values-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin: 25px 0;
    }

    @media (max-width: 480px) {
        .values-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }

    .value-card {
        text-align: center;
        padding: 15px 10px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border: 1px solid #eee;
    }

    .value-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,128,128,0.1);
        border-color: #008080;
    }

    .value-icon {
        font-size: 1.5rem;
        color: #008080;
        margin-bottom: 8px;
    }

    @media (min-width: 768px) {
        .value-icon {
            font-size: 2rem;
        }
    }

    .value-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    @media (min-width: 768px) {
        .value-title {
            font-size: 1rem;
        }
    }

    .value-desc {
        font-size: 0.7rem;
        color: #666;
        line-height: 1.3;
    }

    @media (min-width: 768px) {
        .value-desc {
            font-size: 0.8rem;
        }
    }

    /* ===== MOBILE SPECIFIC STYLES ===== */
    @media (max-width: 767px) {
        .card-header {
            padding: 15px;
        }

        .card-body {
            padding: 15px;
        }

        .section-title {
            margin: 20px 0 8px;
        }

        hr {
            margin: 20px 0;
        }

        .blockquote {
            padding: 12px;
            font-size: 0.85rem;
        }

        .timeline-item {
            padding-left: 20px;
            margin-bottom: 20px;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        .card-header h2 {
            font-size: 1.1rem;
        }

        .owner-logo {
            width: 40px;
            height: 40px;
        }

        .lead {
            font-size: 0.9rem;
        }

        .section-text {
            font-size: 0.8rem;
        }

        .team-card {
            padding: 10px 5px;
        }

        .team-card h5 {
            font-size: 0.8rem;
        }

        .team-card p {
            font-size: 0.65rem;
        }

        .team-card i {
            font-size: 1.3rem;
        }

        .stat-number {
            font-size: 1rem;
        }

        .stat-label {
            font-size: 0.65rem;
        }

        .value-icon {
            font-size: 1.3rem;
        }

        .value-title {
            font-size: 0.8rem;
        }

        .value-desc {
            font-size: 0.65rem;
        }

        .social-link {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }
    }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="about-card">
                <div class="card-header">
                    <img src="assets/images/owner.jpg" alt="Owner Logo" class="owner-logo">
                    <h2><i class="fas fa-info-circle"></i>About Jen's Kakanin</h2>
                </div>
                
                <div class="card-body">
                    <!-- Company Stats - 4x4 Grid on Mobile -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">2020</div>
                            <div class="stat-label">Founded</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">1000+</div>
                            <div class="stat-label">Customers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">4.9</div>
                            <div class="stat-label">Rating</div>
                        </div>
                    </div>

                    <!-- Tagline -->
                    <p class="lead text-center">
                        <i class="fas fa-quote-left me-2" style="color: #008080;"></i>
                        Sari-saring sarap, siguradong tatak Pinoy!
                        <i class="fas fa-quote-right ms-2" style="color: #008080;"></i>
                    </p>
                    
                    <!-- Our Story Section -->
                    <div class="section-title">
                        <i class="fas fa-history"></i>
                        Our Story
                    </div>
                    <p class="section-text">
                        Jen's Kakanin started as a small home-based business in 2020, specializing in traditional Filipino rice cakes made from family recipes passed down through generations. Today, we continue to bring the authentic taste of home to every Filipino family, using only the freshest ingredients and time-honored techniques.
                    </p>

                    <!-- Timeline -->
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-year">2020</div>
                            <div class="timeline-text">Started as a small home-based business</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2022</div>
                            <div class="timeline-text">Expanded to online ordering system</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2024</div>
                            <div class="timeline-text">Reached 1000+ happy customers</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2026</div>
                            <div class="timeline-text">Launched new premium product line</div>
                        </div>
                    </div>
                    
                    <!-- Our Mission Section -->
                    <div class="section-title">
                        <i class="fas fa-bullseye"></i>
                        Our Mission
                    </div>
                    <p class="section-text">
                        To preserve and promote the rich culinary heritage of the Philippines through high-quality kakanin, while providing a seamless online ordering experience for our customers. We are committed to excellence, community, and the delicious flavors that bring people together.
                    </p>

                    <!-- Our Values - 4x4 Grid on Mobile -->
                    <div class="section-title">
                        <i class="fas fa-heart"></i>
                        Our Values
                    </div>
                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="value-title">Quality</div>
                            <div class="value-desc">Fresh ingredients daily</div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div class="value-title">Tradition</div>
                            <div class="value-desc">Family recipes since 2020</div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="value-title">Community</div>
                            <div class="value-desc">Bringing people together</div>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="value-title">Excellence</div>
                            <div class="value-desc">Best service guaranteed</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Development Team Section -->
                    <div class="section-title">
                        <i class="fas fa-laptop-code"></i>
                        Development Team
                    </div>
                    <p class="text-center text-muted mb-4">
                        <strong>DIP IT 3 BLOCK 7</strong>
                    </p>
                    
                    <!-- Team Grid - 4x4 on Mobile -->
                    <div class="team-grid">
                        <div class="team-card">
                            <i class="fas fa-user-circle"></i>
                            <h5>Piolo Niño Salaño</h5>
                            <p>Lead Analyst / Developer</p>
                        </div>
                        <div class="team-card">
                            <i class="fas fa-user-circle"></i>
                            <h5>Apolonio Ygrubay III</h5>
                            <p>Backend Developer</p>
                        </div>
                        <div class="team-card">
                            <i class="fas fa-user-circle"></i>
                            <h5>Jonnel Olid</h5>
                            <p>Frontend Developer</p>
                        </div>
                        <div class="team-card">
                            <i class="fas fa-user-circle"></i>
                            <h5>Lorentz Asis</h5>
                            <p>Database Administrator</p>
                        </div>
                        <div class="team-card">
                            <i class="fas fa-user-circle"></i>
                            <h5>Ahron Salceda</h5>
                            <p>UI/UX Designer</p>
                        </div>
                    </div>

                    <!-- Testimonial Blockquote -->
                    <div class="blockquote">
                        <i class="fas fa-quote-left"></i>
                        We pour our hearts into every kakanin we make, ensuring that each bite brings back memories of home and family. Our team is dedicated to sharing the authentic flavors of Filipino cuisine with the world.
                    </div>

                    <!-- Social Links -->
                    <div class="social-links">
                        <a href="https://www.facebook.com/jenelyndaantos.patagan" target="_blank" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>

                    <!-- Contact Info -->
                    <div class="text-center mt-4">
                        <p class="small text-muted">
                            <i class="fas fa-map-marker-alt me-2" style="color: #008080;"></i>
                            Brgy 83-B Cogon San Jose, Tacloban City
                        </p>
                        <p class="small text-muted">
                            <i class="fas fa-phone me-2" style="color: #008080;"></i>
                            0935 606 2163
                        </p>
                        <p class="small text-muted">
                            <i class="fas fa-envelope me-2" style="color: #008080;"></i>
                            jenskakanin@gmail.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>