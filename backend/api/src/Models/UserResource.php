<?php
namespace App\Models;

class UserResource extends BaseModel
{
    protected $table = 'user_resources';

    public function assignResource($data)
    {
        $sql = "INSERT INTO user_resources (user_id, resource_id, category, assigned_by_admin_id) 
                VALUES (:user_id, :resource_id, :category, :assigned_by_admin_id)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function getUserResources($userId, $category = null)
    {
        $sql = "SELECT ur.*, r.resource_name, r.description, r.resource_type, r.file_size, 
                r.duration, r.google_drive_url, r.thumbnail_url, r.google_drive_id
                FROM user_resources ur 
                JOIN resources r ON ur.resource_id = r.id 
                WHERE ur.user_id = :user_id AND r.is_active = 1";

        $params = ['user_id' => $userId];

        if ($category) {
            $sql .= " AND ur.category = :category";
            $params['category'] = $category;
        }

        $sql .= " ORDER BY ur.assigned_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateProgress($userResourceId, $data)
    {
        $fields = [];
        $params = ['id' => $userResourceId];

        $allowedFields = ['completion_percentage', 'time_spent_minutes'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        $fields[] = "last_accessed_at = NOW()";

        if (empty($fields)) return false;

        $sql = "UPDATE user_resources SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function markCategoryComplete($userId, $category, $adminId)
    {
        $sql = "UPDATE user_resources 
                SET is_completed = 1, completed_by_admin_id = :admin_id, completed_at = NOW() 
                WHERE user_id = :user_id AND category = :category";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'category' => $category,
            'admin_id' => $adminId
        ]);
    }

    public function getProgressSummary($userId)
    {
        $sql = "SELECT 
                category,
                COUNT(*) as total_resources,
                COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_resources,
                ROUND(AVG(completion_percentage), 2) as avg_completion,
                SUM(time_spent_minutes) as total_time_spent
                FROM user_resources 
                WHERE user_id = :user_id 
                GROUP BY category";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getUserProgress($userId)
    {
        $sql = "SELECT ur.*, r.resource_name, r.resource_type, u_assigned.name as assigned_by_name,
                u_completed.name as completed_by_name
                FROM user_resources ur
                JOIN resources r ON ur.resource_id = r.id
                JOIN users u_assigned ON ur.assigned_by_admin_id = u_assigned.id
                LEFT JOIN users u_completed ON ur.completed_by_admin_id = u_completed.id
                WHERE ur.user_id = :user_id
                ORDER BY ur.category, ur.assigned_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}