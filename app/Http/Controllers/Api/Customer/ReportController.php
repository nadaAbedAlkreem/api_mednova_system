<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreReportRequest;
use App\Models\Report;
use App\Repositories\IReportRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ResponseTrait;
    protected IReportRepositories $reportRepository;
    public function __construct(IReportRepositories $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Display a listing of the resource.
     */


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
            $report = $this->reportRepository->create($request->getData());
            return $this->successResponse(__('messages.CREATE_SUCCESS'), [] , 202);
        }catch (\Exception $exception)
        {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function reportEnums(): \Illuminate\Http\JsonResponse
    {
        try {
            $categories = $this->getEnumValues('reports', 'category');
            $subcategories = $this->getEnumValues('reports', 'subcategory');
            $severity = $this->getEnumValues('reports', 'severity');
            $status = $this->getEnumValues('reports', 'status');
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ['categories' => $categories, 'subcategories' => $subcategories, 'severity' => $severity, 'status' => $status], 202);
        }catch (\Exception $exception)
        {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }

    }
    private function getEnumValues(string $table, string $column): array
    {
        $row = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);
        if (empty($row)) {
            return [];
        }
        $type = $row[0]->Type; // هذا يجب أن يكون string
        // استخراج القيم من النوع enum
        preg_match("/^enum\('(.*)'\)$/", $type, $matches);

        return $matches ? explode("','", $matches[1]) : [];
    }


}
