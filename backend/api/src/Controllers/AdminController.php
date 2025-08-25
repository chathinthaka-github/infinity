<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\Resource;
use App\Models\UserResource;
use App\Models\Post;
use App\Models\Testimonial;
use App\Models\Review;
use App\Models\ScoreSheet;
use App\Services\GoogleDriveService;

class AdminController
{
    private $db;
    private $googleDrive;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->googleDrive = $container->get('googleDrive');
    }

    public function getDashboardStats(Request $request, Response $response): Response
    {
        try {
            // Get dashboard statistics
            $stats = [];

            // Total students
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
            $stmt->execute();
            $stats['total_students'] = $stmt->fetch()['total'];

            // Active students
            $stmt = $this->db->prepare("SELECT COUNT(*) as active FROM users WHERE role = 'student' AND is_active = 1");
            $stmt->execute();
            $stats['active_students'] = $stmt->fetch()['active'];

            // Total resources
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM resources WHERE is_active = 1");
            $stmt->execute();
            $stats['total_resources'] = $stmt->fetch()['total'];

            // Average completion rate
            $stmt = $this->db->prepare("SELECT AVG(completion_percentage) as avg_completion FROM user_resources");
            $stmt->execute();
            $stats['avg_completion_rate'] = round($stmt->fetch()['avg_completion'], 2);

            // Recent signups (last 30 days)
            $stmt = $this->db->prepare("SELECT COUNT(*) as recent FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $stats['recent_signups'] = $stmt->fetch()['recent'];

            // Recent activities
            $stmt = $this->db->prepare("
                SELECT u.name, ur.category, r.resource_name, ur.completed_at 
                FROM user_resources ur 
                JOIN users u ON ur.user_id = u.id 
                JOIN resources r ON ur.resource_id = r.id 
                WHERE ur.is_completed = 1 AND ur.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY ur.completed_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $stats['recent_activities'] = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch dashboard stats']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // User Management
    public function getAllUsers(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQueryParams()['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $search = $request->getQueryParams()['search'] ?? '';

            $user = new User($this->db);
            $users = $user->getUsersWithStats($limit, $offset);
            $total = $user->getTotalCount();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_users' => $total
                    ]
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch users']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getUserById(Request $request, Response $response, $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $user = new User($this->db);
            $userData = $user->getById($userId);

            if (!$userData) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $userData
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch user']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateUserStatus(Request $request, Response $response, $args): Response
    {
        try {
            $userId = (int)$args['id'];
            $data = json_decode($request->getBody(), true);

            if (!isset($data['is_active'])) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'is_active field required']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $user = new User($this->db);
            $result = $user->updateStatus($userId, (bool)$data['is_active']);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'User status updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update user status']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update user status']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getUserProgress(Request $request, Response $response, $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $userResource = new UserResource($this->db);
            $progress = $userResource->getUserProgress($userId);
            $summary = $userResource->getProgressSummary($userId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'detailed_progress' => $progress,
                    'summary' => $summary
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch user progress']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Resource Management
    public function getAllResources(Request $request, Response $response): Response
    {
        try {
            $resource = new Resource($this->db);
            $resources = $resource->getActiveResources();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $resources
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch resources']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function uploadResource(Request $request, Response $response): Response
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $data = $request->getParsedBody();

            if (!isset($uploadedFiles['file'])) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'No file uploaded']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $file = $uploadedFiles['file'];
            $adminId = $request->getAttribute('user_id');

            // Upload to Google Drive
            $driveResult = $this->googleDrive->uploadFile($file, $data);

            if (!$driveResult['success']) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => $driveResult['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Save to database
            $resource = new Resource($this->db);
            $resourceData = [
                'resource_name' => $data['resource_name'] ?? $file->getClientFilename(),
                'description' => $data['description'] ?? '',
                'resource_type' => $this->getFileType($file->getClientMediaType()),
                'file_size' => $this->formatFileSize($file->getSize()),
                'duration' => $data['duration'] ?? null,
                'google_drive_id' => $driveResult['file_id'],
                'google_drive_url' => $driveResult['view_url'],
                'thumbnail_url' => $driveResult['thumbnail_url'] ?? null,
                'created_by' => $adminId
            ];

            $resourceId = $resource->create($resourceData);

            if ($resourceId) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Resource uploaded successfully',
                    'data' => [
                        'resource_id' => $resourceId,
                        'google_drive_id' => $driveResult['file_id']
                    ]
                ]));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to save resource']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function assignResource(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);
            $adminId = $request->getAttribute('user_id');

            $required = ['user_id', 'resource_id', 'category'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode(['success' => false, 'error' => "Field '$field' is required"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            $validCategories = ['reading', 'listening', 'writing', 'speaking', 'extra_resources'];
            if (!in_array($data['category'], $validCategories)) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid category']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $userResource = new UserResource($this->db);
            $assignmentData = [
                'user_id' => (int)$data['user_id'],
                'resource_id' => (int)$data['resource_id'],
                'category' => $data['category'],
                'assigned_by_admin_id' => $adminId
            ];

            $result = $userResource->assignResource($assignmentData);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Resource assigned successfully'
                ]));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to assign resource']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to assign resource']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function markCategoryComplete(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);
            $adminId = $request->getAttribute('user_id');

            $required = ['user_id', 'category'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode(['success' => false, 'error' => "Field '$field' is required"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            $userResource = new UserResource($this->db);
            $result = $userResource->markCategoryComplete((int)$data['user_id'], $data['category'], $adminId);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Category marked as complete'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to mark category complete']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to mark category complete']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Content Management Methods
    public function createPost(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);
            $authorId = $request->getAttribute('user_id');

            $required = ['title', 'content'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode(['success' => false, 'error' => "Field '$field' is required"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            $post = new Post($this->db);
            $postData = [
                'title' => htmlspecialchars($data['title']),
                'slug' => $post->generateSlug($data['title']),
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 200) . '...',
                'category' => htmlspecialchars($data['category'] ?? 'General'),
                'tags' => json_encode($data['tags'] ?? []),
                'author_id' => $authorId,
                'status' => $data['status'] ?? 'published',
                'meta_title' => htmlspecialchars($data['meta_title'] ?? $data['title']),
                'meta_description' => htmlspecialchars($data['meta_description'] ?? '')
            ];

            $postId = $post->create($postData);

            if ($postId) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Post created successfully',
                    'data' => ['post_id' => $postId]
                ]));
                return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to create post']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to create post']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Helper methods
    private function getFileType($mimeType)
    {
        $typeMapping = [
            'application/pdf' => 'pdf',
            'video/mp4' => 'video',
            'audio/mpeg' => 'audio',
            'audio/wav' => 'audio',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document'
        ];

        return $typeMapping[$mimeType] ?? 'document';
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}