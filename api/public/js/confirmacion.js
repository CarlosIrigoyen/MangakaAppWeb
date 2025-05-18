let formularioAConfirmar = null;

function mostrarModalConfirmacion(mensaje, form) {
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    document.getElementById('mensajeConfirmacion').innerText = mensaje;
    formularioAConfirmar = form;
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const btnConfirmar = document.getElementById('btnConfirmarAccion');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            if (formularioAConfirmar) {
                formularioAConfirmar.submit();
            }
        });
    }
});
