CREATE DATABASE IF NOT EXISTS nexoti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexoti;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    foto VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
);

CREATE TABLE prioridades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE,
    nivel TINYINT NOT NULL UNIQUE
);

CREATE TABLE estados_ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT NOT NULL,
    usuario_id INT NOT NULL,
    tecnico_id INT NULL,
    categoria_id INT NOT NULL,
    prioridad_id INT NOT NULL,
    estado_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    CONSTRAINT fk_tickets_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_tickets_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id),
    CONSTRAINT fk_tickets_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_tickets_prioridad FOREIGN KEY (prioridad_id) REFERENCES prioridades(id),
    CONSTRAINT fk_tickets_estado FOREIGN KEY (estado_id) REFERENCES estados_ticket(id)
);

CREATE TABLE comentarios_ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_com_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_com_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE ticket_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    comentario_id INT NULL,
    usuario_id INT NULL,
    archivo VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_adj_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_adj_comentario FOREIGN KEY (comentario_id) REFERENCES comentarios_ticket(id) ON DELETE SET NULL,
    CONSTRAINT fk_adj_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE notificaciones (
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

INSERT INTO roles(nombre) VALUES ("Admin"), ("Tecnico"), ("Usuario");
INSERT INTO prioridades(nombre, nivel) VALUES ("Baja",1), ("Media",2), ("Alta",3), ("Critica",4);
INSERT INTO estados_ticket(nombre) VALUES ("Abierto"), ("En Proceso"), ("Resuelto"), ("Cerrado");
INSERT INTO categorias(nombre, descripcion) VALUES
("Hardware", "Equipos, perifericos y piezas"),
("Software", "Aplicaciones y sistemas"),
("Redes", "Conectividad e internet"),
("Accesos", "Usuarios y permisos");

INSERT INTO usuarios(nombre, email, clave_hash, rol_id)
VALUES ("Usuario Demo", "demo@nexoti.local", "$2y$10$7fG9N3SxIP7JY7PEoY2wWu8vNL8m4gyaRQdNki4mJTLmnY2m5SIuW", 3);
