<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Post;
use App\Models\Testimonial;
use App\Models\Review;
use App\Models\ScoreSheet;
use App\Models\ContactSubmission;

class PublicController
{
    private $db;

    public function __construct($container)
    {
        $this->db = $container->get('db');
    }

    public function getAllPosts(Request $request, Response $response): Response
    {
        try {
            $post = new Post($this->db);
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            $posts = $post->getPublishedPosts($limit, $offset);
            $total = $post->getTotalCount();

            $data = [
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_posts' => $total,
                        'has_next' => $page < ceil($total / $limit)
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch posts']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getPostBySlug(Request $request, Response $response, $args): Response
    {
        try {
            $post = new Post($this->db);
            $postData = $post->getBySlug($args['slug']);

            if (!$postData) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Post not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $postData]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch post']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getTestimonials(Request $request, Response $response): Response
    {
        try {
            $testimonial = new Testimonial($this->db);
            $featuredOnly = filter_var($request->getQueryParams()['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $testimonials = $testimonial->getApproved($featuredOnly);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $testimonials]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch testimonials']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getReviews(Request $request, Response $response): Response
    {
        try {
            $review = new Review($this->db);
            $minRating = $request->getQueryParams()['min_rating'] ?? null;
            $limit = (int)($request->getQueryParams()['limit'] ?? 20);

            $reviews = $review->getReviews($minRating, $limit);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $reviews]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch reviews']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getScores(Request $request, Response $response): Response
    {
        try {
            $scoreSheet = new ScoreSheet($this->db);
            $featuredOnly = filter_var($request->getQueryParams()['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $examType = $request->getQueryParams()['exam_type'] ?? null;

            $scores = $scoreSheet->getScores($featuredOnly, $examType);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $scores]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch score sheets']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function submitContact(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);

            $required = ['name', 'email', 'subject', 'message'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode(['success' => false, 'error' => "Field '$field' is required"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            $contactSubmission = new ContactSubmission($this->db);
            $submissionData = [
                'name' => htmlspecialchars($data['name']),
                'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                'phone' => htmlspecialchars($data['phone'] ?? ''),
                'subject' => htmlspecialchars($data['subject']),
                'message' => htmlspecialchars($data['message']),
                'source_page' => htmlspecialchars($data['source_page'] ?? 'contact-form')
            ];

            $id = $contactSubmission->create($submissionData);

            if ($id) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Thank you for your message. We will get back to you soon!'
                ]));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to submit contact form']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to process contact form']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}