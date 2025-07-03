$(".card-text button").click(function () {
  const pid = $(this).closest("[data-pid]").attr("data-pid");
  const rate = $(this).attr("data-rate");

  console.log(pid, rate);

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
        const result = JSON.parse(data);
        if (result.status == "success") {
          Swal.fire({
            title: "Rating berhasil!",
            text: "Terima kasih atas rating yang Anda berikan.",
            icon: "success",
          }).then(() => {
            location.replace("/");
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
