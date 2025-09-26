# SysPlanner - Sistema de Gestión de Recursos y Reservas

## 📋 Descripción

SysPlanner es un sistema completo de gestión de recursos y reservas desarrollado en PHP con arquitectura MVC. Permite a las organizaciones administrar eficientemente sus recursos (aulas, laboratorios, equipos, salas de reuniones, etc.) y gestionar las reservas de usuarios.

## ✨ Características Principales

### 🔐 Sistema de Autenticación
- Registro e inicio de sesión seguro
- Roles de usuario (Admin, Usuario)
- Gestión de sesiones con seguridad
- Recuperación de contraseñas

### 👥 Gestión de Usuarios
- **Administradores**: Control total del sistema
- **Usuarios**: Pueden hacer reservas y ver recursos disponibles
- Perfiles de usuario editables
- Sistema de activación/desactivación

### 🏢 Gestión de Recursos
- Múltiples tipos de recursos: Aulas, Laboratorios, Equipos, Auditorios, etc.
- Capacidad y características detalladas
- Organización por departamentos
- Estados activo/inactivo
- Imágenes y descripciones

### 📅 Sistema de Reservas
- **Estados de reserva**:
  - Pendiente: Nueva reserva esperando confirmación
  - Confirmada: Reserva aprobada
  - Cancelada: Reserva cancelada por usuario/admin
  - Finalizada: Reserva completada automáticamente
- Validación de disponibilidad en tiempo real
- Prevención de conflictos de horarios
- Observaciones y notas

### 📊 Dashboard y Reportes
- Panel administrativo con estadísticas
- Reportes detallados con filtros avanzados
- Exportación a CSV
- Gráficos de uso y tendencias
- Métricas de rendimiento

### 📱 Interfaz Responsive
- Diseño moderno con Bootstrap 5
- Compatible con dispositivos móviles
- Iconos Font Awesome
- Alertas con SweetAlert2

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 8+**: Lenguaje principal
- **MySQL**: Base de datos
- **PDO**: Capa de abstracción de base de datos
- **Arquitectura MVC**: Separación de responsabilidades

### Frontend
- **Bootstrap 5.3.0**: Framework CSS
- **Font Awesome 6.4.0**: Iconos
- **SweetAlert2**: Alertas elegantes
- **FullCalendar 6.1.9**: Componente de calendario
- **Chart.js**: Gráficos y estadísticas

## 📁 Estructura del Proyecto

```
Sysplanner/
├── api/                          # Endpoints de API REST
│   ├── calendar_events.php       # Eventos del calendario
│   ├── check_availability.php    # Verificar disponibilidad
│   ├── resource_schedule.php     # Horarios de recursos
│   ├── change_reservation_status.php # Cambiar estado de reservas
│   └── export_reports.php        # Exportar reportes
├── auth/                         # Sistema de autenticación
│   ├── login.php                 # Inicio de sesión
│   ├── logout.php                # Cerrar sesión
│   └── register.php              # Registro de usuarios
├── config/                       # Configuración
│   └── database.php              # Conexión a base de datos
├── controllers/                  # Controladores MVC
│   ├── UserController.php        # Gestión de usuarios
│   ├── ResourceController.php    # Gestión de recursos
│   └── ReservationController.php # Gestión de reservas
├── includes/                     # Archivos compartidos
│   ├── header.php                # Cabecera HTML
│   ├── footer.php                # Pie de página
│   └── navbar.php                # Barra de navegación
├── models/                       # Modelos de datos
│   ├── User.php                  # Modelo de usuario
│   ├── Resource.php              # Modelo de recurso
│   ├── Reservation.php           # Modelo de reserva
│   └── Department.php            # Modelo de departamento
├── public/                       # Vistas públicas
│   └── schedule_public.php       # Horarios públicos
├── views/                        # Vistas del sistema
│   ├── admin/                    # Panel administrativo
│   │   ├── dashboard.php         # Dashboard principal
│   │   ├── manage_users.php      # Gestión de usuarios
│   │   ├── edit_user.php         # Editar usuario
│   │   ├── manage_resources.php  # Gestión de recursos
│   │   ├── create_resource.php   # Crear recurso
│   │   ├── view_resource.php     # Ver detalles del recurso
│   │   └── reports.php           # Reportes y análisis
│   ├── user/                     # Panel de usuario
│   │   ├── make_reservation.php  # Hacer reserva
│   │   ├── view_schedule.php     # Ver horarios
│   │   └── profile.php           # Perfil de usuario
│   └── view_resource.php         # Vista pública de recurso
├── index.php                     # Página principal
└── script.sql                    # Script de base de datos
```

