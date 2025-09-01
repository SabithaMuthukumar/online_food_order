<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wow Food - Categories</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional: externalize CSS -->
    <style>
        /* Include your existing CSS styles here or link externally */
        /* ... (your full CSS from earlier) ... */

        /* Search Bar Styles */
        .search-bar {
            text-align: center;
            margin-bottom: 40px;
        }

        .search-bar input {
            width: 80%;
            max-width: 400px;
            padding: 10px 15px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .search-bar input:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
        }
   
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: #f9f9f9;
        font-family: 'Arial', sans-serif;
        color: #333;
        min-height: 100vh;
        overflow-x: hidden;
        font-size: 16px;
        line-height: 1.6;
    }

    /* Animated Background */
    .animated-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            radial-gradient(circle at 20% 50%, rgba(255, 107, 107, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 193, 7, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 40% 80%, rgba(40, 167, 69, 0.05) 0%, transparent 50%);
        z-index: -1;
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    /* Navbar Styles */
    .navbar {
        background-image: url(images/backgrounds/bg.jpg);
        padding: 15px 0;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1000;
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .logo {
        float: left;
    }

    .logo img {
        height: 40px;
    }

    .menu {
        float: right;
    }

    .menu ul {
        list-style: none;
        display: flex;
        align-items: center;
    }

    .menu li {
        margin-left: 25px;
    }

    .menu a {
        color: #444;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
        position: relative;
        font-size: 1rem;
    }

    .menu a:hover {
        color: #e74c3c;
    }

    .menu a.active {
        color: #e74c3c;
    }

    .menu a::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: #e74c3c;
        transition: width 0.3s ease;
    }

    .menu a:hover::after {
        width: 100%;
    }

    .menu a.active::after {
        width: 100%;
    }

    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }

    /* Main Content */
    .main-content {
        margin-top: 80px;
        padding: 40px 0 80px;
    }

    /* Page Title */
    .page-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .page-title h1 {
        font-size: 2.2rem;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .page-title p {
        color: #666;
        font-size: 1rem;
    }

    /* Categories Grid */
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 35px;
        padding: 0 25px 50px;
        max-width: 800px;
        margin: 0 auto 40px;
    }

    .category-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        height: 220px;
    }

    .category-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .category-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        padding: 20px;
        color: white;
    }

    .category-title {
        font-size: 1.4rem;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .category-description {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    /* Hover Effects */
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 28px rgba(0,0,0,0.15), 
                    0 10px 10px rgba(0,0,0,0.12);
        z-index: 1;
    }

    .category-card:hover .category-image {
        transform: scale(1.1);
    }

    /* Explore Button */
    .explore-btn {
        display: inline-block;
        margin-top: 12px;
        padding: 6px 15px;
        background: #e74c3c;
        color: white;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: background 0.3s ease;
    }

    .explore-btn:hover {
        background: #c0392b;
    }

    /* Footer */
    .footer {
        background: #2c3e50;
        padding: 20px 0;
        text-align: center;
    }

    .footer p {
        margin: 0;
        color: #ecf0f1;
        font-size: 0.9rem;
    }

    .footer a {
        color: #e74c3c;
        text-decoration: none;
        font-weight: 600;
    }

    .footer a:hover {
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .menu {
            float: none;
            clear: both;
            margin-top: 15px;
        }

        .menu ul {
            flex-wrap: wrap;
            justify-content: center;
        }

        .menu li {
            margin: 8px 15px;
        }

        .navbar {
            position: relative;
            padding: 15px 0;
        }

        .main-content {
            margin-top: 0;
            padding: 30px 0 50px;
        }

        .page-title h1 {
            font-size: 1.8rem;
        }

        .page-title {
            margin-bottom: 40px;
        }

        .categories-grid {
            grid-template-columns: 1fr;
            max-width: 400px;
            gap: 25px;
            padding: 0 15px 30px;
            margin: 0 auto 30px;
        }
        
        .category-card {
            height: 200px;
        }
    }

    @media (max-width: 480px) {
        .categories-grid {
            padding: 0 15px;
            gap: 20px;
        }
        
        .category-overlay {
            padding: 15px;
        }
        
        .category-title {
            font-size: 1.2rem;
        }
    }
    </style>
</head>
<body>
    <div class="animated-bg"></div>

    <!-- Navbar -->
    <section class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.html"><img src="images/logo.png" alt="Restaurant Logo"></a>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="categories.php" class="active">Categories</a></li>
                    <li><a href="foods.html">Foods</a></li>
                    <li><a href="about-us.html">About Us</a></li>
                    <li><a href="login.html">Login</a></li>
                </ul>
            </div>
            <div class="clearfix"></div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">
            <h1>Our Food Categories</h1>
            <p style="font-size:22px;">Explore our delicious selection</p>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="categorySearch" placeholder="Search categories...">
        </div>

        <div class="categories-grid" id="categoriesGrid">
            <?php
            // Include database connection
            include("back_end/database_connectivity.php");
            
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT Cat_ID, Title, Image, Description FROM categories WHERE Active = 'Yes' ORDER BY Title";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $cat_id = $row['Cat_ID'];
                    $title = htmlspecialchars($row['Title']);
                    $description = htmlspecialchars($row['Description']);
                    $image = !empty($row['Image']) ? 'images/categories/' . $row['Image'] : 'images/categories/default.jpg';
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                    
                    echo '
                    <div class="category-card" data-title="' . strtolower($title) . '">
                        <img src="' . $image . '" alt="' . $title . '" class="category-image">
                        <div class="category-overlay">
                            <h3 class="category-title">' . $title . '</h3>
                            <p class="category-description">' . $description . '</p>
                            <a href="foods.php?category=' . urlencode($slug) . '" class="explore-btn">Explore</a>
                        </div>
                    </div>';
                }
            } else {
                echo "<p style='text-align:center; grid-column: 1 / -1;'>No active categories found.</p>";
            }

            $conn->close();
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Wow Food. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
        </div>
    </footer>

    <!-- JavaScript for Search Filter -->
    <script>
        const searchInput = document.getElementById('categorySearch');
        const cards = document.querySelectorAll('.category-card');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                card.style.display = title.includes(query) ? 'block' : 'none';
            });
        });

        // Intersection animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.category-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(card);
        });

        window.addEventListener('load', function () {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>