<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpaceFinder - The Study Room</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .calendar-day {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
        }
        .calendar-day:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }
        .calendar-header {
            font-weight: bold;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .rating {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-1">SpaceFinder</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Search</a></li>
                        <li class="breadcrumb-item active" aria-current="page">The Study Room</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Detalles del espacio -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>The Study Room</h2>
                <div class="d-flex align-items-center mb-3">
                    <span class="rating me-2">
                        <i class="fas fa-star"></i> 4.8 (120 reviews)
                    </span>
                    <span class="mx-2">•</span>
                    <span>10 guests</span>
                    <span class="mx-2">•</span>
                    <span>3 bedrooms</span>
                    <span class="mx-2">•</span>
                    <span>3 beds</span>
                    <span class="mx-2">•</span>
                    <span>2 baths</span>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Selector de fechas -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">Select dates</h3>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <button class="btn btn-outline-secondary"><i class="fas fa-chevron-left"></i></button>
                            <h5 class="mb-0">October 2024 - November 2024</h5>
                            <button class="btn btn-outline-secondary"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        
                        <?php
                        // Configuración de días disponibles (ejemplo: 5-25 de cada mes)
                        function getAvailableDays($year, $month) {
                            $days = [];
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                            for ($i = 5; $i <= 25 && $i <= $daysInMonth; $i++) {
                                $days[] = $i;
                            }
                            return $days;
                        }

                        function renderCalendar($year, $month, $selectedDays = []) {
                            $dias = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                            $firstDayOfMonth = date('w', strtotime("$year-$month-01"));
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                            $availableDays = getAvailableDays($year, $month);

                            echo '<div class="d-flex justify-content-center mb-2">';
                            echo '<strong>' . date('F Y', strtotime("$year-$month-01")) . '</strong>';
                            echo '</div>';
                            echo '<div class="d-flex flex-wrap" style="max-width: 300px;">';

                            // Encabezados
                            foreach ($dias as $dia) {
                                echo '<div class="calendar-day calendar-header">' . $dia . '</div>';
                            }

                            // Espacios en blanco antes del primer día
                            for ($i = 0; $i < $firstDayOfMonth; $i++) {
                                echo '<div class="calendar-day"></div>';
                            }

                            // Días del mes
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $isAvailable = in_array($day, $availableDays);
                                $isSelected = in_array("$year-$month-$day", $selectedDays);
                                $classes = 'calendar-day';
                                if (!$isAvailable) $classes .= ' text-muted';
                                if ($isAvailable) $classes .= ' available-day';
                                if ($isSelected) $classes .= ' selected-day';

                                $disabled = $isAvailable ? '' : 'disabled';
                                $dataAttr = $isAvailable ? "data-date=\"$year-$month-$day\"" : '';
                                echo "<div class=\"$classes\" $dataAttr $disabled>$day</div>";
                            }
                            echo '</div>';
                        }
                        ?>

                        <style>
                            .available-day {
                                border: 1px solid #0d6efd;
                                background: #e7f1ff;
                                cursor: pointer;
                                transition: background 0.2s;
                            }
                            .available-day:hover {
                                background: #b6dbff;
                            }
                            .selected-day {
                                background: #0d6efd !important;
                                color: #fff !important;
                            }
                            .calendar-day[disabled] {
                                pointer-events: none;
                                opacity: 0.5;
                            }
                        </style>

                        <div class="row">
                            <div class="col-md-6">
                                <?php renderCalendar(2024, 10); ?>
                            </div>
                            <div class="col-md-6">
                                <?php renderCalendar(2024, 11); ?>
                            </div>
                        </div>

                        <input type="hidden" id="selectedDates" name="selectedDates" value="">

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const selectedDates = new Set();

                            document.querySelectorAll('.available-day').forEach(function(day) {
                                day.addEventListener('click', function() {
                                    const date = this.getAttribute('data-date');
                                    if (selectedDates.has(date)) {
                                        selectedDates.delete(date);
                                        this.classList.remove('selected-day');
                                    } else {
                                        selectedDates.add(date);
                                        this.classList.add('selected-day');
                                        alert('Seleccionaste el día: ' + date); // <-- Alerta con el día seleccionado
                                    }
                                    document.getElementById('selectedDates').value = Array.from(selectedDates).join(',');
                                });
                            });
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Selector de hora -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">Select time</h3>
                <div class="card">
                    <div class="card-body">
                        <select class="form-select" aria-label="Select time">
                            <option selected>Select time</option>
                            <option value="1">8:00 AM</option>
                            <option value="2">9:00 AM</option>
                            <option value="3">10:00 AM</option>
                            <option value="4">11:00 AM</option>
                            <option value="5">12:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>