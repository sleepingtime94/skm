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
      $("#employee-list").append(`
                <div class="col-md-4">
                    <div class="card h-100" data-pid="${item.employee_id}">
                        <img src="/assets/img/EMPLOYEE.jpg" class="card-img-top" alt="Thumbnail">
                        <div class="card-body">
                            <h5 class="card-title">${item.employee_name}</h5>
                            <div class="card-text text-secondary small">${item.employee_about}</div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-success" data-rate="5"><i class="bi bi-hand-thumbs-up-fill me-2"></i>Bagus</button>
                            <button class="btn btn-outline-primary my-2" data-rate="3"><i class="bi bi-hand-thumbs-up me-2"></i>Lumayan</button>
                            <button class="btn btn-outline-danger" data-rate="1"><i class="bi bi-hand-thumbs-down me-2"></i>Buruk</button>
                        </div>
                    </div>
                </div>
                `);
    });

    $("button").click(function () {
      const pid = $(this).parent().parent().attr("data-pid");
      const rate = $(this).attr("data-rate");

      Swal.fire({
        title: "Beri Nilai",
        text: "Anda yakin untuk memberikan penilaian ini?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, berikan rating!",
        cancelButtonText: "Tidak, batalkan!",
      }).then((result) => {
        if (result.isConfirmed) {
          $.post("/rating", { pid: pid, rate: rate }, function (data) {
            if (data == "success") {
              Swal.fire({
                title: "Rating berhasil!",
                text: "Terima kasih atas rating yang Anda berikan.",
                icon: "success",
              });
            } else {
              Swal.fire({
                title: "Rating gagal!",
                text: "Rating tidak dapat disimpan.",
                icon: "error",
              });
            }
          });
        }
      });
    });
  });
});
