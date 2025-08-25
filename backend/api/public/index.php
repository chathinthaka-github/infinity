<?php
use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add CORS Middleware
$app->add(new App\Middleware\CorsMiddleware());

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Database Connection
$container->set('db', function() {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
});

// Google Drive Service
$container->set('googleDrive', function() {
    return new App\Services\GoogleDriveService();
});

// Public Routes
$app->group('/api/public', function ($group) {
    $group->get('/posts', App\Controllers\PublicController::class . ':getAllPosts');
    $group->get('/posts/{slug}', App\Controllers\PublicController::class . ':getPostBySlug');
    $group->get('/testimonials', App\Controllers\PublicController::class . ':getTestimonials');
    $group->get('/reviews', App\Controllers\PublicController::class . ':getReviews');
    $group->get('/scores', App\Controllers\PublicController::class . ':getScores');
    $group->post('/contact', App\Controllers\PublicController::class . ':submitContact');
});

// Auth Routes
$app->group('/api/auth', function ($group) {
    $group->post('/register', App\Controllers\AuthController::class . ':register');
    $group->post('/login', App\Controllers\AuthController::class . ':login');
    $group->post('/logout', App\Controllers\AuthController::class . ':logout');
});

// Protected Auth Routes
$app->group('/api/auth', function ($group) {
    $group->get('/me', App\Controllers\AuthController::class . ':getCurrentUser');
})->add(new App\Middleware\AuthMiddleware($container));

// Student Routes
$app->group('/api/student', function ($group) {
    $group->get('/resources', App\Controllers\StudentController::class . ':getMyResources');
    $group->get('/resources/{category}', App\Controllers\StudentController::class . ':getResourcesByCategory');
    $group->get('/resource/view/{id}', App\Controllers\StudentController::class . ':viewResource');
    $group->post('/resource/progress', App\Controllers\StudentController::class . ':updateProgress');
    $group->get('/progress/summary', App\Controllers\StudentController::class . ':getProgressSummary');
    $group->get('/profile', App\Controllers\StudentController::class . ':getProfile');
    $group->put('/profile', App\Controllers\StudentController::class . ':updateProfile');
})->add(new App\Middleware\AuthMiddleware($container));

// Admin Routes
$app->group('/api/admin', function ($group) {
    $group->get('/dashboard/stats', App\Controllers\AdminController::class . ':getDashboardStats');

    // User Management
    $group->get('/users', App\Controllers\AdminController::class . ':getAllUsers');
    $group->get('/users/{id}', App\Controllers\AdminController::class . ':getUserById');
    $group->put('/users/{id}/status', App\Controllers\AdminController::class . ':updateUserStatus');
    $group->get('/users/{id}/progress', App\Controllers\AdminController::class . ':getUserProgress');

    // Resource Management
    $group->get('/resources', App\Controllers\AdminController::class . ':getAllResources');
    $group->post('/resources/upload', App\Controllers\AdminController::class . ':uploadResource');
    $group->put('/resources/{id}', App\Controllers\AdminController::class . ':updateResource');
    $group->delete('/resources/{id}', App\Controllers\AdminController::class . ':deleteResource');
    $group->post('/resources/assign', App\Controllers\AdminController::class . ':assignResource');
    $group->put('/resources/complete', App\Controllers\AdminController::class . ':markCategoryComplete');

    // Content Management
    $group->get('/posts', App\Controllers\AdminController::class . ':getAllPosts');
    $group->post('/posts', App\Controllers\AdminController::class . ':createPost');
    $group->put('/posts/{id}', App\Controllers\AdminController::class . ':updatePost');
    $group->delete('/posts/{id}', App\Controllers\AdminController::class . ':deletePost');

    $group->get('/testimonials', App\Controllers\AdminController::class . ':getAllTestimonials');
    $group->post('/testimonials', App\Controllers\AdminController::class . ':createTestimonial');
    $group->put('/testimonials/{id}', App\Controllers\AdminController::class . ':updateTestimonial');
    $group->delete('/testimonials/{id}', App\Controllers\AdminController::class . ':deleteTestimonial');

    $group->get('/reviews', App\Controllers\AdminController::class . ':getAllReviews');
    $group->post('/reviews', App\Controllers\AdminController::class . ':createReview');
    $group->put('/reviews/{id}', App\Controllers\AdminController::class . ':updateReview');
    $group->delete('/reviews/{id}', App\Controllers\AdminController::class . ':deleteReview');

    $group->get('/scores', App\Controllers\AdminController::class . ':getAllScores');
    $group->post('/scores', App\Controllers\AdminController::class . ':createScore');
    $group->put('/scores/{id}', App\Controllers\AdminController::class . ':updateScore');
    $group->delete('/scores/{id}', App\Controllers\AdminController::class . ':deleteScore');

})->add(new App\Middleware\AdminMiddleware($container));

$app->run();