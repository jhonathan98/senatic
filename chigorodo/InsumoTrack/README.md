# InsumoTrack - Sistema de Gestión de Préstamos

## Descripción
InsumoTrack es una aplicación web para gestionar préstamos de insumos (equipos, herramientas, libros, etc.) en instituciones educativas. El sistema permite a los usuarios solicitar préstamos y a los administradores gestionar el inventario y aprobar solicitudes.

## Características

### Para Usuarios:
- ✅ Registro y autenticación de usuarios
- ✅ Solicitud de préstamos de insumos
- ✅ Visualización del inventario disponible
- ✅ Seguimiento del estado de sus préstamos
- ✅ Gestión de perfil personal

### Para Administradores:
- ✅ Dashboard administrativo con estadísticas
- ✅ Gestión completa del inventario
- ✅ Aprobación/rechazo de solicitudes
- ✅ Control de entregas y devoluciones
- ✅ Reportes y seguimiento de préstamos

## Requisitos del Sistema

- **Servidor Web**: Apache (XAMPP, WAMP, LAMP)
- **PHP**: Versión 7.4 o superior
- **Base de Datos**: MySQL 5.7 o superior
- **Navegador**: Moderno con soporte para JavaScript ES6+

## Instalación

### 1. Preparar el Entorno
```bash
# Si usas XAMPP, asegúrate de que Apache y MySQL estén ejecutándose
```

### 2. Clonar/Descargar el Proyecto
```bash
# Coloca los archivos en tu directorio web (ej: htdocs para XAMPP)
# La estructura debe quedar así:
# htdocs/senatic/senatic/chigorodo/InsumoTrack/
```

### 3. Configurar la Base de Datos

6. Ejecutar el script SQL para crear la base de datos:
   ```sql
   mysql -u root -p < insumoTrack.sql
   ```
   O importar el archivo `insumoTrack.sql` desde phpMyAdmin.

## 🛠️ Scripts de Utilidades

El sistema incluye scripts adicionales para gestión de datos:

### Generar Datos de Prueba Adicionales
```
http://localhost/InsumoTrack/generar_datos_prueba.php
```
- Crea usuarios, insumos y préstamos adicionales
- Útil para pruebas extensas del sistema
- Genera hashes seguros de contraseñas automáticamente

### Reset Completo de Base de Datos
```
http://localhost/InsumoTrack/reset_database.php
```
- ⚠️ **PELIGROSO**: Elimina todos los datos
- Restablece el sistema a estado inicial
- Mantiene solo administrador por defecto y datos básicos
- Requiere confirmación doble por seguridad

## 📊 Datos de Prueba

2. **Configurar Conexión**:
   - Edita `config/database.php` si es necesario:
   ```php
   $host = 'localhost';
   $dbname = 'insumo_track_db';
   $username = 'root';  // Tu usuario MySQL
   $password = '';      // Tu contraseña MySQL
   ```

### 4. Datos Iniciales

El script SQL incluye:
- **Roles**: `admin` y `user`
- **Usuario Administrador**:
  - Email: `admin@institucion.edu.co`
  - Contraseña: `admin123`
- **Insumos de Ejemplo**: Microscopio y Multímetro

## Uso del Sistema

### Acceso Inicial
1. Navega a: `http://localhost/senatic/senatic/chigorodo/InsumoTrack/`
2. **Para Administradores**:
   - Email: `admin@institucion.edu.co`
   - Contraseña: `admin123`
3. **Para Usuarios**: Crear cuenta nueva desde el formulario de registro

### Flujo de Trabajo

#### Como Usuario:
1. **Registro**: Completar formulario con datos institucionales
2. **Login**: Acceder con email y contraseña
3. **Explorar**: Ver inventario disponible en el dashboard
4. **Solicitar**: Seleccionar insumo y fecha de devolución
5. **Seguimiento**: Monitorear estado de solicitudes

#### Como Administrador:
1. **Dashboard**: Ver estadísticas y solicitudes pendientes
2. **Gestión**:
   - Aprobar/rechazar solicitudes
   - Marcar como entregado
   - Registrar devoluciones
3. **Inventario**: Agregar, editar y gestionar insumos
4. **Control**: Monitorear préstamos activos y atrasados

## Estructura del Proyecto

```
InsumoTrack/
├── config/
│   └── database.php          # Configuración de BD
├── functions/
│   ├── auth.php             # Autenticación
│   ├── user_functions.php   # Funciones de usuario
│   └── admin_functions.php  # Funciones de admin
├── includes/
│   ├── header.php          # Header común
│   └── footer.php          # Footer común
├── views/
│   ├── login.php           # Login/Registro
│   ├── admin/              # Vistas de administrador
│   │   ├── dashboard.php
│   │   ├── inventory.php
│   │   ├── loans.php
│   │   └── reports.php
│   └── user/               # Vistas de usuario
│       ├── dashboard.php
│       └── profile.php
├── index.php               # Página principal
└── insumoTrack.sql        # Script de BD
```

