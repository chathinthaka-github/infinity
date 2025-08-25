<?php
namespace App\Models;

class Review extends BaseModel
{
    protected $table = 'reviews';

    public function create($data)
    {
        $sql = "INSERT INTO reviews (reviewer_name, reviewer_photo_url, review_text, rating, 
                google_review_url, review_date, is_featured) 
                VALUES (:reviewer_name, :reviewer_photo_url, :review_text, :rating, 
                :google_review_url, :review_date, :is_featured)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function getReviews($minRating = null, $limit = null)
    {
        $sql = "SELECT * FROM reviews WHERE 1=1";
        $params = [];

        if ($minRating) {
            $sql .= " AND rating >= :min_rating";
            $params['min_rating'] = $minRating;
        }

        $sql .= " ORDER BY is_featured DESC, review_date DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}