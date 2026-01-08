<?php

namespace App\Repositories\Eloquent;

use App\Models\Program;
use App\Repositories\IProgramRepositories;


class ProgramRepository  extends BaseRepository implements IProgramRepositories
{
    public function __construct()
    {
        $this->model = new Program();
    }
    public function baseQuery()
    {
        return Program::query()
            ->public()
            ->with(['creator'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->withCount('enrollments');
    }

    public function findWithDetails(int $id)
    {
        return $this->baseQuery()
            ->with(['videos'])
            ->find($id);
    }

    public function paginateWithDetails(int $limit = 5)
    {
        return $this->baseQuery()
            ->orderBy('id', 'DESC')
            ->paginate($limit);
    }

}
