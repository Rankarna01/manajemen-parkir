<?php
// templates/header_app.php

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistem Manajemen Parkir'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Palet Warna Kustom
                        'primary': '#0B1F4F', // Navy Blue (untuk header, sidebar, elemen utama)
                        'secondary': '#EBEFF3', // Light Gray (untuk latar belakang konten)
                        'accent': '#F9A825', // Bright Gold (untuk tombol, link aktif, highlight)
                        'success': '#10B981', // Emerald Green
                        'danger': '#EF4444', // Deep Red
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Sembunyikan scrollbar untuk Chrome, Safari, dan Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Sembunyikan scrollbar untuk IE, Edge, dan Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-secondary"> <div class="flex h-screen bg-secondary">
    

