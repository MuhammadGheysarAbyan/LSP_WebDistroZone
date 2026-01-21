<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - DistroZone Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #10B981;
            --primary-dark: #047857;
            --secondary: #0F766E;
            --bg-color: #ECFDF5;
            --text-dark: #1F2937;
            --text-light: #64748B;
            --white: #FFFFFF;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%);
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 24px rgba(0,0,0,0.02);
        }
        
        .logo {
            padding: 0 24px 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .nav-badge {
            margin-left: auto;
            background: #EF4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #E2E8F0;
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Chat Layout */
        .chat-container {
            flex: 1;
            display: grid;
            grid-template-columns: 350px 1fr;
            overflow: hidden;
        }
        
        /* Conversation List */
        .conversation-list {
            background: white;
            border-right: 1px solid #E2E8F0;
            overflow-y: auto;
        }
        
        .conversation-header {
            padding: 20px;
            border-bottom: 1px solid #E2E8F0;
            background: #F8FAFC;
        }
        
        .conversation-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .conversation-item {
            padding: 16px 20px;
            border-bottom: 1px solid #F1F5F9;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .conversation-item:hover {
            background: #F8FAFC;
        }
        
        .conversation-item.active {
            background: #ECFDF5;
            border-left: 3px solid var(--primary);
        }
        
        .conversation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-time {
            font-size: 11px;
            color: var(--text-light);
            font-weight: 400;
        }
        
        .conversation-preview {
            font-size: 13px;
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-unread {
            background: var(--primary);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .no-conversations {
            padding: 40px;
            text-align: center;
            color: var(--text-light);
        }
        
        .no-conversations i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #D1D5DB;
        }
        
        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
            background: #F8FAFC;
            height: 100%;
            overflow: hidden; /* Ensure child scrollbar works */
        }
        
        .chat-header {
            padding: 20px 24px;
            background: white;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .chat-header-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .chat-header-info h4 {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        
        .chat-header-info span {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .chat-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .chat-message {
            max-width: 60%;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .chat-message.sent {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .chat-message.received {
            align-self: flex-start;
            background: #F1F5F9;
            color: var(--text-dark);
            border-bottom-left-radius: 4px;
            border: 1px solid #E2E8F0;
        }
        
        .chat-message:hover .delete-msg-btn {
            opacity: 1;
            pointer-events: auto;
        }
        
        .delete-msg-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #EF4444;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            opacity: 0;
            pointer-events: none; /* Prevent accidental clicks when not visible */
            transition: all 0.2s;
            z-index: 10;
        }
        
        .chat-message.sent .delete-msg-btn {
            left: -32px;
        }
        
        .chat-message.received .delete-msg-btn {
            right: -32px;
        }

        .msg-time {
            font-size: 10px;
            opacity: 0.7;
            margin-top: 4px;
            display: block;
            text-align: right;
        }
        
        .chat-message.sent .msg-time {
            color: rgba(255,255,255,0.9);
        }
        
        .chat-input-area {
            padding: 20px 24px;
            background: white;
            border-top: 1px solid #E2E8F0;
            display: flex;
            gap: 16px;
        }
        
        .chat-input {
            flex: 1;
            padding: 14px 20px;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .chat-input:focus {
            border-color: var(--primary);
        }
        
        .chat-send {
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 14px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .chat-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .chat-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .chat-empty-state {
            flex: 1;
            background: none;
            margin-left: 20px;
            margin-top: 20px;
            font-size: 16px;
        }    background: #F8FAFC;
        }
        
        .chat-empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #D1D5DB;
        }
        
        .chat-empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-layer-group" style="font-size: 24px; color: #10B981;"></i>
                <h1>DistroZone</h1>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="karyawan.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Kelola Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kaos.php" class="nav-link">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chat.php" class="nav-link active">
                        <i class="fas fa-comments"></i>
                        Live Chat
                        <span class="nav-badge" id="totalUnread" style="display: none;">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Live Chat</h2>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="text-align: right;">
                        <div style="font-weight: 700;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--primary);">Administrator</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px;">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <div class="chat-container">
                <!-- Conversation List -->
                <div class="conversation-list">
                    <div class="conversation-header">
                        <h3>Percakapan</h3>
                    </div>
                    <div id="conversationList">
                        <div class="no-conversations">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada percakapan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area" id="chatArea">
                    <div class="chat-empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>Pilih Percakapan</h3>
                        <p>Pilih percakapan dari daftar untuk mulai chat</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    (function() {
        let activeConversationId = null;
        let lastMessageId = 0;
        let pollInterval = null;
        let displayedMsgIds = new Set(); // Track displayed message IDs
        let isSending = false; // Flag to block polling during send
        let fetchController = null; // AbortController for fetch
        let pollVersion = 0; // Version counter to ignore stale responses
        
        // Fetch conversations
        function fetchConversations() {
            fetch('../api/chat_conversations.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderConversations(data.conversations);
                        updateTotalUnread(data.total_unread);
                    }
                })
                .catch(console.error);
        }
        
        // Render conversations
        function renderConversations(conversations) {
            const list = document.getElementById('conversationList');
            
            if (!conversations || conversations.length === 0) {
                list.innerHTML = `
                    <div class="no-conversations">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada percakapan</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = conversations.map(conv => {
                const initial = conv.customer_name ? conv.customer_name.charAt(0).toUpperCase() : '?';
                const time = conv.last_message_time ? formatTime(conv.last_message_time) : '';
                
                let preview = conv.last_message;
                // Handle attachment-only messages
                if (!preview && conv.last_attachment) {
                    if (conv.last_attachment_type === 'image') preview = '📷 Foto';
                    else if (conv.last_attachment_type === 'video') preview = '🎥 Video';
                    else preview = '📎 File';
                }
                preview = preview || 'Belum ada pesan';
                
                const isActive = conv.id == activeConversationId;
                
                return `
                    <div class="conversation-item ${isActive ? 'active' : ''}" data-id="${conv.id}" onclick="selectConversation(${conv.id}, '${escapeHtml(conv.customer_name)}')">
                        <div class="conversation-avatar">${initial}</div>
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <span>${escapeHtml(conv.customer_name)}</span>
                                <span class="conversation-time">${time}</span>
                            </div>
                            <div class="conversation-preview">${escapeHtml(preview)}</div>
                        </div>
                        ${conv.unread_count > 0 ? `<div class="conversation-unread">${conv.unread_count}</div>` : ''}
                    </div>
                `;
            }).join('');
        }
        
        // Update total unread badge
        function updateTotalUnread(count) {
            const badge = document.getElementById('totalUnread');
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Select conversation
        window.selectConversation = function(convId, customerName) {
            activeConversationId = convId;
            lastMessageId = 0;
            displayedMsgIds.clear(); // Reset displayed messages for new conversation
            isSending = false;
            pollVersion++;
            
            // Update active state
            document.querySelectorAll('.conversation-item').forEach(el => {
                el.classList.toggle('active', el.dataset.id == convId);
            });
            
            // Show chat area
            const chatArea = document.getElementById('chatArea');
            const initial = customerName ? customerName.charAt(0).toUpperCase() : '?';
            
            chatArea.innerHTML = `
                <div class="chat-header">
                    <div class="chat-header-avatar">${initial}</div>
                    <div class="chat-header-info">
                        <h4>${escapeHtml(customerName)}</h4>
                        <span><i class="fas fa-circle" style="font-size: 8px; color: #10B981;"></i> Customer</span>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                
                <div class="chat-input-area">
                    <input type="file" id="adminFileInput" style="display:none;" accept="image/*,video/mp4,application/pdf">
                    <button class="chat-attach-btn" id="adminAttachBtn" title="Kirim file">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    
                    <input type="text" class="chat-input" id="chatInput" placeholder="Ketik pesan..." autocomplete="off">
                    <button class="chat-send" id="chatSend" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                        Kirim
                    </button>
                </div>
            `;
            
            // Bind attachment events
            const fileInput = document.getElementById('adminFileInput');
            const attachBtn = document.getElementById('adminAttachBtn');
            const input = document.getElementById('chatInput');
            
            attachBtn.onclick = () => fileInput.click();
            
            // No preview logic (removed by request/to prevent crash)

            // Enter key to send
            input.onkeypress = function(e) {
                if (e.key === 'Enter') sendMessage();
            };
            
            // Initial fetch
            fetchMessages();
            // Start polling if not already running
            startPolling();
        };
        
        // Send message - with proper race condition protection
        window.sendMessage = function() {
            const input = document.getElementById('chatInput');
            const fileInput = document.getElementById('adminFileInput');
            const message = input.value.trim();
            const file = fileInput ? fileInput.files[0] : null;
            
            if (!activeConversationId) {
                console.error('No active conversation ID');
                alert('Silakan pilih percakapan terlebih dahulu');
                return;
            }
            
            if ((!message && !file) || isSending) return;
            
            // Stop polling to prevent interference during upload setup
            stopPolling();
            pollVersion++;
            
            isSending = true;
            input.disabled = true;
            
            const msgToSend = message;
            input.value = '';
            
            // Clear attachment UI Immediately
            if (fileInput) {
                 fileInput.value = ''; 
            }

            // Prepare Data
            let fetchBody;
            let fetchHeaders = {};
            
            if (file) {
                fetchBody = new FormData();
                fetchBody.append('message', msgToSend);
                fetchBody.append('conversation_id', activeConversationId);
                fetchBody.append('attachment', file);
            } else {
                fetchHeaders = {'Content-Type': 'application/json'};
                fetchBody = JSON.stringify({
                    message: msgToSend,
                    conversation_id: parseInt(activeConversationId)
                });
            }
            
            fetch('../api/chat_send.php', {
                method: 'POST',
                headers: fetchHeaders,
                body: fetchBody
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // NUCLEAR OPTION: Do NOT add to UI manually.
                    // Rely 100% on Polling to prevent Double Bubbles.
                    // This ensures Single Source of Truth from Server.
                    
                    isSending = false; // Allow polling to run
                    fetchMessages(); // Trigger immediate fetch
                    
                } else {
                    input.value = msgToSend;
                    alert('Gagal mengirim: ' + (data.message || 'Error'));
                }
            })
            .catch(e => {
                console.error(e);
                input.value = msgToSend;
                alert('Gagal mengirim pesan');
            })
            .finally(() => {
                isSending = false;
                input.disabled = false;
                input.focus();
                // Resume polling if not already running
                // fetchMessages calls above might be async, but startPolling sets interval
                if (activeConversationId) {
                    // Small delay to ensure we don't spam if user clicks fast
                     setTimeout(() => {
                        if (!isSending) startPolling();
                    }, 500);
                }
            });
        };
        
        // Fetch messages with abort controller and version check
        function fetchMessages() {
            if (!activeConversationId || isSending) return;
            
            const currentVersion = pollVersion;
            
            // Abort previous fetch
            if (fetchController) {
                fetchController.abort();
            }
            fetchController = new AbortController();
            
            fetch('../api/chat_fetch.php?conversation_id=' + activeConversationId + '&last_id=' + lastMessageId, {
                signal: fetchController.signal
            })
            .then(res => res.json())
            .then(data => {
                // Ignore if sending or version changed
                if (isSending || currentVersion !== pollVersion) {
                    return;
                }
                
                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        // Path correction for polling in admin
                        // msg.attachment is 'assets/...'
                        if (msg.attachment && !msg.attachment.startsWith('../')) {
                            msg.attachment = '../' + msg.attachment;
                        }
                        
                        const msgId = parseInt(msg.id);
                        if (!displayedMsgIds.has(msgId)) {
                            displayedMsgIds.add(msgId);
                            if (msgId > lastMessageId) lastMessageId = msgId;
                            addMessageToUI(msg);
                        }
                    });
                    
                    // Update last ID properly from max returned
                    // (Already handled in loop)
                    
                    // Only fetch convs if we got new messages
                    fetchConversations(); 
                }
            })
            .catch(e => {
                if (e.name !== 'AbortError') console.error(e);
            });
        }
        
        // Add message to DOM
        function addMessageToUI(msg) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return;
            
            // Duplicate check (Strict String Coercion)
            if (msg.id && document.querySelector(`.chat-message[data-msg-id="${msg.id}"]`)) return;
            
            // Orphan check
            if (msg.id && msg.message) {
                 const existingMsgs = chatMessages.querySelectorAll('.chat-message:not([data-msg-id])');
                 existingMsgs.forEach(el => {
                     if (el.textContent.includes(msg.message)) el.remove();
                 });
            }
            
            const div = document.createElement('div');
            // Normalize role
            const role = String(msg.sender_role || '').toLowerCase().trim();
            div.className = `chat-message ${role === 'admin' ? 'sent' : 'received'}`;
            if (msg.id) div.dataset.msgId = msg.id;
            
            const time = formatTime(msg.created_at);
            
            // Attachment HTML
            let attachmentHtml = '';
            if (msg.attachment) {
                if (msg.attachment_type === 'image') {
                    attachmentHtml = `<div style="margin-bottom:5px;"><img src="${msg.attachment}" style="max-width:200px; border-radius:8px; cursor:pointer;" onclick="window.open(this.src)"></div>`;
                } else if (msg.attachment_type === 'video') {
                    attachmentHtml = `<div style="margin-bottom:5px;"><video src="${msg.attachment}" controls style="max-width:200px; border-radius:8px;"></video></div>`;
                } else {
                    attachmentHtml = `<div style="margin-bottom:5px;"><a href="${msg.attachment}" target="_blank" style="color:inherit; text-decoration:underline;"><i class="fas fa-file"></i> File</a></div>`;
                }
            }
            
            // Delete Button
            const deleteBtn = `<button class="delete-msg-btn" onclick="deleteMessage(${msg.id})" title="Hapus Chat"><i class="fas fa-trash"></i></button>`;
            
            div.innerHTML = `
                ${deleteBtn}
                ${attachmentHtml}
                ${escapeHtml(msg.message)}
                <span class="msg-time">${time}</span>
            `;
            
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Delete Message Function
        window.deleteMessage = function(id) {
            if (!confirm('Hapus pesan ini selamanya?')) return;
            
            fetch('../api/chat_delete.php', {
                method: 'POST',
                body: JSON.stringify({ message_id: id }),
                headers: {'Content-Type': 'application/json'}
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.querySelector(`.chat-message[data-msg-id="${id}"]`);
                    if (el) {
                        el.style.transition = 'opacity 0.3s';
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 300);
                    }
                    displayedMsgIds.delete(parseInt(id));
                } else {
                    alert('Gagal menghapus: ' + (data.message || 'Error'));
                }
            })
            .catch(e => console.error(e));
        };
        
        // Polling with immediate first fetch
        function startPolling() {
            stopPolling();
            fetchMessages(); // Fetch immediately
            pollInterval = setInterval(fetchMessages, 2000);
        }
        
        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (fetchController) {
                fetchController.abort();
                fetchController = null;
            }
        }
        
        // Helper functions
        function formatTime(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Baru saja';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'j';
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initial load
        fetchConversations();
        setInterval(fetchConversations, 3000);
    })();
    </script>
</body>
</html>
