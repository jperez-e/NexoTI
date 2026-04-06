// Este archivo JavaScript define la lógica de interfaz del módulo prioridades.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
// Este código se ejecuta cuando el DOM ha sido completamente cargado y listo para ser manipulado.

document.addEventListener('DOMContentLoaded', function () {
    window.NexoCatalogo.crearPaginaCatalogo({
        controlador: 'prioridad',
        formId: 'prioridad-form',
        listId: 'prioridades-list',
        hiddenId: 'prioridad-id',
        campos: [
            { id: 'nombre', key: 'nombre' },
            { id: 'nivel', key: 'nivel' },
        ],
        renderTituloItem: function (fila) {
            return fila.nombre + ' (Nivel ' + fila.nivel + ')';
        },
        mensajes: {
            formTitleNuevo: 'Nueva prioridad',
            formTitleEditar: 'Editar prioridad',
            submitGuardar: 'Guardar prioridad',
            submitActualizar: 'Actualizar prioridad',
            listaVacia: 'No hay prioridades registradas.',
            confirmarEliminar: function (fila) {
                return 'Se eliminará la prioridad ' + fila.nombre + '. ¿Deseas continuar?';
            },
            errorEliminar: 'No se pudo eliminar la prioridad.',
            toastCreadoTitulo: 'Prioridad registrada',
            toastActualizadoTitulo: 'Prioridad actualizada',
            errorOperacion: 'No se pudo completar la operación.',
        },
    });
});
