<?php

namespace App\Repositories;

interface IProgramRepositories
{
    public function baseQuery();
    public function findWithDetails(int $id);
    public function paginateWithDetails(int $limit);



}
