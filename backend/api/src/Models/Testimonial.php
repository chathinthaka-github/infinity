<?php
namespace App\Models;

class Testimonial extends BaseModel
{
    protected $table = 'testimonials';

    public function create($data)
    {
        $sql = "INSERT INTO testimonials (student_name, student_photo_url, score_before, score_after, 
                score_breakdown, testimonial_text, video_url, location, exam_date, is_featured, status) 
                VALUES (:student_name, :student_photo_url, :score_before, :score_after, 
                :score_breakdown, :testimonial_text, :video_url, :location, :exam_date, :is_featured, :status)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['student_name', 'student_photo_url', 'score_before', 'score_after',
            'score_breakdown', 'testimonial_text', 'video_url', 'location',
            'exam_date', 'is_featured', 'status'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE testimonials SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getApproved($featuredOnly = false)
    {
        $sql = "SELECT * FROM testimonials WHERE status = 'approved'";

        if ($featuredOnly) {
            $sql .= " AND is_featured = 1";
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}