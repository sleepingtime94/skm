$(".card-text button").click(function () {
  const pid = $(this).closest("[data-pid]").attr("data-pid");
  const rate = $(this).attr("data-rate");

  Swal.fire({
    title: "Beri Penilaian",
    html: `
      <p class="small text-muted mb-3" style="font-size: 0.82rem; line-height: 1.4;">Silakan lengkapi data diri Anda sebelum memberikan penilaian. Data Anda hanya digunakan untuk verifikasi validitas rating.</p>
      <div class="text-start mb-3">
        <label class="form-label small fw-bold text-dark mb-1">Nama Lengkap</label>
        <input type="text" id="swal-rate-name" class="form-control form-control-sm" placeholder="Nama Anda (Opsional)">
      </div>
      <div class="text-start mb-2">
        <label class="form-label small fw-bold text-dark mb-1">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
        <input type="tel" id="swal-rate-phone" class="form-control form-control-sm" placeholder="Contoh: 08123456789">
        <div class="form-text text-muted" style="font-size: 0.72rem; margin-top: 4px;">Gunakan format nomor HP Indonesia aktif (diawali 08 atau +62, 10-14 digit).</div>
      </div>
    `,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Kirim Penilaian",
    cancelButtonText: "Batal",
    focusConfirm: false,
    preConfirm: () => {
      const name = document.getElementById('swal-rate-name').value.trim();
      const phone = document.getElementById('swal-rate-phone').value.trim();
      
      if (!phone) {
        Swal.showValidationMessage("Nomor WhatsApp/HP wajib diisi.");
        return false;
      }
      
      // Validasi format nomor HP Indonesia (minimal 10 digit, diawali 08 atau +62)
      const phoneRegex = /^(08|\+62)[0-9]{8,12}$/;
      if (!phoneRegex.test(phone)) {
        Swal.showValidationMessage("Format nomor HP tidak valid (diawali 08/+62, 10-14 digit).");
        return false;
      }
      
      return { name: name, phone: phone };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const { name, phone } = result.value;
      $.post("/rating", { pid: pid, rate: rate, name: name, phone: phone }, function (data) {
        let res;
        try {
          res = JSON.parse(data);
        } catch (e) {
          res = { status: "error", message: "Gagal memproses respon server." };
        }
        
        if (res.status === "success") {
          Swal.fire({
            title: "Terima Kasih!",
            text: res.message,
            icon: "success",
          }).then(() => {
            location.replace("/");
          });
        } else {
          Swal.fire({
            title: "Penilaian Gagal!",
            text: res.message || "Terjadi kesalahan saat menyimpan penilaian.",
            icon: "error",
          });
        }
      });
    }
  });
});
