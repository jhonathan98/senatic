# Periódico Digital - Colegio Gonzalo Mejía

Sistema de periódico digital desarrollado para el Colegio Gonzalo Mejía utilizando PHP, MySQL y Bootstrap.

## Características

- **Gestión de Noticias**: Crear, editar y gestionar noticias con categorías
- **Sistema de Usuarios**: Diferentes tipos de usuarios (admin, redactor, miembro, invitado)
- **Comentarios**: Sistema de comentarios en las noticias
- **Panel de Administración**: Dashboard completo para administradores y redactores
- **Calendario de Eventos**: Vista de calendario para eventos escolares
- **Responsive Design**: Interfaz adaptable usando Bootstrap 5

## Tipos de Usuario

1. **Invitado**: Solo puede leer noticias
2. **Miembro**: Puede comentar en las noticias
3. **Redactor**: Puede crear y gestionar sus propias noticias
4. **Admin**: Acceso completo al sistema

## Instalación

### Requisitos
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 o superior
- MySQL 5.7 o superior

### Pasos de Instalación

1. **Configurar XAMPP**
   - Instalar XAMPP
   - Iniciar Apache y MySQL

2. **Base de Datos**
   - Abrir phpMyAdmin (http://localhost/phpmyadmin)
   - Importar el archivo `periodico.sql`
   - Se creará la base de datos `periodico_digital_db`

3. **Configuración**
   - Verificar la configuración en `includes/conexion.php`
   - Ajustar credenciales de base de datos si es necesario

4. **Acceso**
   - Abrir el navegador e ir a: `http://localhost/ruta-del-proyecto/index.php`

### Usuario Administrador por Defecto
- **Correo**: admin@colegio.edu
- **Contraseña**: admin123

## Estructura del Proyecto

```
PeriodicoDigital/
├── index.php                 # Página principal
├── periodico.sql            # Script de base de datos
├── includes/               # Archivos comunes
│   ├── conexion.php        # Conexión a la base de datos
│   ├── header.php          # Header HTML
│   ├── footer.php          # Footer HTML
│   ├── navbar.php          # Navegación
│   └── logout.php          # Cerrar sesión
├── vistas/                 # Vistas principales
│   ├── login.php           # Iniciar sesión
│   ├── registro.php        # Registro de usuarios
│   ├── noticias_detalle.php # Detalle de noticia
│   ├── noticias_todas.php  # Lista de todas las noticias
│   ├── noticias_categoria.php # Noticias por categoría
│   ├── calendario.php      # Calendario de eventos
│   ├── perfil.php          # Perfil de usuario
│   └── admin/              # Panel de administración
│       ├── dashboard.php   # Dashboard principal
│       ├── crear_noticia.php # Crear nueva noticia
│       ├── gestionar_noticias.php # Gestionar noticias
│       └── gestionar_usuarios.php # Gestionar usuarios
└── assets/                 # Recursos
    └── images/
        └── noticias/       # Imágenes de las noticias
```

## Funcionalidades Principales

### Para Usuarios Generales
- Ver noticias por categorías
- Leer noticias completas con comentarios
- Calendario de eventos
- Registro e inicio de sesión
- Perfil de usuario

### Para Redactores
- Crear nuevas noticias
- Gestionar sus propias noticias
- Subir imágenes para las noticias
- Marcar noticias como destacadas

### Para Administradores
- Gestión completa de noticias
- Gestión de usuarios
- Gestión de categorías
- Dashboard con estadísticas
- Control de estado de noticias

## Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Bootstrap 5
- **Iconos**: Bootstrap Icons

## Categorías Incluidas

1. **Cultura**: Eventos, arte, literatura
2. **Deportes**: Actividades y competencias deportivas
3. **Opinión**: Artículos y puntos de vista
4. **Eventos**: Fechas importantes y actos cívicos
5. **Académico**: Logros y proyectos escolares

## Seguridad

- Contraseñas hasheadas con `password_hash()`
- Validación de entrada de datos
- Protección contra SQL injection usando prepared statements
- Control de acceso por tipo de usuario
- Validación de tipos de archivo para imágenes

## Desarrollo

El proyecto está diseñado para ser fácil de mantener y extender:

- Código modular y reutilizable
- Separación clara entre lógica y presentación
- Comentarios en el código
- Estructura de archivos organizada

## Soporte

Para soporte técnico o mejoras, contactar al equipo de desarrollo del colegio.

---

**Desarrollado para el Colegio Gonzalo Mejía**
