<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\UserResource;
use App\Models\Resource;

class StudentController
{
    private $db;
    private $googleDrive;

    public function __construct($container)
    {
        $this->db = $container->get('db');
        $this->googleDrive = $container->get('googleDrive');
    }

    public function getMyResources(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $userResource = new UserResource($this->db);

            $resources = $userResource->getUserResources($userId);

            // Categorize resources
            $categorized = [
                'reading' => [],
                'listening' => [],
                'writing' => [],
                'speaking' => [],
                'extra_resources' => []
            ];

            foreach ($resources as $resource) {
                $categorized[$resource['category']][] = $resource;
            }

            // Get progress summary
            $progressSummary = $userResource->getProgressSummary($userId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'resources_by_category' => $categorized,
                    'progress_summary' => $progressSummary
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch resources']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getResourcesByCategory(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $category = $args['category'];

            $validCategories = ['reading', 'listening', 'writing', 'speaking', 'extra_resources'];
            if (!in_array($category, $validCategories)) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid category']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $userResource = new UserResource($this->db);
            $resources = $userResource->getUserResources($userId, $category);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $resources
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch category resources']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function viewResource(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $resourceId = (int)$args['id'];

            $userResource = new UserResource($this->db);
            $resources = $userResource->getUserResources($userId);

            // Check if user has access to this resource
            $hasAccess = false;
            $resourceData = null;

            foreach ($resources as $resource) {
                if ($resource['resource_id'] == $resourceId) {
                    $hasAccess = true;
                    $resourceData = $resource;
                    break;
                }
            }

            if (!$hasAccess) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Access denied']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Get Google Drive view URL
            $viewUrl = $this->googleDrive->getViewUrl($resourceData['google_drive_id']);

            // Update download count
            $resource = new Resource($this->db);
            $resource->incrementDownloadCount($resourceId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'resource' => $resourceData,
                    'view_url' => $viewUrl,
                    'download_url' => $resourceData['google_drive_url']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to access resource']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateProgress(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $data = json_decode($request->getBody(), true);

            if (empty($data['resource_id'])) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Resource ID required']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $userResource = new UserResource($this->db);

            // Find the user_resource record
            $userResources = $userResource->getUserResources($userId);
            $userResourceId = null;

            foreach ($userResources as $ur) {
                if ($ur['resource_id'] == $data['resource_id']) {
                    $userResourceId = $ur['id'];
                    break;
                }
            }

            if (!$userResourceId) {
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Resource not assigned to user']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $progressData = [];
            if (isset($data['completion_percentage'])) {
                $progressData['completion_percentage'] = min(100, max(0, (float)$data['completion_percentage']));
            }
            if (isset($data['time_spent'])) {
                $progressData['time_spent_minutes'] = max(0, (int)$data['time_spent']);
            }

            $result = $userResource->updateProgress($userResourceId, $progressData);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Progress updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update progress']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update progress']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getProgressSummary(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $userResource = new UserResource($this->db);

            $summary = $userResource->getProgressSummary($userId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $summary
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch progress summary']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'whatsapp_number' => $user['whatsapp_number'],
                    'current_location' => $user['current_location'],
                    'target_score' => $user['target_score'],
                    'reason_for_pte' => $user['reason_for_pte'],
                    'heard_about_us' => $user['heard_about_us'],
                    'created_at' => $user['created_at']
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to fetch profile']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $data = json_decode($request->getBody(), true);

            $user = new User($this->db);
            $result = $user->updateProfile($userId, $data);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update profile']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Failed to update profile']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}