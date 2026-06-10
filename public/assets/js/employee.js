$(document).ready(function () {
  $("#employee-list").html(`
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Memuat data...</span>
            </div>
            <div class="mt-3 text-muted">Silahkan tunggu beberapa saat...</div>
        </div>
        `);

  $.get("/pegawai", function (data) {
    const pegawai = JSON.parse(data);

    $("#employee-list").empty();

    pegawai.forEach(function (item) {
      if (item.employee_image != null) {
        var image = `/storage/uploads/${item.employee_image}`;
      } else {
        var image = "/assets/img/EMPLOYEE.jpg";
      }

      $("#employee-list").append(`
                <div class="col-md-4">
                    <div class="card h-100" data-pid="${item.employee_id}">
                        <img src="${image}" class="card-img-top" alt="Thumbnail">
                        <div class="card-body">
                            <a class="card-title text-decoration-none fw-bold" href="/penilaian-pegawai/${item.employee_id}">${item.employee_name}</a>
                            <div class="card-text text-secondary small">${item.employee_about}</div>
                        </div>
                    </div>
                </div>
                `);
    });
  });
});
