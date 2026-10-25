<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Review API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .section {
            margin: 30px 0;
        }
        .section h2 {
            color: #764ba2;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        .endpoint {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .method {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 0.9em;
        }
        .get { background: #28a745; color: white; }
        .post { background: #007bff; color: white; }
        .put { background: #ffc107; color: #333; }
        .delete { background: #dc3545; color: white; }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Movie Review API</h1>
        <p class="subtitle">RESTful API for Movie Review Application</p>
        
        <span class="status">✓ API Active</span>
        
        <div class="info-box">
            <strong>Base URL:</strong> <code>http://localhost/movie-review/api</code><br>
            <strong>Version:</strong> 1.0<br>
            <strong>Database:</strong> <?php 
                try {
                    require_once 'api/config/database.php';
                    $conn = getDBConnection();
                    echo '<span style="color: green;">✓ Connected</span>';
                } catch(Exception $e) {
                    echo '<span style="color: red;">✗ Not Connected</span>';
                }
            ?>
        </div>

        <div class="section">
            <h2>📚 Authentication Endpoints</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/auth/register.php</code>
                <p>Register new user account</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/auth/login.php</code>
                <p>Login and get authentication token</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/auth/logout.php</code>
                <p>Logout current session (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/auth/profile.php</code>
                <p>Get current user profile (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method put">PUT</span>
                <code>/auth/update_profile.php</code>
                <p>Update user profile (requires token)</p>
            </div>
        </div>

        <div class="section">
            <h2>🎥 Movies Endpoints</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/get_all.php</code>
                <p>Get all movies with pagination and filters</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/get_detail.php?id={id}</code>
                <p>Get movie detail by ID</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/search.php?q={query}</code>
                <p>Search movies by title, director, or genre</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/trending.php</code>
                <p>Get trending movies</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/top_rated.php</code>
                <p>Get top rated movies</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/by_genre.php?genre={genre}</code>
                <p>Get movies by genre</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/movies/genres.php</code>
                <p>Get list of all genres</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/movies/create.php</code>
                <p>Create new movie (admin only, requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method put">PUT</span>
                <code>/movies/update.php</code>
                <p>Update movie (admin only, requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method delete">DELETE</span>
                <code>/movies/delete.php</code>
                <p>Delete movie (admin only, requires token)</p>
            </div>
        </div>

        <div class="section">
            <h2>⭐ Reviews Endpoints</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/reviews/get_by_movie.php?movie_id={id}</code>
                <p>Get all reviews for a movie</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/reviews/get_by_user.php?user_id={id}</code>
                <p>Get all reviews by a user</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/reviews/check_user_review.php?movie_id={id}</code>
                <p>Check if current user reviewed a movie (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/reviews/create.php</code>
                <p>Create new review (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method put">PUT</span>
                <code>/reviews/update.php</code>
                <p>Update review (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method delete">DELETE</span>
                <code>/reviews/delete.php</code>
                <p>Delete review (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/reviews/mark_helpful.php</code>
                <p>Mark review as helpful (requires token)</p>
            </div>
        </div>

        <div class="section">
            <h2>📋 Watchlist Endpoints</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/watchlist/get.php</code>
                <p>Get user's watchlist (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/watchlist/check.php?movie_id={id}</code>
                <p>Check if movie is in watchlist (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/watchlist/add.php</code>
                <p>Add movie to watchlist (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method delete">DELETE</span>
                <code>/watchlist/remove.php</code>
                <p>Remove movie from watchlist (requires token)</p>
            </div>
        </div>

        <div class="section">
            <h2>📤 Upload Endpoints</h2>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/upload/poster.php</code>
                <p>Upload movie poster (requires token)</p>
            </div>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <code>/upload/profile.php</code>
                <p>Upload profile picture (requires token)</p>
            </div>
        </div>

        <div class="section">
            <h2>📊 Statistics Endpoints</h2>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <code>/stats/dashboard.php</code>
                <p>Get dashboard statistics (admin only, requires token)</p>
            </div>
        </div>

        <div class="info-box">
            <h3>🔐 Authentication</h3>
            <p>Protected endpoints require authentication token in header:</p>
            <code>Authorization: Bearer YOUR_TOKEN_HERE</code>
        </div>

        <div class="info-box">
            <h3>📖 Documentation</h3>
            <p>For detailed request/response examples, please refer to the documentation.</p>
            <p><strong>Test the API:</strong> <a href="test.html">Open Test Page</a></p>
        </div>
    </div>
</body>
</html>