# Sistema de Mentorías - Gestión de Suscripciones y Pagos

## Nuevas Funcionalidades Implementadas

### 1. Sistema de Pagos Completo

#### Características principales:
- **Selección de planes**: Básico ($299), Estándar ($499), Premium ($799)
- **Múltiples métodos de pago**: Tarjeta de crédito, PayPal, Transferencia bancaria
- **Sistema de descuentos**: Códigos promocionales (NUEVO50, FAMILIA20)
- **Validación de formularios**: Validación en tiempo real de campos
- **Confirmación de pago**: Página de confirmación con detalles completos

#### Flujo de trabajo:
1. Usuario selecciona un plan en `paquetes.php`
2. Redirige a `pagos.php` con parámetros del plan
3. Usuario completa información de pago
4. Sistema procesa el pago y crea la suscripción
5. Redirige a `confirmacion_pago.php` con confirmación

### 2. Panel de Administración de Suscripciones

#### Funcionalidades para administradores:
- **Dashboard de estadísticas**: Total de suscripciones, activas, suspendidas, ingresos
- **Gestión de estados**: Cambiar estado de suscripciones (activa, suspendida, cancelada, vencida)
- **Filtros avanzados**: Por estado, tipo de plan, fechas
- **Detalles completos**: Información del usuario, plan, historial de pagos
- **Acciones rápidas**: Suspender, reactivar, ver detalles

#### Acceso:
- Solo usuarios con rol `admin`
- Disponible en: `/admin/gestionar_suscripciones.php`
- Enlace agregado al menú principal del dashboard

### 3. Base de Datos Actualizada y Sistema de Roles

#### Gestión de Roles Implementada:
- **Usuarios regulares**: Rol 'usuario' (asignado automáticamente en registro)
- **Mentores**: Rol 'mentor' (asignado por administradores)
- **Administradores**: Rol 'admin' (asignado manualmente)

#### Nuevas tablas:
```sql
-- Tabla usuarios actualizada con roles
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'mentor', 'usuario') DEFAULT 'usuario',
    -- ... otros campos
);

-- Tabla mentores con relación a usuarios
CREATE TABLE mentores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,  -- NUEVA: Relación con tabla usuarios
    nombre_completo VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    -- ... otros campos
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Planes personalizados
CREATE TABLE planes_personalizados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    tipo ENUM('basico', 'estandar', 'premium') NOT NULL,
    caracteristicas JSON,
    sesiones_mensuales INT,
    duracion_sesion DECIMAL(3,1),
    -- ... campos adicionales
);

-- Suscripciones de usuarios
CREATE TABLE suscripciones_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    plan_id INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    estado ENUM('activa', 'suspendida', 'cancelada', 'vencida'),
    metodo_pago ENUM('tarjeta', 'transferencia', 'paypal', 'efectivo'),
    monto_mensual DECIMAL(10,2),
    proximo_pago DATE,
    -- ... campos adicionales
);

-- Historial de pagos
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suscripcion_id INT,
    compra_id INT,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('tarjeta', 'transferencia', 'paypal', 'efectivo'),
    numero_transaccion VARCHAR(100),
    estado ENUM('exitoso', 'fallido', 'pendiente', 'reembolsado'),
    -- ... campos adicionales
);
```

#### Campos actualizados:
- Tabla `usuarios`: Campo `rol` con valores por defecto apropiados
- Tabla `mentores`: Nuevo campo `usuario_id` para relación con usuarios
- Tabla `compras_paquetes`: Agregados campos para descuentos y códigos promocionales
- Nuevos índices para mejor rendimiento

### 4. Archivos Creados/Modificados

#### Nuevos archivos:
- `pagos.php` - Sistema de procesamiento de pagos
- `confirmacion_pago.php` - Página de confirmación
- `admin/gestionar_suscripciones.php` - Panel de administración de suscripciones
- `admin/procesar_suscripcion.php` - API para acciones de administrador
- `admin/registrar_mentor.php` - **NUEVO**: Formulario para registrar mentores con roles

#### Archivos modificados:
- `script.sql` - **ACTUALIZADO**: Base de datos con sistema de roles y nuevas tablas
- `paquetes.php` - Enlaces a sistema de pagos
- `dashboard.php` - Enlace a gestión de suscripciones para admins
- `registro.php` - **CORREGIDO**: Asigna rol 'usuario' correctamente
- `admin/admin_mentores.php` - **ACTUALIZADO**: Enlace al nuevo formulario de registro

