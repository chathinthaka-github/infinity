<?php
namespace App\Models;

class ScoreSheet extends BaseModel
{
    protected $table = 'score_sheets';

    public function create($data)
    {
        $sql = "INSERT INTO score_sheets (student_name, exam_type, overall_score, listening_score, 
                reading_score, speaking_score, writing_score, image_url, exam_date, location, is_featured) 
                VALUES (:student_name, :exam_type, :overall_score, :listening_score, 
                :reading_score, :speaking_score, :writing_score, :image_url, :exam_date, :location, :is_featured)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function getScores($featuredOnly = false, $examType = null)
    {
        $sql = "SELECT * FROM score_sheets WHERE 1=1";
        $params = [];

        if ($featuredOnly) {
            $sql .= " AND is_featured = 1";
        }

        if ($examType) {
            $sql .= " AND exam_type = :exam_type";
            $params['exam_type'] = $examType;
        }

        $sql .= " ORDER BY is_featured DESC, exam_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}