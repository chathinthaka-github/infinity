<?php
namespace App\Models;

class Post extends BaseModel
{
    protected $table = 'posts';

    public function create($data)
    {
        $sql = "INSERT INTO posts (title, slug, content, excerpt, category, tags, author_id, status, meta_title, meta_description) 
                VALUES (:title, :slug, :content, :excerpt, :category, :tags, :author_id, :status, :meta_title, :meta_description)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'slug', 'content', 'excerpt', 'category', 'tags', 'status', 'meta_title', 'meta_description'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE posts SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getBySlug($slug)
    {
        $stmt = $this->db->prepare("SELECT p.*, u.name as author_name FROM posts p 
                                   LEFT JOIN users u ON p.author_id = u.id 
                                   WHERE p.slug = :slug AND p.status = 'published'");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getPublishedPosts($limit = null, $offset = null)
    {
        $sql = "SELECT p.*, u.name as author_name FROM posts p 
                LEFT JOIN users u ON p.author_id = u.id 
                WHERE p.status = 'published' 
                ORDER BY p.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->db->prepare($sql);

        if ($limit) {
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            if ($offset) {
                $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function generateSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists($slug)
    {
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE slug = :slug");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }
}