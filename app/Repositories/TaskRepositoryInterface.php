<?php
namespace App\Repositories;

interface TaskRepositoryInterface {
    public function all(?string $status = null, int $page = 1, int $perPage = 12);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function reorder(array $orderedIds);
}