## 🚀 Instalación

### Requisitos Previos
- PHP 8.0 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL, mbstring, openssl

### Pasos de Instalación

1. **Clonar el proyecto**
   ```bash
   git clone [url-del-repositorio]
   cd Sysplanner
   ```

2. **Configurar la base de datos**
   - Crear una base de datos MySQL
   - Importar el archivo `script.sql`
   ```sql
   CREATE DATABASE sysplanner;
   USE sysplanner;
   SOURCE script.sql;
   ```

3. **Configurar conexión**
   - Editar `config/database.php`
   - Actualizar credenciales de base de datos:
   ```php
   private $host = "localhost";
   private $db_name = "sysplanner";
   private $username = "tu_usuario";
   private $password = "tu_password";
   ```

4. **Configurar servidor web**
   - Apuntar el document root a la carpeta del proyecto
   - Asegurar que PHP tenga permisos de escritura

5. **Acceso inicial**
   - Usuario admin por defecto: `admin@sysplanner.com`
   - Contraseña: `admin123`

## 👤 Uso del Sistema

### Para Administradores
1. **Gestión de Usuarios**: Crear, editar, activar/desactivar usuarios
2. **Gestión de Recursos**: Administrar recursos disponibles
3. **Monitoreo**: Dashboard con estadísticas en tiempo real
4. **Reportes**: Generar reportes detallados y exportar datos

### Para Usuarios
1. **Hacer Reservas**: Seleccionar recursos y horarios disponibles
2. **Ver Horarios**: Consultar disponibilidad de recursos
3. **Perfil**: Actualizar información personal
4. **Historial**: Ver reservas pasadas y futuras

### Para Visitantes
1. **Consulta Pública**: Ver horarios y disponibilidad sin registro
2. **Información de Recursos**: Detalles y características

## 🔧 API Endpoints

### Autenticación
- `POST /auth/login.php` - Iniciar sesión
- `POST /auth/register.php` - Registrar usuario
- `GET /auth/logout.php` - Cerrar sesión

### Recursos
- `GET /api/resource_schedule.php` - Obtener horarios
- `POST /api/check_availability.php` - Verificar disponibilidad

### Reservas
- `GET /api/calendar_events.php` - Eventos del calendario
- `POST /api/change_reservation_status.php` - Cambiar estado

### Reportes
- `GET /api/export_reports.php` - Exportar reportes

## 📊 Base de Datos

### Tablas Principales
- **usuarios**: Información de usuarios del sistema
- **recursos**: Recursos disponibles para reserva
- **reservas**: Reservas realizadas por usuarios
- **departamentos**: Organización departamental

### Relaciones
- Un usuario puede tener múltiples reservas
- Un recurso puede tener múltiples reservas
- Los recursos pertenecen a departamentos
- Las reservas tienen estados y fechas

## 🔒 Seguridad

- **Autenticación**: Verificación de credenciales segura
- **Autorización**: Control de acceso basado en roles
- **Validación**: Sanitización de datos de entrada
- **Sesiones**: Gestión segura de sesiones de usuario
- **SQL Injection**: Prevención con prepared statements

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama para la funcionalidad (`git checkout -b feature/nueva-funcionalidad`)
3. Commit los cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas, contactar:
- Email: soporte@sysplanner.com
- Documentación: [Wiki del proyecto]
- Issues: [GitHub Issues]

## 🚀 Roadmap

### Próximas Funcionalidades
- [ ] Notificaciones por email
- [ ] App móvil
- [ ] Integración con calendarios externos
- [ ] Sistema de calificaciones
- [ ] Chat en tiempo real
- [ ] API REST completa
- [ ] Múltiples idiomas

---

Desarrollado con ❤️ para mejorar la gestión de recursos organizacionales.
