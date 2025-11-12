<?php

namespace App\Repositories;

use App\Models\GloveError;

interface IGloveErrorRepositories
{

    public function storeGloveError(string $errorMessage, ?int $gloveId = null ,  ?int $commandId = null  ,?string $errorType = null ): void;


}
