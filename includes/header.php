<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DistroZone - Toko Kaos Distro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <!-- Live Chat Widget -->
    <div class="chat-widget">
        <button class="chat-btn" onclick="toggleChat()">
            <i class="fas fa-comments"></i>
        </button>
        <div class="chat-box" id="chatBox">
            <div class="chat-header">
                <h6><i class="fas fa-headset"></i> Customer Service</h6>
                <button onclick="toggleChat()" class="btn-close"></button>
            </div>
            <div class="chat-body" id="chatBody">
                <div class="chat-message cs-message">
                    <p>Halo! Selamat datang di DistroZone. Ada yang bisa saya bantu?</p>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" id="chatInput" placeholder="Ketik pesan..." onkeypress="sendMessageOnEnter(event)">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>