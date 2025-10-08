
$(document).ready(function() {
/////////////   datatable

    $('.data-table-job').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        info: false,
        lengthChange: false,
        paging: false,

         ajax: {
             url: "admin/job",
             data: function (d) {

            }
        },
        columns: [
            {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            {data: 'customer', name: 'customer'},
            {data: 'order', name: 'order'},
            {data: 'response', name: 'response'},
        ],

        order: [[2, 'asc']], // ترتيب حسب الاسم
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        responsive: true,
        dom: 'lfrtip',
         "drawCallback": function(settings) {
             KTMenu.createInstances();
             initMenus();

         }

 });

 function initMenus() {
     // كل عنصر يحتوي على data-menu
     $('[data-menu="true"]').each(function() {
         var menu = $(this);
         if (!menu.data('menu-initialized')) {
             // استدعاء الكلاس JS الخاص بالقائمة
             // إذا تستخدم Metronic 8:
             new KTMenu(menu[0]); // تهيئة القائمة
             menu.data('menu-initialized', true);
         }
     });
 }


 initMenus();
    /////////////   datatable

});



///////////////    add notification


$('#kt_modal_add_notification_form').on('submit', function(e) {
    e.preventDefault();
    // Validate form before submit
                let formData = new FormData($("#kt_modal_add_notification_form")[0]);
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                });
                $.ajax({
                    type: "POST",
                    url: "admin/notification/add",
                    data: formData,
                    contentType: false, // determint type object
                    processData: false, // processing on response
                    success: function (response) {
                        $(".data-table-notification").DataTable().ajax.reload();
                            // Show popup confirmation
                            Swal.fire({
                                text: "تم اضافة البيانات بنجاح ",
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            }).then(function (result) {
                                if (result.isConfirmed) {
                                     $('#kt_modal_add_notification').modal('hide');
                                     $('#kt_modal_add_notification_form')[0].reset();

                                 }
                            });


                    },

                    error: function (response) {
                        Swal.fire({
                            text: response.responseJSON.data.error,
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: 'ok !',
                            customClass: {
                                confirmButton: "btn btn-primary",
                            },
                        });
                    },
                });


});


///////////////   add notification

















