// Este código se ejecuta cuando el DOM ha sido completamente cargado y listo para ser manipulado.

document.addEventListener('DOMContentLoaded', function () {
    window.NexoCatalogo.crearPaginaCatalogo({
        controlador: 'estado',
        formId: 'estado-form',
        listId: 'estados-list',
        hiddenId: 'estado-id',
        campos: [
            { id: 'nombre', key: 'nombre' },
        ],
        renderTituloItem: function (fila) {
            return fila.nombre;
        },
        mensajes: {
            formTitleNuevo: 'Nuevo estado',
            formTitleEditar: 'Editar estado',
            submitGuardar: 'Guardar estado',
            submitActualizar: 'Actualizar estado',
            listaVacia: 'No hay estados registrados.',
            confirmarEliminar: function (fila) {
                return 'Se eliminará el estado ' + fila.nombre + '. ¿Deseas continuar?';
            },
            errorEliminar: 'No se pudo eliminar el estado.',
            toastCreadoTitulo: 'Estado registrado',
            toastActualizadoTitulo: 'Estado actualizado',
            errorOperacion: 'No se pudo completar la operación.',
        },
    });
});
