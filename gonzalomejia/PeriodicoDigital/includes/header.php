<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Periódico Digital - Colegio Gonzalo Mejía'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Estilos personalizados mínimos -->
    <style>
        .carousel-item img {
            height: 400px;
            object-fit: cover;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .news-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .sidebar {
            background-color: #f8f9fa;
            min-height: calc(100vh - 56px);
        }
        .footer {
            background-color: #212529;
            color: white;
            margin-top: 50px;
        }
    </style>
</head>
<body>