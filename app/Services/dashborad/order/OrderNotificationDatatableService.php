<?php

namespace App\Services\dashborad\order;

use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;


class OrderNotificationDatatableService extends Controller
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
            ->addColumn('message', function ($data) {
                return  '<span class="badge badge-sm">
                 ' . $data->message . '
                </span>' ;
            })

            ->addColumn('send_type', function ($data) {
                  return '<td class="text-gray-800 font-medium">
                  ' . $data->send_type . '
                </td>' ;

              })



           ->rawColumns([ 'send_type' ,'message' , 'action','checkbox' ])
           ->make(true);

    }




}
