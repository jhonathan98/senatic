
</div> <!-- Cerrar container del header -->

<footer class="bg-primary text-white text-center py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="mb-2"><i class="bi bi-box-seam me-2"></i>InsumoTrack</h6>
                <p class="mb-0">Sistema de Gestión de Préstamos de Insumos</p>
            </div>
            <div class="col-md-6">
                <p class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>
                    Control preciso, insumo al día
                </p>
                <small>&copy; <?php echo date('Y'); ?> InsumoTrack. Todos los derechos reservados.</small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle (incluye Popper.js para dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script adicional para asegurar que los dropdowns funcionen -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los dropdowns manualmente
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Debug: verificar que Bootstrap esté cargado
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JavaScript no está cargado correctamente');
    } else {
        console.log('Bootstrap JavaScript cargado correctamente');
    }
});
</script>

</body>
</html>