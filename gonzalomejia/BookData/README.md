# BookData - Sistema de Gestión de Biblioteca

BookData es un sistema web completo para la gestión de una biblioteca digital, desarrollado en PHP con MySQL y Bootstrap.

## Características Principales

### Para Estudiantes
- ✅ Registro e inicio de sesión seguro
- ✅ Navegación por catálogo de libros
- ✅ Búsqueda y filtrado por categorías
- ✅ Préstamo de libros con fechas personalizables
- ✅ Visualización de libros prestados
- ✅ Historial de préstamos

### Para Administradores
- ✅ Panel de administración completo
- ✅ Gestión de usuarios
- ✅ Gestión de libros y categorías
- ✅ Control de préstamos y devoluciones
- ✅ Estadísticas y reportes
- ✅ Gestión de libros vencidos

## Requisitos del Sistema

- **Servidor Web**: Apache/Nginx
- **PHP**: Versión 7.4 o superior
- **MySQL**: Versión 5.7 o superior
- **Extensiones PHP**: PDO, PDO_MySQL

## Instalación

### 1. Clonar o descargar el proyecto
```bash
git clone [URL del repositorio]
```

### 2. Configurar la base de datos
1. Crear una base de datos MySQL llamada `bookdata`
2. Ejecutar el script `script.sql` para crear las tablas y datos de prueba
3. Verificar que la configuración en `includes/config.php` sea correcta:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bookdata');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Configurar el servidor web
- Asegurarse de que el proyecto esté accesible desde el navegador
- Verificar que la URL en `includes/config.php` sea correcta
- Dar permisos de escritura a las carpetas necesarias

## Usuarios de Prueba

Después de ejecutar el script SQL, tendrás los siguientes usuarios disponibles:

### Administradores
- **Usuario**: `admin1` | **Email**: `admin1@bookdata.com` | **Contraseña**: `password`
- **Usuario**: `admin2` | **Email**: `admin2@bookdata.com` | **Contraseña**: `password`

### Estudiantes
- **Usuario**: `estudiante1` | **Email**: `estudiante1@bookdata.com` | **Contraseña**: `password`
- **Usuario**: `estudiante2` | **Email**: `estudiante2@bookdata.com` | **Contraseña**: `password`
- **Usuario**: `estudiante3` | **Email**: `estudiante3@bookdata.com` | **Contraseña**: `password`
- **Usuario**: `estudiante4` | **Email**: `estudiante4@bookdata.com` | **Contraseña**: `password`

## Estructura del Proyecto

```
bookdata/
├── assets/
│   ├── css/
│   │   └── style.css          # Estilos principales
│   ├── images/                # Imágenes y recursos
│   └── js/
│       └── app.js            # JavaScript principal
├── includes/
│   ├── config.php            # Configuración general
│   ├── db_connection.php     # Conexión a base de datos
│   ├── functions.php         # Funciones principales
│   └── header.php           # Header común
├── index.php                # Página de inicio/login
├── login.php                # Procesamiento de login
├── register.php             # Registro de usuarios
├── dashboard.php            # Dashboard principal
├── book_detail.php          # Detalle de libro
├── admin_dashboard.php      # Panel de administración
├── logout.php              # Cerrar sesión
└── script.sql              # Script de base de datos
```

## Funcionalidades Técnicas

### Seguridad
- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Validación y sanitización de datos de entrada
- ✅ Protección contra inyección SQL con PDO
- ✅ Sesiones seguras
- ✅ Verificación de permisos por rol

### Base de Datos
- ✅ Diseño normalizado
- ✅ Índices optimizados para rendimiento
- ✅ Restricciones de integridad referencial
- ✅ Campos de auditoría (created_at, updated_at)

### Interfaz de Usuario
- ✅ Diseño responsivo con Bootstrap 5
- ✅ Interfaz moderna y intuitiva
- ✅ Animaciones CSS/JS
- ✅ Filtros y búsqueda en tiempo real
- ✅ Experiencia de usuario optimizada

## Configuración Avanzada

### Personalizar la aplicación
1. Modificar `includes/config.php` para cambiar configuraciones
2. Actualizar estilos en `assets/css/style.css`
3. Personalizar funcionalidades en `assets/js/app.js`

### Agregar nuevas funcionalidades
1. Crear nuevos archivos PHP en la raíz
2. Utilizar las funciones existentes en `includes/functions.php`
3. Seguir la estructura establecida

## Solución de Problemas

### Error de conexión a base de datos
- Verificar credenciales en `config.php`
- Asegurar que MySQL esté ejecutándose
- Comprobar que la base de datos existe

### Problemas de permisos
- Verificar permisos de archivos y carpetas
- Asegurar que PHP tenga acceso de escritura donde sea necesario

### Errores de JavaScript/CSS
- Verificar que las rutas a los archivos sean correctas
- Comprobar la consola del navegador para errores

## Contribución

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear una rama para la funcionalidad
3. Realizar commits descriptivos
4. Enviar pull request

## Licencia

[Especificar licencia aquí]

## Contacto

[Información de contacto del desarrollador]

---

**Nota**: Este sistema está diseñado para fines educativos y de demostración. Para uso en producción, considerar implementar medidas de seguridad adicionales y optimizaciones de rendimiento.
