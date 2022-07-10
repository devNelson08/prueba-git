$(document).ready(function () {
    $('#example').DataTable({
        ajax: 'ajax/data.txt',
        columns: [
            { data: 'name' },
            { data: 'position' },
            { data: 'office' },
            { data: 'extn' },
            { data: 'start_date' },
            { data: 'salary' },
        ],
    });
});