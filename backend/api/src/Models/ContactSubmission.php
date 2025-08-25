<?php
namespace App\Models;

class ContactSubmission extends BaseModel
{
    protected $table = 'contact_submissions';

    public function create($data)
    {
        $sql = "INSERT INTO contact_submissions (name, email, phone, subject, message, source_page) 
                VALUES (:name, :email, :phone, :subject, :message, :source_page)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($data);

        return $result ? $this->db->lastInsertId() : false;
    }
}