## Estados de Préstamos

- **Pendiente**: Solicitud recién creada
- **Aprobado**: Solicitud aprobada por admin
- **Entregado**: Insumo entregado al usuario
- **Devuelto**: Insumo devuelto exitosamente
- **Atrasado**: Fecha de devolución vencida
- **Rechazado**: Solicitud rechazada

## Estados de Insumos

- **Disponible**: Listo para préstamo
- **Prestado**: Actualmente en préstamo
- **No disponible**: Temporalmente no disponible
- **Mantenimiento**: En reparación o mantenimiento

## Tecnologías Utilizadas

- **Backend**: PHP 7.4+, MySQL
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **Framework CSS**: Bootstrap 5.3
- **Iconos**: Bootstrap Icons
- **Base de Datos**: MySQL con PDO

## Personalización

### Cambiar Colores/Tema
Edita los archivos CSS en `includes/header.php` para personalizar:
- Colores principales
- Logos
- Tipografías

### Agregar Campos
1. Modifica las tablas en `insumoTrack.sql`
2. Actualiza las funciones en `functions/`
3. Modifica los formularios en `views/`

### Configurar Email (Opcional)
Para notificaciones por email, configura SMTP en `functions/` y agrega biblioteca como PHPMailer.

## Troubleshooting

### Problemas Comunes

1. **Error de Conexión a BD**:
   - Verificar credenciales en `config/database.php`
   - Asegurar que MySQL esté ejecutándose

2. **Páginas en Blanco**:
   - Activar `display_errors` en PHP
   - Revisar logs de error de Apache

3. **Problemas de Sesión**:
   - Verificar que `session_start()` funcione
   - Comprobar permisos de directorio temporal

4. **Estilos No Cargan**:
   - Verificar conexión a internet (Bootstrap CDN)
   - Comprobar rutas de archivos

### Logs de Error
```bash
# En XAMPP, revisar:
# xampp/apache/logs/error.log
# xampp/mysql/data/mysql_error.log
```

## 🧪 Testing del Sistema

Utiliza las siguientes URL para probar el sistema:

- **Sistema principal**: `http://localhost/InsumoTrack/`
- **Login directo**: `http://localhost/InsumoTrack/views/login.php`
- **Test completo**: `http://localhost/InsumoTrack/test_functions.php`
- **Test dropdown**: `http://localhost/InsumoTrack/test_dropdown.php`
- **Prueba de auth.php**: `http://localhost/InsumoTrack/test_auth.php`
- **Dashboard Admin**: `http://localhost/InsumoTrack/views/admin/dashboard.php`
- **Dashboard Usuario**: `http://localhost/InsumoTrack/views/user/dashboard.php`

### 🔧 Solución de Problemas

Si el sistema no funciona correctamente:

1. **Verificar todas las funciones**: Visita `test_functions.php`
2. **Probar dropdown del usuario**: Visita `test_dropdown.php`
3. **Verificar solo autenticación**: Visita `test_auth.php`
4. **Revisar rutas**: Asegurar que XAMPP esté ejecutándose
5. **Verificar datos**: Usar `generar_datos_prueba.php` si no hay datos
6. **Reset completo**: Usar `reset_database.php` para empezar de nuevo

#### 🔧 Problemas Específicos del Dropdown:

- **Dropdown no abre**: Verificar que Bootstrap JS esté cargado
- **Botones no responden**: Comprobar consola del navegador para errores JavaScript
- **Estilos incorrectos**: Asegurar que Bootstrap CSS esté cargado correctamente

## Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Consultas preparadas (prevención SQL Injection)
- ✅ Validación de entrada
- ✅ Control de acceso por roles
- ✅ Sanitización de salida con `htmlspecialchars()`

## Próximas Mejoras

- [ ] Sistema de notificaciones por email
- [ ] Reportes en PDF
- [ ] API REST para aplicaciones móviles
- [ ] Sistema de códigos QR para insumos
- [ ] Historial detallado de préstamos
- [ ] Integración con LDAP/Active Directory

## Soporte

Para reportar problemas o solicitar mejoras:
1. Documenta el error con detalles
2. Incluye navegador y versión de PHP
3. Proporciona pasos para reproducir el problema

## Licencia

Este proyecto es de uso educativo y puede ser modificado según las necesidades de cada institución.

---

**InsumoTrack** - Control preciso, insumo al día 📋✅
