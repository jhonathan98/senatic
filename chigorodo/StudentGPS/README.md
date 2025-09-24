# StudentGPS - Sistema de Gestión y Seguimiento Estudiantil 🎓📍

Sistema integral desarrollado para el monitoreo y seguimiento de estudiantes en instituciones educativas, implementado específicamente para Chigorodó, Antioquia, Colombia. Combina control de asistencia tradicional con tecnología GPS moderna para ofrecer una solución completa de gestión estudiantil.

## 🚀 Características Principales

### 👥 Sistema de Roles
- **Administrador**: Control total del sistema, registro de usuarios, estadísticas generales
- **Profesor**: Gestión de asistencia, visualización de estudiantes asignados
- **Acudiente**: Seguimiento de sus hijos, visualización de ubicación y asistencia

### 📱 Funcionalidades

#### Gestión de Usuarios
- ✅ Sistema de autenticación seguro con tokens CSRF
- ✅ Registro de estudiantes, profesores y acudientes
- ✅ Roles y permisos diferenciados
- ✅ Generación automática de contraseñas temporales

#### Control de Asistencia
- ✅ Registro diario de asistencia por profesor
- ✅ Estados: Asistió, No asistió, Tarde, Excusado
- ✅ Búsqueda de asistencia por fechas
- ✅ Estadísticas por grado y general

#### Monitoreo GPS
- ✅ Ubicación en tiempo real de estudiantes
- ✅ Integración con Google Maps
- ✅ API para recepción de coordenadas
- ✅ Historial de ubicaciones
- ✅ Simulador para pruebas

#### Panel de Control
- ✅ Dashboard personalizado por rol
- ✅ Estadísticas en tiempo real
- ✅ Actividad reciente del sistema
- ✅ Acciones rápidas contextuales

## 🛠️ Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, mysqli

### Paso a Paso

1. **Clonar o descargar el proyecto**
   ```bash
   git clone [repositorio] studentgps
   cd studentgps
   ```

2. **Configurar la base de datos**
   ```bash
   # Importar el esquema de base de datos
   mysql -u root -p < sql_setup.sql
   ```

3. **Configurar conexión**
   Editar `config.php` con los datos de tu base de datos:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'studentgps');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   ```

4. **Configurar permisos**
   ```bash
   chmod 755 -R /ruta/al/proyecto
   chmod 777 includes/logs (si planeas usar logs)
   ```

5. **Configurar Google Maps** (Opcional)
   Obtener API Key de Google Maps y reemplazar en `map.php`:
   ```javascript
   src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&callback=initMap"
   ```

## 👤 Usuarios de Prueba

El sistema incluye usuarios predeterminados para pruebas:

| Usuario | Contraseña | Rol | Descripción |
|---------|------------|-----|-------------|
| `admin` | `password` | Administrador | Acceso completo al sistema |
| `juanperez` | `password` | Acudiente | Padre con estudiante registrado |
| `profesor1` | `password` | Profesor | Profesor con estudiantes asignados |

## 📋 Estructura del Proyecto

```
StudentGPS/
├── index.php              # Página de login
├── dashboard.php           # Panel principal
├── config.php             # Configuración de BD y funciones base
├── logout.php             # Cerrar sesión
├── sql_setup.sql          # Esquema de base de datos
├── simulator.html         # Simulador de ubicaciones GPS
│
├── includes/
│   └── functions.php      # Funciones de utilidad
│
├── api/
│   └── location.php       # API para recibir ubicaciones
│
├── Módulos principales:
├── register_student.php   # Registro de estudiantes
├── register_teacher.php   # Registro de profesores
├── attendance_register.php # Registro de asistencia
├── search_student.php     # Búsqueda de estudiantes
├── map.php               # Mapa de ubicaciones
│
└── Paneles por rol:
    ├── panel_admin.php    # Panel del administrador
    ├── panel_teacher.php  # Panel del profesor
    └── panel_parent.php   # Panel del acudiente
```

## 🔧 Configuración Avanzada

### Base de Datos
El sistema crea automáticamente las siguientes tablas:
- `users` - Usuarios del sistema
- `students` - Información de estudiantes
- `teacher_students` - Relación profesor-estudiante
- `attendance` - Registro de asistencia
- `locations` - Ubicaciones GPS
- `activity_log` - Log de actividades
- `notifications` - Sistema de notificaciones

### API de Ubicaciones
Para enviar ubicaciones desde dispositivos móviles:

```bash
POST /api/location.php
Content-Type: application/json

{
    "student_id": 1,
    "latitude": 7.6656,
    "longitude": -76.6281,
    "api_key": "StudentGPS2024"
}
```

### Simulador GPS
Acceder a `simulator.html` para:
- Enviar ubicaciones manuales
- Simular movimiento automático
- Probar la API de ubicaciones
- Ver respuestas del servidor en tiempo real

## 🔐 Seguridad

### Implementaciones de Seguridad
- ✅ Protección CSRF en formularios
- ✅ Sanitización de datos de entrada
- ✅ Consultas preparadas (PDO)
- ✅ Validación de roles y permisos
- ✅ Hash seguro de contraseñas
- ✅ Regeneración de ID de sesión
- ✅ Validación de tipos de datos
- ✅ Log de actividades

### Recomendaciones
- Cambiar las contraseñas por defecto
- Configurar HTTPS en producción
- Actualizar la API key de Google Maps
- Revisar logs de actividad regularmente
- Implementar backups automáticos

## 📱 Integración Móvil

### Envío de Ubicaciones
Para integrar con aplicaciones móviles Android/iOS:

1. **Obtener permisos de ubicación**
2. **Configurar GPS de alta precisión**
3. **Enviar coordenadas cada 30-60 segundos**
4. **Manejar errores de conectividad**

### Ejemplo JavaScript (Web)
```javascript
navigator.geolocation.getCurrentPosition(function(position) {
    fetch('/api/location.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            student_id: STUDENT_ID,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            api_key: 'StudentGPS2024'
        })
    });
});
```

## 🚀 Próximas Características

- [ ] Notificaciones push
- [ ] Reportes en PDF
- [ ] Geofencing (zonas seguras)
- [ ] App móvil nativa
- [ ] Chat entre usuarios
- [ ] Sistema de calificaciones
- [ ] Backup automático
- [ ] Multi-idioma

## 🐛 Solución de Problemas

### Problemas Comunes

**Error de conexión a BD:**
- Verificar credenciales en `config.php`
- Confirmar que MySQL esté ejecutándose
- Verificar permisos del usuario de BD

**Mapa no carga:**
- Verificar API Key de Google Maps
- Confirmar que el dominio esté autorizado
- Revisar consola del navegador

**Ubicaciones no se guardan:**
- Verificar API key en las peticiones
- Confirmar que el estudiante existe
- Revisar logs del servidor

## 📞 Soporte

Para reportar problemas o solicitar nuevas características:
- Crear un issue en el repositorio
- Enviar email a soporte@studentgps.edu.co
- Consultar la documentación técnica

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

**Desarrollado con ❤️ para instituciones educativas colombianas**

*Versión 1.0.0 - Diciembre 2024*
