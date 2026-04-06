// Este archivo JavaScript define la lógica de interfaz del módulo categorias.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
document.addEventListener('DOMContentLoaded', function () {
    window.NexoCatalogo.crearPaginaCatalogo({
        controlador: 'categoria',
        formId: 'categoria-form',
        listId: 'categorias-list',
        hiddenId: 'categoria-id',
        campos: [
            { id: 'nombre', key: 'nombre' },
            { id: 'descripcion', key: 'descripcion' },
        ],
        renderTituloItem: function (fila) {
            return fila.nombre;
        },
        renderDescripcionItem: function (fila) {
            return fila.descripcion ? fila.descripcion : 'Sin descripcion';
        },
        mensajes: {
            formTitleNuevo: 'Nueva categoría',
            formTitleEditar: 'Editar categoría',
            submitGuardar: 'Guardar categoría',
            submitActualizar: 'Actualizar categoría',
            listaVacia: 'No hay categorías registradas.',
            confirmarEliminar: function (fila) {
                return 'Se eliminará la categoría ' + fila.nombre + '. ¿Deseas continuar?';
            },
            errorEliminar: 'No se pudo eliminar la categoría.',
            toastCreadoTitulo: 'Categoría registrada',
            toastActualizadoTitulo: 'Categoría actualizada',
            errorOperacion: 'No se pudo completar la operación.',
        },
    });
});
