<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use PDO;

/**
 * ResourceService
 * Light service layer around the Resource model.
 */
class ResourceService
{
    private Resource $resourceModel;

    public function __construct(PDO $db)
    {
        $this->resourceModel = new Resource($db);
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->resourceModel->getActiveResources($limit, $offset);
    }

    public function getById(int $id)
    {
        return $this->resourceModel->getById($id);
    }

    public function create(array $data): int
    {
        return $this->resourceModel->create($data);
    }
}
