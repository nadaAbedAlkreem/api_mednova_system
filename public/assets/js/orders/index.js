
$(document).ready(function() {

$('.data-table-orders').DataTable({
     processing: true,
    serverSide: true,
    ordering: false,
    searching: false,
     info: false,
     lengthChange: false,
     paging: false,

     ajax: {
         url: "admin/home",
         data: function (d) {

        }
    },
    columns: [
        {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
        {data: 'customer', name: 'customer'},
        {data: 'status', name: 'status'},
        {data: 'action', name: 'action', searchable: false},
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
 });
