@extends('app')

@section('container')
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier">
                                </i>
                                <input placeholder="Search users" type="text" value=""/>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            <select class="select select-sm w-28">
                                <option value="1">
                                    Active
                                </option>
                                <option value="2">
                                    Disabled
                                </option>
                                <option value="2">
                                    Pending
                                </option>
                            </select>
                            <select class="select select-sm w-28">
                                <option value="1">
                                    Latest
                                </option>
                                <option value="2">
                                    Older
                                </option>
                                <option value="3">
                                    Oldest
                                </option>
                            </select>
                            <button class="btn btn-sm btn-outline btn-primary">
                                <i class="ki-filled ki-setting-4">
                                </i>
                                Filters
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-page-size="20">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border data-table-orders" data-datatable-table="true">
                                <thead>
                                <tr>
                                    <th class="w-[60px] text-center">
                                        <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"/>
                                    </th>
                                    <th class="min-w-[300px]">
                                       <span class="sort asc">
                                        <span class="sort-label font-normal text-gray-700">
                                       customer
                                        </span>
                                        <span class="sort-icon">
                                        </span>
                                       </span>
                                    </th>


                                    <th class="min-w-[180px]">
                                       <span class="sort">
                                        <span class="sort-label font-normal text-gray-700">
                                         Status
                                        </span>
                                        <span class="sort-icon">
                                        </span>
                                       </span>
                                    </th>


                                    <th class="w-[60px]">
                                    </th>
                                </tr>
                                </thead>

                                <tbody>
                                {{--                                 <tr>--}}
                                {{--                                     <td class="text-center">--}}
                                {{--                                         <input class="checkbox checkbox-sm" data-datatable-row-check="true" type="checkbox" value="1"/>--}}
                                {{--                                     </td>--}}
                                {{--                                     <td>--}}
                                {{--                                         <div class="flex items-center gap-2.5">--}}
                                {{--                                             <img alt="" class="rounded-full size-9 shrink-0" src="assets/media/avatars/300-1.png"/>--}}
                                {{--                                             <div class="flex flex-col">--}}
                                {{--                                                 <a class="text-sm font-medium text-gray-900 hover:text-primary-active mb-px" href="#">--}}
                                {{--                                                     Esther Howard--}}
                                {{--                                                 </a>--}}
                                {{--                                                 <a class="text-2sm text-gray-700 font-normal hover:text-primary-active" href="#">--}}
                                {{--                                                     esther.howard@gmail.com--}}
                                {{--                                                 </a>--}}
                                {{--                                             </div>--}}
                                {{--                                         </div>--}}
                                {{--                                     </td>--}}
                                {{--                                     <td class="text-gray-800 font-normal">--}}
                                {{--                                         Editor--}}
                                {{--                                     </td>--}}
                                {{--                                     <td>--}}
                                {{--               <span class="badge badge-danger badge-outline rounded-[30px]">--}}
                                {{--                <span class="size-1.5 rounded-full bg-danger me-1.5">--}}
                                {{--                </span>--}}
                                {{--                On Leave--}}
                                {{--               </span>--}}
                                {{--                                     </td>--}}
                                {{--                                     <td>--}}
                                {{--                                         <div class="flex items-center text-gray-800 font-normal gap-1.5">--}}
                                {{--                                             <img alt="" class="rounded-full size-4 shrink-0" src="assets/media/flags/malaysia.svg"/>--}}
                                {{--                                             Malaysia--}}
                                {{--                                         </div>--}}
                                {{--                                     </td>--}}
                                {{--                                     <td class="text-gray-800 font-normal">--}}
                                {{--                                         Week ago--}}
                                {{--                                     </td>--}}
                                {{--                                     <td class="text-center">--}}
                                {{--                                         <div class="menu flex-inline" data-menu="true">--}}
                                {{--                                             <div class="menu-item" data-menu-item-offset="0, 10px" data-menu-item-placement="bottom-end" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:click">--}}
                                {{--                                                 <button class="menu-toggle btn btn-sm btn-icon btn-light btn-clear">--}}
                                {{--                                                     <i class="ki-filled ki-dots-vertical">--}}
                                {{--                                                     </i>--}}
                                {{--                                                 </button>--}}
                                {{--                                                 <div class="menu-dropdown menu-default w-full max-w-[175px]" data-menu-dismiss="true">--}}
                                {{--                                                     <div class="menu-item">--}}
                                {{--                                                         <a class="menu-link" href="#">--}}
                                {{--                    <span class="menu-icon">--}}
                                {{--                     <i class="ki-filled ki-search-list">--}}
                                {{--                     </i>--}}
                                {{--                    </span>--}}
                                {{--                                                             <span class="menu-title">--}}
                                {{--                     View--}}
                                {{--                    </span>--}}
                                {{--                                                         </a>--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-item">--}}
                                {{--                                                         <a class="menu-link" href="#">--}}
                                {{--                    <span class="menu-icon">--}}
                                {{--                     <i class="ki-filled ki-file-up">--}}
                                {{--                     </i>--}}
                                {{--                    </span>--}}
                                {{--                                                             <span class="menu-title">--}}
                                {{--                     Export--}}
                                {{--                    </span>--}}
                                {{--                                                         </a>--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-separator">--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-item">--}}
                                {{--                                                         <a class="menu-link" href="#">--}}
                                {{--                    <span class="menu-icon">--}}
                                {{--                     <i class="ki-filled ki-pencil">--}}
                                {{--                     </i>--}}
                                {{--                    </span>--}}
                                {{--                                                             <span class="menu-title">--}}
                                {{--                     Edit--}}
                                {{--                    </span>--}}
                                {{--                                                         </a>--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-item">--}}
                                {{--                                                         <a class="menu-link" href="#">--}}
                                {{--                    <span class="menu-icon">--}}
                                {{--                     <i class="ki-filled ki-copy">--}}
                                {{--                     </i>--}}
                                {{--                    </span>--}}
                                {{--                                                             <span class="menu-title">--}}
                                {{--                     Make a copy--}}
                                {{--                    </span>--}}
                                {{--                                                         </a>--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-separator">--}}
                                {{--                                                     </div>--}}
                                {{--                                                     <div class="menu-item">--}}
                                {{--                                                         <a class="menu-link" href="#">--}}
                                {{--                    <span class="menu-icon">--}}
                                {{--                     <i class="ki-filled ki-trash">--}}
                                {{--                     </i>--}}
                                {{--                    </span>--}}
                                {{--                                                             <span class="menu-title">--}}
                                {{--                     Remove--}}
                                {{--                    </span>--}}
                                {{--                                                         </a>--}}
                                {{--                                                     </div>--}}
                                {{--                                                 </div>--}}
                                {{--                                             </div>--}}
                                {{--                                         </div>--}}
                                {{--                                     </td>--}}
                                {{--                                 </tr>--}}

                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                            <div class="flex items-center gap-2 order-2 md:order-1">
                                Show
                                <select class="select select-sm w-16" data-datatable-size="true" name="perpage">
                                </select>
                                per page
                            </div>
                            <div class="flex items-center gap-4 order-1 md:order-2">
            <span data-datatable-info="true">
            </span>
                                <div class="pagination" data-datatable-pagination="true">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- ملفك -->

    <script src="assets/js/orders/index.js">
    </script>
@endsection
