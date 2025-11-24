"use strict";
var KTAccountSettingsDeactivateAccount = (function () {
    var t, n, e;
    return {
        init: function () {
            (t = document.querySelector("#kt_account_deactivate_form")) &&
                ((e = document.querySelector(
                    "#kt_account_deactivate_account_submit"
                )),
                (n = FormValidation.formValidation(t, {
                    fields: {
                        deactivate: {
                            validators: {
                                notEmpty: {
                                    message:
                                        "Veuillez cocher la case pour désactiver votre compte",
                                },
                            },
                        },
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        submitButton: new FormValidation.plugins.SubmitButton(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: ".fv-row",
                            eleInvalidClass: "",
                            eleValidClass: "",
                        }),
                    },
                })),
                e.addEventListener("click", function (t) {
                    t.preventDefault(),
                        n.validate().then(function (t) {
                            "Valid" == t
                                ? swal
                                      .fire({
                                          text: "Êtes-vous sûr de vouloir désactiver votre compte?",
                                          icon: "warning",
                                          buttonsStyling: !1,
                                          showDenyButton: !0,
                                          confirmButtonText: "Oui",
                                          denyButtonText: "Non",
                                          customClass: {
                                              confirmButton:
                                                  "btn btn-light-primary",
                                              denyButton: "btn btn-danger",
                                          },
                                      })
                                      .then((t) => {
                                          t.isConfirmed
                                              ? Swal.fire({
                                                    text: "Cette action a ete supendu pour le moment",
                                                    icon: "info",
                                                    confirmButtonText: "Ok",
                                                    buttonsStyling: !1,
                                                    customClass: {
                                                        confirmButton:
                                                            "btn btn-light-primary",
                                                    },
                                                })
                                              : t.isDenied &&
                                                Swal.fire({
                                                    text: "Cette action a ete supendu pour le moment",
                                                    icon: "info",
                                                    confirmButtonText: "Ok",
                                                    buttonsStyling: !1,
                                                    customClass: {
                                                        confirmButton:
                                                            "btn btn-light-primary",
                                                    },
                                                });
                                      })
                                : swal.fire({
                                      text: "Désolé, il semble que des erreurs aient été détectées, veuillez réessayer.",
                                      icon: "error",
                                      buttonsStyling: !1,
                                      confirmButtonText: "D'accord!",
                                      customClass: {
                                          confirmButton:
                                              "btn btn-light-primary",
                                      },
                                  });
                        });
                }));
        },
    };
})();
KTUtil.onDOMContentLoaded(function () {
    KTAccountSettingsDeactivateAccount.init();
});
