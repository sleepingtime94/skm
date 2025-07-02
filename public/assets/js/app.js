$(document).ready(function () {
  $("#quest").on("submit", function (event) {
    event.preventDefault();

    let formDataArray = $(this).serializeArray();
    let params = {};

    formDataArray.forEach((item) => {
      if (item.name === "product[]") {
        if (!params["product"]) {
          params["product"] = [];
        }
        params["product"].push(item.value);
      } else {
        params[item.name] = item.value;
      }
    });

    if (params["product"]) {
      params["product"] = params["product"].join("|");
    }

    let dataToSend = {
      type: "create",
      params: params,
    };

    $.ajax({
      url: "/survey/submit",
      type: "POST",
      data: JSON.stringify(dataToSend),
      contentType: "application/json",
      success: function (res) {
        res = JSON.parse(res);
        Swal.fire({
          icon: res.status,
          text: res.message,
          timer: 3000,
          allowOutsideClick: false,
        })
          .then(() => {
            location.reload();
          })
          .catch((error) => console.error(error));
      },
      error: function (err) {
        console.error("AJAX Error:", err);
      },
    });
  });
});
