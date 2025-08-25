<?php
namespace App\Models;

class Resource extends BaseModel
{
    protected $table = 'resources';

    public function create($data)
    {
        $sql = "INSERT INTO resources (resource_name, description, resource_type, file_size, duration, 
                google_drive_id, google_drive_url, thumbnail_url, created_by) 
                VALUES (:resource_name, :description, :resource_type, :file_size, :duration, 
                :google_drive_id, :google_drive_url, :thumbnail_url, :created_by)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['resource_name', 'description', 'is_active'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE resources SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getActiveResources()
    {
        $stmt = $this->db->prepare("SELECT r.*, u.name as created_by_name FROM resources r 
                                   LEFT JOIN users u ON r.created_by = u.id 
                                   WHERE r.is_active = 1 
                                   ORDER BY r.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function incrementDownloadCount($id)
    {
        $stmt = $this->db->prepare("UPDATE resources SET download_count = download_count + 1 WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}