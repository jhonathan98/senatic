<?php
// controllers/UserController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Department.php';

class UserController {
    private $user;
    private $department;

    public function __construct() {
        $this->user = new User();
        $this->department = new Department();
    }

    // Mostrar lista de usuarios
    public function index() {
        $stmt = $this->user->read();
        return $stmt;
    }

    // Mostrar formulario de creación
    public function create() {
        $departments = $this->department->read();
        return $departments;
    }

    // Guardar nuevo usuario
    public function store($data) {
        // Validar datos
        if (empty($data['nombre_completo']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados.'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del email no es válido.'];
        }

        // Verificar si el email ya existe
        $this->user->email = $data['email'];
        if ($this->user->emailExists()) {
            return ['success' => false, 'message' => 'Ya existe un usuario con este email.'];
        }

        // Asignar datos
        $this->user->nombre_completo = $data['nombre_completo'];
        $this->user->email = $data['email'];
        $this->user->password_hash = $data['password'];
        $this->user->rol = $data['rol'] ?? 'usuario';
        $this->user->departamento_id = !empty($data['departamento_id']) ? $data['departamento_id'] : null;
        $this->user->activo = isset($data['activo']) ? (bool)$data['activo'] : true;

        if ($this->user->create()) {
            return ['success' => true, 'message' => 'Usuario creado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al crear el usuario.'];
        }
    }

    // Mostrar un usuario específico
    public function show($id) {
        $this->user->id = $id;
        if ($this->user->readOne()) {
            return $this->user;
        }
        return null;
    }

    // Mostrar formulario de edición
    public function edit($id) {
        $user = $this->show($id);
        $departments = $this->department->read();
        
        return ['user' => $user, 'departments' => $departments];
    }

    // Actualizar usuario
    public function update($id, $data) {
        // Validar datos
        if (empty($data['nombre_completo']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'Los campos nombre y email son obligatorios.'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del email no es válido.'];
        }

        $this->user->id = $id;
        
        // Verificar si el email ya existe (excluyendo el usuario actual)
        $existing_user = new User();
        $existing_user->email = $data['email'];
        if ($existing_user->emailExists()) {
            // Verificar si es el mismo usuario
            $stmt = $existing_user->read();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['email'] === $data['email'] && $row['id'] != $id) {
                    return ['success' => false, 'message' => 'Ya existe otro usuario con este email.'];
                }
            }
        }

        // Asignar datos
        $this->user->nombre_completo = $data['nombre_completo'];
        $this->user->email = $data['email'];
        $this->user->rol = $data['rol'] ?? 'usuario';
        $this->user->departamento_id = !empty($data['departamento_id']) ? $data['departamento_id'] : null;
        $this->user->activo = isset($data['activo']) ? (bool)$data['activo'] : true;

        if ($this->user->update()) {
            return ['success' => true, 'message' => 'Usuario actualizado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el usuario.'];
        }
    }

    // Eliminar usuario
    public function destroy($id) {
        $this->user->id = $id;
        
        if ($this->user->delete()) {
            return ['success' => true, 'message' => 'Usuario eliminado exitosamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar el usuario.'];
        }
    }

    // Autenticar usuario
    public function authenticate($email, $password) {
        if ($this->user->authenticate($email, $password)) {
            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['user_nombre'] = $this->user->nombre_completo;
            $_SESSION['user_email'] = $this->user->email;
            $_SESSION['user_rol'] = $this->user->rol;
            $_SESSION['user_departamento'] = $this->user->departamento_id;
            
            return ['success' => true, 'message' => 'Autenticación exitosa.'];
        } else {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }
    }

    // Cerrar sesión
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada exitosamente.'];
    }

    // Cambiar contraseña
    public function changePassword($id, $current_password, $new_password, $confirm_password) {
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if (strlen($new_password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        $this->user->id = $id;
        if ($this->user->readOne()) {
            // Verificar contraseña actual
            if (!password_verify($current_password, $this->user->password_hash)) {
                return ['success' => false, 'message' => 'La contraseña actual es incorrecta.'];
            }

            if ($this->user->changePassword($new_password)) {
                return ['success' => true, 'message' => 'Contraseña cambiada exitosamente.'];
            } else {
                return ['success' => false, 'message' => 'Error al cambiar la contraseña.'];
            }
        }

        return ['success' => false, 'message' => 'Usuario no encontrado.'];
    }
}
?>
