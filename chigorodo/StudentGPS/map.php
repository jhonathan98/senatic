<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Location: dashboard.php?section=map");
    exit;
}

require_once 'config.php';
require_once 'includes/functions.php';

$user = $_SESSION['user'];
$students = [];
$selected_id = null;
$location_data = null;

// Obtener estudiantes según el rol
if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
    if (isset($_GET['student_id'])) {
        $selected_id = (int)$_GET['student_id'];
    }
    $students = getStudentsByRole($pdo, $user);
} else {
    // Acudiente: solo sus estudiantes
    $students = getStudentsByRole($pdo, $user);
    if (!empty($students)) {
        $selected_id = $students[0]['id']; // Seleccionar el primer estudiante por defecto
    }
}

// Obtener la última ubicación del estudiante seleccionado
if ($selected_id) {
    $stmt = $pdo->prepare("
        SELECT l.*, s.name as student_name 
        FROM locations l 
        JOIN students s ON l.student_id = s.id 
        WHERE l.student_id = ? 
        ORDER BY l.timestamp DESC 
        LIMIT 1
    ");
    $stmt->execute([$selected_id]);
    $location_data = $stmt->fetch();
}
?>

<h2><i class="bi bi-geo-alt-fill text-primary"></i> Ubicación en Tiempo Real</h2>

<?php if (count($students) > 1): ?>
    <form method="GET" class="mb-4">
        <input type="hidden" name="section" value="map">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Seleccionar estudiante:</label>
                <select name="student_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Selecciona un estudiante --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $selected_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?> - <?= htmlspecialchars($s['grade']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-outline-primary" onclick="refreshLocation()">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php if ($selected_id): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-person-fill"></i> 
                <?= htmlspecialchars($location_data['student_name'] ?? 'Estudiante') ?>
            </h5>
            <?php if ($location_data): ?>
                <small class="text-muted">
                    Última actualización: <?= formatDateTime($location_data['timestamp']) ?>
                </small>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($location_data): ?>
                <!-- Mapa con ubicación real -->
                <div id="map" style="height: 450px; width: 100%;"></div>
                
                <!-- Información adicional -->
                <div class="p-3 bg-light">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Latitud:</strong> <?= number_format($location_data['latitude'], 6) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Longitud:</strong> <?= number_format($location_data['longitude'], 6) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Tiempo:</strong> <?= formatDateTime($location_data['timestamp']) ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Sin ubicación -->
                <div class="text-center py-5">
                    <i class="bi bi-geo-alt text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Sin ubicación disponible</h4>
                    <p class="text-muted">No se ha registrado ninguna ubicación para este estudiante.</p>
                    
                    <!-- Mapa por defecto (Chigorodó, Antioquia) -->
                    <div id="map" style="height: 400px; width: 100%; margin-top: 20px;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script del mapa -->
    <script>
        let map;
        let marker;
        
        function initMap() {
            <?php if ($location_data): ?>
                // Ubicación del estudiante
                const studentLocation = {
                    lat: <?= $location_data['latitude'] ?>,
                    lng: <?= $location_data['longitude'] ?>
                };
                
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 16,
                    center: studentLocation,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true
                });
                
                marker = new google.maps.Marker({
                    position: studentLocation,
                    map: map,
                    title: "<?= htmlspecialchars($location_data['student_name']) ?>",
                    icon: {
                        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiMwMDdCRkYiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iOCIgZmlsbD0id2hpdGUiLz4KPC9zdmc+',
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });
                
                // InfoWindow
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <h6><strong><?= htmlspecialchars($location_data['student_name']) ?></strong></h6>
                            <p>Última ubicación registrada<br/>
                            <small><?= formatDateTime($location_data['timestamp']) ?></small></p>
                        </div>
                    `
                });
                
                marker.addListener("click", () => {
                    infoWindow.open(map, marker);
                });
            
            <?php else: ?>
                // Ubicación por defecto - Chigorodó, Antioquia
                const defaultLocation = { lat: 7.6656, lng: -76.6281 };
                
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 13,
                    center: defaultLocation,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true
                });
                
                // Marcador de la ciudad
                marker = new google.maps.Marker({
                    position: defaultLocation,
                    map: map,
                    title: "Chigorodó, Antioquia"
                });
            <?php endif; ?>
        }
        
        function refreshLocation() {
            location.reload();
        }
        
        // Cargar el mapa cuando se carga la página
        window.onload = initMap;
    </script>
    
    <!-- API de Google Maps -->
    <script async defer 
        src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap">
    </script>
    
<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        No hay estudiantes disponibles para mostrar la ubicación.
    </div>
<?php endif; ?>