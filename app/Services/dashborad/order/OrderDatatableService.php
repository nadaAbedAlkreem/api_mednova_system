<?php

namespace App\Services\dashborad\order;

use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;


class OrderDatatableService extends Controller
{
    public function handle( $request,$data )
    {
          return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($data) {
                  return '
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input select-row" type="checkbox" name="ids[]" value="' . $data->id . '" id="checkbox_' . $data->id . '" />
                        <label for="checkbox_' . $data->id . '"></label>
                    </div>';
              })
              ->addColumn('status', function ($data) {
                  $statuses = [
                      'pending' => 'badge-warning',
                      'accepted' => 'badge-success',
                      'cancelled' => 'badge-danger',
                  ];

                  $currentStatus = $data->status ?? 'pending';
                  $badgeClass = $statuses[$currentStatus];

                  return '
                            <div class="relative inline-block text-left">
                                <button type="button"
                                    class="badge '.$badgeClass.' badge-outline rounded-[30px] px-3 py-1 dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="size-1.5 rounded-full me-1.5"></span>
                                    '.ucfirst($currentStatus).'
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item change-status" data-id="'.$data->id.'" data-status="pending" href="#">Pending</a></li>
                                    <li><a class="dropdown-item change-status" data-id="'.$data->id.'" data-status="accepted" href="#">Accepted</a></li>
                                    <li><a class="dropdown-item change-status" data-id="'.$data->id.'" data-status="cancelled" href="#">Not Accepted</a></li>
                                </ul>
                            </div>
                        ';
              })

            ->addColumn('action', function ($data)
            {
                 return '
                                          <div class="menu flex-inline" data-menu="true">
                                             <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">
                                                 <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">
                                                     <i class="ki-filled ki-dots-vertical">
                                                     </i>
                                                 </button>
                                                 <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">
                                                     <div class="menu-item">
                                                         <a class="menu-link" href="#">
                    <span class="menu-icon">
                     <i class="ki-filled ki-search-list">
                     </i>
                    </span>
                                                             <span class="menu-title">
                     View
                    </span>
                                                         </a>
                                                     </div>
                                                     <div class="menu-item">
                                                         <a class="menu-link" href="#">
                    <span class="menu-icon">
                     <i class="ki-filled ki-file-up">
                     </i>
                    </span>
                                                             <span class="menu-title">
                     Export
                    </span>
                                                         </a>
                                                     </div>
                                                     <div class="menu-separator">
                                                     </div>
                                                     <div class="menu-item">
                                                         <a class="menu-link" href="#">
                    <span class="menu-icon">
                     <i class="ki-filled ki-pencil">
                     </i>
                    </span>
                                                             <span class="menu-title">
                     Edit
                    </span>
                                                         </a>
                                                     </div>
                                                     <div class="menu-item">
                                                         <a class="menu-link" href="#">
                    <span class="menu-icon">
                     <i class="ki-filled ki-copy">
                     </i>
                    </span>
                                                             <span class="menu-title">
                     Make a copy
                    </span>
                                                         </a>
                                                     </div>
                                                     <div class="menu-separator">
                                                     </div>
                                                     <div class="menu-item">
                                                         <a class="menu-link" href="#">
                    <span class="menu-icon">
                     <i class="ki-filled ki-trash">
                     </i>
                    </span>
                                                             <span class="menu-title">
                     Remove
                    </span>
                                                         </a>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>';
            })

            ->addColumn('customer', function ($data)
              {
               return '
                                         <div class="flex items-center gap-2.5">
                                             <img alt="" class="rounded-full size-9 shrink-0" src="assets/media/avatars/300-1.png"/>
                                             <div class="flex flex-col">
                                                 <a class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px">
                                                   ' . $data->customer->name . '
                                                 </a>
                                                 <a class="text-2sm text-gray-700 font-normal hover:text-primary-active" >
                                                 ' . $data->customer->email . '

                                                  </a>
                                             </div>
                                         </div>
                              '
              ;


              })



           ->rawColumns(['customer', 'action','status' ,'checkbox' ])
           ->make(true);

    }




}
