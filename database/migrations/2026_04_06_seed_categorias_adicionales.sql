-- Inserta categorias adicionales del catalogo de tickets.
-- Es idempotente: si una categoria ya existe (UNIQUE por nombre),
-- solo actualiza su descripcion.

INSERT INTO categorias (nombre, descripcion)
VALUES
    ('Correo', 'Cuentas, envio y recepcion'),
    ('Impresion', 'Impresoras y escaneres'),
    ('Telefonia', 'Extensiones y VoIP'),
    ('Seguridad', 'Antivirus y alertas'),
    ('Academico', 'Plataformas academicas'),
    ('Administrativo', 'ERP y gestion interna'),
    ('Web institucional', 'Portal y contenido web'),
    ('Laboratorio', 'Soporte en laboratorios'),
    ('Videoconferencia', 'Zoom, Meet y Teams'),
    ('Base de datos', 'Consultas, accesos y respaldo'),
    ('Servidores', 'Servicios e infraestructura'),
    ('Backup', 'Copias y restauraciones'),
    ('Licencias', 'Activacion y renovacion'),
    ('Soporte general', 'Asistencia a usuarios'),
    ('Mantenimiento', 'Revision preventiva'),
    ('Equipos nuevos', 'Solicitud y reemplazo')
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion);
