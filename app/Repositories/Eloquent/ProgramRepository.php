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
            ->withCount([
                'ratings as ratings_4_to_5' => function ($query) {
                    $query->whereBetween('rating', [4, 5]);
                },
                'ratings as ratings_3_to_4' => function ($query) {
                    $query->whereBetween('rating', [3, 4]);
                },
                'ratings as ratings_2_to_3' => function ($query) {
                    $query->whereBetween('rating', [2, 3]);
                },
                'ratings as ratings_0_to_1' => function ($query) {
                    $query->whereBetween('rating', [0, 1]);
                },
            ])
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