### 5. Características de Seguridad

- **Validación de sesiones**: Verificación de roles de usuario
- **Sanitización de datos**: Uso de prepared statements
- **Validación de formularios**: Cliente y servidor
- **Transacciones SQL**: Consistencia de datos
- **Números de transacción únicos**: Para seguimiento de pagos

### 6. Interfaz de Usuario

#### Características del diseño:
- **Responsive**: Compatible con dispositivos móviles
- **Animaciones**: Efectos suaves y profesionales
- **Feedback visual**: Indicadores de carga y estados
- **Iconografía**: FontAwesome para mejor UX
- **Colores consistentes**: Tema morado corporativo

### 7. Simulación de Datos

El sistema incluye datos de ejemplo para demostración:
- 3 planes predefinidos con características específicas
- 2 suscripciones de ejemplo
- Historial de pagos simulado
- Códigos promocionales activos

### 8. Uso del Sistema

#### Para usuarios regulares (rol: 'usuario'):
1. **Registro**: Automáticamente asignado rol 'usuario'
2. **Suscripciones**: Acceder a "Paquetes y promociones" desde el dashboard
3. **Selección**: Elegir plan deseado
4. **Pago**: Completar información de pago
5. **Confirmación**: Recibir confirmación de suscripción

#### Para mentores (rol: 'mentor'):
1. **Registro**: Solo puede ser realizado por administradores
2. **Acceso**: Credenciales proporcionadas por el administrador
3. **Dashboard**: Acceso a funciones específicas de mentor (por implementar)

#### Para administradores (rol: 'admin'):
1. **Gestión de Suscripciones**: 
   - Acceder a "Gestionar Suscripciones" desde el dashboard
   - Ver estadísticas generales
   - Filtrar y buscar suscripciones
   - Gestionar estados y ver detalles
2. **Gestión de Mentores**:
   - Acceder a "Administrar Mentores" desde el dashboard
   - Registrar nuevos mentores con "Registrar Nuevo Mentor"
   - Gestionar mentores existentes

### 9. Próximas Mejoras Sugeridas

- [ ] Integración con pasarelas de pago reales (Stripe, PayPal)
- [ ] Notificaciones por email automáticas
- [ ] Reportes y análisis avanzados
- [ ] Sistema de facturación automática
- [ ] Renovación automática de suscripciones
- [ ] Webhooks para pagos externos
- [ ] Sistema de reembolsos
- [ ] Métricas de conversión

## Sistema de Roles y Permisos

### Roles Implementados:
1. **usuario** (por defecto)
   - Puede registrarse automáticamente
   - Acceso a suscripciones y mentores
   - No puede acceder a funciones administrativas

2. **mentor** 
   - Solo puede ser creado por administradores
   - Requiere información profesional adicional
   - Vinculado a tabla de usuarios para autenticación
   - Aparece en listado de mentores disponibles

3. **admin**
   - Acceso completo al sistema
   - Puede gestionar suscripciones
   - Puede registrar y gestionar mentores
   - Acceso a estadísticas y reportes

### Flujo de Registro:

#### Usuarios Regulares:
```
registro.php → Tabla usuarios (rol: 'usuario') → login.php → dashboard.php
```

#### Mentores:
```
admin/registrar_mentor.php → Tabla usuarios (rol: 'mentor') + Tabla mentores → 
Credenciales enviadas → login.php → dashboard.php (con permisos de mentor)
```

## Instalación y Configuración

1. Ejecutar el `script.sql` actualizado para crear las nuevas tablas con sistema de roles
2. Verificar permisos de archivo en el servidor
3. Configurar datos de conexión en `config/db.php`
4. **IMPORTANTE**: Asegurar que el usuario admin tenga rol 'admin' en la base de datos
5. Los mentores deben ser registrados desde el panel de administración

## Tecnologías Utilizadas

- **Backend**: PHP 7.4+, MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Frameworks**: Bootstrap 5.3, FontAwesome 6.0
- **AJAX**: Fetch API para interacciones asíncronas
- **Base de datos**: PDO para conexiones seguras
