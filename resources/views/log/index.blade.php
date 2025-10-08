@extends('app')

@section('container')
    <style>
        .modal.show {
            display: block !important;
            opacity: 1 !important;
            z-index: 1055 !important; /* لازم يكون أعلى من backdrop */
        }

        .modal.show .modal-dialog {
            transform: none !important;
            opacity: 1 !important;
        }

    </style>
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
                         <button type="button" class="btn btn-sm btn-outline btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_notification">
                             <i class="bi bi-plus-circle me-2"></i> إضافة أشعار
                         </button>
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
                             <table class="table table-auto table-border data-table-log" data-datatable-table="true">
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
                                         order
                                        </span>
                                        <span class="sort-icon">
                                        </span>
                                       </span>
                                     </th>
                                     <th class="min-w-[180px]">
                                       <span class="sort">
                                        <span class="sort-label font-normal text-gray-700">
                                         response
                                        </span>
                                        <span class="sort-icon">
                                        </span>
                                       </span>
                                     </th>
                                 </tr>
                                 </thead>

                                 <tbody>


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


     <!-- model  -->

    <!--begin::Modal - Add task-->

    <!-- Add Notification Modal -->
    <!-- Add Notification Modal -->
    <div class="modal fade" id="kt_modal_add_notification" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content border-0 shadow-sm">

                <!-- Header -->
                <div class="modal-header py-3 px-4 border-0">
                    <h3 class="fw-bold text-gray-800 mb-0">Create Notification</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg fs-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body py-4 px-4">
                    <form id="kt_modal_add_notification_form" class="form">
                       @csrf
                        <!-- Channel -->
                        <div class="fv-row mb-6">
                            <label class="form-label required fw-semibold">Channel</label>
                            <select name="channel" class="form-select form-select-solid" data-control="select2" data-placeholder="Select channel">
                                <option></option>
                                <option value="sms">SMS</option>
                                <option value="email">Email</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="push">Push Notification</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div class="fv-row mb-6">
                            <label class="form-label required fw-semibold">Message</label>
                            <textarea name="message" class="form-control form-control-solid" rows="1" placeholder="Enter the notification message"></textarea>
                        </div>

                        <!-- Send Type -->
                        <div class="fv-row mb-6">
                            <label class="form-label required fw-semibold">Send Type</label>
                            <select id="send_type" name="send_type" class="form-select form-select-solid" data-control="select2" data-placeholder="Select type">
                                <option></option>
                                <option value="relative">Relative (Send a message linked to a specific event after a specific time)</option>
                                <option value="absolute">Absolute (Send a message only after a certain date)</option>
                            </select>
                        </div>

                        <!-- Trigger Event -->
                        <div class="fv-row mb-6">
                            <label class="form-label required fw-semibold ">Trigger Event (just Relative )</label>
                            <select name="trigger_event" class="form-select form-select-solid "  data-control="select2" data-placeholder="Select trigger">
                                <option></option>
                                <option value="register_created">User Registered</option>
                                <option value="order_created">Order Created</option>
                                <option value="order_pending">Order Pending</option>
                                <option value="order_accepted">Order Accepted</option>
                                <option value="order_rejected">Order Rejected</option>
                                <option value="manual">Manual Trigger</option>
                            </select>
                        </div>


                        <!-- Relative: Send After Minutes -->
                        <div id="relative_fields" class="fv-row mb-6">
                            <label class="form-label fw-semibold">Send After (Minutes just relative)</label>
                            <input type="number" name="send_after_minutes" class="form-control form-control-solid" placeholder="Enter minutes" />
                        </div>

                        <!-- Absolute: Send At -->
                        <div id="absolute_fields" class="fv-row mb-6">
                            <label class="form-label fw-semibold">Send At (absolute) </label>
                            <input type="datetime-local" name="send_at" class="form-control form-control-solid" />
                        </div>


                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer py-3 px-4 border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" data-kt-notification-modal-action="submit" form="kt_modal_add_notification_form" class="btn btn-primary">
                        <span class="indicator-label">Save</span>
                        <span class="indicator-progress" style="display:none;">
                            Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                          </span>
                    </button>
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

     <script src="assets/js/log/index.js">
     </script>
@endsection
