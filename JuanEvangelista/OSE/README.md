# OSE - Optimización del Sistema Educativo

## 📚 Descripción

OSE es una plataforma educativa interactiva diseñada para mejorar el rendimiento académico de los estudiantes mediante exámenes adaptativos, gamificación y retroalimentación inmediata. El sistema está alineado con el currículo nacional colombiano y ofrece una experiencia de aprendizaje personalizada.

## ✨ Características Principales

### 🎯 Funcionalidades Clave
- **Contenido Curricular**: Alineado con el currículo nacional colombiano
- **Exámenes Interactivos**: Evaluaciones adaptativas con retroalimentación inmediata
- **Gamificación**: Sistema de logros, puntos y recompensas
- **Modo Offline**: Funcionalidad básica sin conexión a internet
- **Dashboard Educativo**: Paneles para docentes y acudientes
- **Progreso Personalizado**: Seguimiento detallado del rendimiento

### 🏆 Sistema de Gamificación
- **Puntos por respuesta correcta**: 10 puntos
- **Bonus por examen completo**: 50 puntos
- **Racha perfecta**: 100 puntos adicionales
- **Logros desbloqueables**:
  - 🥇 Primer Paso: Completar el primer examen
  - 🎯 Dedicado: Completar 5 exámenes
  - 🚀 Experto: Completar 10 exámenes
  - ⭐ Perfección: Obtener 100% en un examen
  - 🔥 Imparable: 3 exámenes perfectos consecutivos

### 📊 Analíticas y Progreso
- Estadísticas detalladas por materia
- Gráficos de progreso temporal
- Identificación de áreas de mejora
- Recomendaciones personalizadas
- Sistema de niveles basado en rendimiento

## 🛠 Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework CSS**: Bootstrap 5.3
- **Iconos**: Font Awesome 6.0
- **Gráficos**: Chart.js
- **Servidor**: Apache (XAMPP recomendado)

## 📁 Estructura del Proyecto

```
OSE/
├── assets/
│   ├── css/
│   │   └── style.css          # Estilos personalizados
│   └── images/                # Imágenes y recursos
├── includes/
│   ├── config.php            # Configuración general
│   ├── db_connect.php        # Conexión a base de datos
│   └── functions.php         # Funciones utilitarias
├── pages/
│   ├── welcome.php           # Dashboard principal
│   ├── select_grade.php      # Selección de grado
│   ├── select_subject.php    # Selección de materia
│   ├── exam_list.php         # Lista de exámenes
│   ├── exam.php              # Interfaz de examen
│   ├── results.php           # Resultados del examen
│   ├── profile.php           # Perfil del usuario
│   ├── progress.php          # Página de progreso
│   ├── login.php             # Inicio de sesión
│   ├── register.php          # Registro de usuario
│   └── logout.php            # Cerrar sesión
├── index.php                 # Página principal
├── script.sql               # Base de datos y datos de ejemplo
└── README.md                # Documentación
```

## 🚀 Instalación y Configuración

### Prerrequisitos
- XAMPP o servidor con PHP 7.4+ y MySQL
- Navegador web moderno

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone <repositorio>
   cd OSE
   ```

2. **Configurar el servidor web**
   - Colocar el proyecto en la carpeta `htdocs` de XAMPP
   - Iniciar Apache y MySQL desde el panel de XAMPP

3. **Crear la base de datos**
   - Abrir phpMyAdmin (http://localhost/phpmyadmin)
   - Crear una nueva base de datos llamada `ose_db`
   - Importar el archivo `script.sql`

4. **Configurar la conexión a la base de datos** (si es necesario)
   ```php
   // includes/config.php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'ose_db');
   ```

5. **Acceder a la aplicación**
   - Navegar a `http://localhost/senatic/senatic/JuanEvangelista/OSE/`

## 👤 Uso del Sistema

### Flujo de Usuario Estudiante

1. **Registro/Inicio de Sesión**
   - Crear cuenta nueva o iniciar sesión
   - Verificación de credenciales

2. **Selección de Contenido**
   - Elegir grado académico
   - Seleccionar materia de interés
   - Explorar exámenes disponibles

3. **Realizar Examen**
   - Responder preguntas interactivas
   - Temporizador y progreso en tiempo real
   - Envío y procesamiento de respuestas

4. **Ver Resultados**
   - Puntuación detallada
   - Retroalimentación por pregunta
   - Logros desbloqueados
   - Recomendaciones de mejora

5. **Seguimiento de Progreso**
   - Estadísticas personales
   - Gráficos de rendimiento
   - Historial de exámenes

### Tipos de Usuario

- **Estudiante**: Acceso completo a exámenes y progreso personal
- **Docente**: Dashboard para monitorear estudiantes (futuro)
- **Admin**: Gestión completa del sistema (futuro)

## 🎨 Características de Diseño

### Interfaz de Usuario
- **Responsive Design**: Adaptable a móviles, tablets y desktop
- **Animaciones Suaves**: Transiciones y efectos visuales
- **Gamificación Visual**: Elementos motivacionales
- **Accesibilidad**: Contraste adecuado y navegación clara

### Experiencia de Usuario
- **Navegación Intuitiva**: Flujo lógico y fácil de seguir
- **Retroalimentación Inmediata**: Respuestas visuales a acciones
- **Personalización**: Adaptación al progreso individual
- **Motivación**: Sistema de recompensas y logros

## 📈 Roadmap y Mejoras Futuras

### Próximas Funcionalidades
- [ ] Dashboard para docentes
- [ ] Sistema de reportes avanzados
- [ ] Modo offline completo
- [ ] Aplicación móvil nativa
- [ ] Integración con LMS existentes
- [ ] Análisis de aprendizaje con IA
- [ ] Contenido multimedia interactivo
- [ ] Sistema de tutorías virtuales

### Mejoras Técnicas
- [ ] API RESTful
- [ ] Autenticación con OAuth
- [ ] Base de datos distribuida
- [ ] Cache inteligente
- [ ] Monitoreo y analytics
- [ ] Tests automatizados

## 🐛 Problemas Conocidos

- Los datos de ejemplo son limitados
- No hay validación avanzada de seguridad
- Falta implementación de roles avanzados
- No hay backup automático

## 🤝 Contribución

Para contribuir al proyecto:

1. Fork el proyecto
2. Crear una rama para la nueva funcionalidad
3. Commit los cambios
4. Push a la rama
5. Crear un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 👨‍💻 Autor

**Juan Evangelista**
- Desarrollador Full Stack
- Especialista en Sistemas Educativos

## 📞 Soporte

Para soporte o preguntas:
- Email: soporte@ose-educativo.com
- Documentación: [Wiki del proyecto]
- Issues: [GitHub Issues]

---

**OSE - Facilitando el aprendizaje, potenciando el conocimiento** 🚀
