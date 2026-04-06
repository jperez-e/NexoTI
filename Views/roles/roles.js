// Este archivo JavaScript define la lógica de interfaz del módulo roles.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
// Este código se ejecuta cuando el DOM ha sido completamente cargado y listo para ser manipulado.

document.addEventListener('DOMContentLoaded', function () {
    window.NexoCatalogo.crearPaginaCatalogo({
        controlador: 'rol',
        formId: 'rol-form',
        listId: 'roles-list',
        hiddenId: 'rol-id',
        campos: [
            { id: 'nombre', key: 'nombre' },
        ],
        renderTituloItem: function (fila) {
            return fila.nombre;
        },
        mensajes: {
            formTitleNuevo: 'Nuevo rol',
            formTitleEditar: 'Editar rol',
            submitGuardar: 'Guardar rol',
            submitActualizar: 'Actualizar rol',
            listaVacia: 'No hay roles registrados.',
            confirmarEliminar: function (fila) {
                return 'Se eliminará el rol ' + fila.nombre + '. ¿Deseas continuar?';
            },
            errorEliminar: 'No se pudo eliminar el rol.',
            toastCreadoTitulo: 'Rol registrado',
            toastActualizadoTitulo: 'Rol actualizado',
            errorOperacion: 'No se pudo completar la operación.',
        },
    });
});
