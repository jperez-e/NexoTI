CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ticket_id INT NULL,
    actor_id INT NULL,
    tipo VARCHAR(30) NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leida_en DATETIME NULL,
    CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_notificaciones_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_notificaciones_actor FOREIGN KEY (actor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

ALTER TABLE ticket_adjuntos
    ADD COLUMN comentario_id INT NULL AFTER ticket_id,
    ADD COLUMN usuario_id INT NULL AFTER comentario_id;

ALTER TABLE ticket_adjuntos
    ADD CONSTRAINT fk_adj_comentario FOREIGN KEY (comentario_id) REFERENCES comentarios_ticket(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_adj_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;
