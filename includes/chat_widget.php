<?php
/**
 * Chat Widget Component for Customer
 * Include this file at the end of customer pages (before </body>)
 */

// Only show if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    return;
}
?>

<!-- Chat Widget Styles -->
<style>
.chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 2147483647;
    font-family: 'Outfit', sans-serif;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10B981 0%, #0F766E 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    transition: transform 0.3s ease;
    position: relative;
}

.chat-toggle:hover {
    transform: scale(1.1);
}

.chat-toggle .chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    background: #EF4444;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.chat-box {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 360px;
    height: 480px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.chat-box.active {
    display: flex;
}

.chat-header {
    background: linear-gradient(135deg, #10B981 0%, #0F766E 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.chat-header-info h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.chat-header-info span {
    font-size: 11px;
    opacity: 0.8;
}

.chat-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #F8FAFC;
}

.chat-message {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.4;
    word-wrap: break-word;
}

.chat-message.sent {
    align-self: flex-end;
    background: linear-gradient(135deg, #10B981 0%, #0F766E 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.received {
    align-self: flex-start;
    background: white;
    color: #1F2937;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.chat-message .msg-sender {
    font-size: 10px;
    font-weight: 600;
    margin-bottom: 4px;
    display: block;
    opacity: 0.8;
}

.chat-message .msg-time {
    font-size: 9px;
    opacity: 0.7;
    margin-top: 4px;
    display: block;
}

.chat-empty {
    text-align: center;
    color: #9CA3AF;
    padding: 30px;
    font-size: 13px;
}

.chat-empty i {
    font-size: 40px;
    margin-bottom: 12px;
    color: #D1D5DB;
    display: block;
}

.chat-input-area {
    padding: 12px 16px;
    background: white;
    border-top: 1px solid #E5E7EB;
    display: flex;
    gap: 10px;
}

.chat-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
}

.chat-input:focus {
    border-color: #10B981;
}

.chat-input:disabled {
    background: #f3f4f6;
}

.chat-send {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #10B981 0%, #0F766E 100%);
    border: none;
    border-radius: 10px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.chat-send:hover {
    opacity: 0.9;
}

.chat-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

    .chat-attach-btn {
        background: none;
        border: none;
        color: #9CA3AF;
        padding: 0 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.3s;
    }
    
    .chat-attach-btn:hover {
        color: #10B981;
    }
    
    .attachment-preview {
        padding: 8px 16px;
        background: #F3F4F6;
        border-top: 1px solid #E5E7EB;
        display: none;
        align-items: center;
        gap: 10px;
        font-size: 11px;
    }
    
    .attachment-preview.active {
        display: flex;
    }
    
    .attachment-preview img {
        height: 30px;
        width: 30px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .remove-attachment {
        margin-left: auto;
        color: #EF4444;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 14px;
    }

    /* Media queries */
    @media (max-width: 480px) {
        .chat-box {
            width: calc(100vw - 40px);
            height: calc(100vh - 120px);
        }
    }
</style>

<!-- Chat Widget HTML -->
<div class="chat-widget" id="chatWidget">
    <button class="chat-toggle" id="chatToggle" type="button">
        <i class="fas fa-comments"></i>
        <span class="chat-badge" id="chatBadge">0</span>
    </button>
    
    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            <div class="chat-header-avatar">
                <i class="fas fa-headset"></i>
            </div>
            <div class="chat-header-info">
                <h4>DistroZone Support</h4>
                <span id="chatStatus"><i class="fas fa-circle" style="font-size: 7px; color: #34D399;"></i> Online</span>
            </div>
        </div>
        
        <div id="offlineMessage" style="display: none; padding: 15px; background: #FFF7ED; border-bottom: 1px solid #FFEDD5; color: #9A3412; font-size: 13px; text-align: center;">
            <i class="fas fa-clock" style="margin-bottom: 5px;"></i><br>
            Maaf, layanan chat kami sedang offline.<br>
            <strong>Jam Operasional: 09:00 - 21:00</strong><br>
            Silakan tinggalkan pesan, kami akan membalas saat online.
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="chat-empty" id="chatEmptyState">
                <i class="fas fa-comments"></i>
                <p>Mulai percakapan dengan admin!</p>
            </div>
        </div>
        
        <div class="attachment-preview" id="attachmentPreview">
            <span id="previewIcon"><i class="fas fa-file"></i></span>
            <span id="previewName">filename.jpg</span>
            <button class="remove-attachment" onclick="clearAttachment()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="chat-input-area">
            <input type="file" id="chatFileInput" style="display: none;" accept="image/*,video/mp4,application/pdf">
            <button class="chat-attach-btn" id="chatAttachBtn" type="button" title="Kirim Foto/Video">
                <i class="fas fa-paperclip"></i>
            </button>
            <input type="text" class="chat-input" id="chatInput" placeholder="Ketik pesan..." autocomplete="off">
            <button class="chat-send" id="chatSend" type="button">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    var chatToggle = document.getElementById('chatToggle');
    var chatBox = document.getElementById('chatBox');
    var chatMessages = document.getElementById('chatMessages');
    var chatInput = document.getElementById('chatInput');
    var chatSend = document.getElementById('chatSend');
    var chatBadge = document.getElementById('chatBadge');
    var chatEmptyState = document.getElementById('chatEmptyState');
    
    // Attachment Elements
    var chatAttachBtn = document.getElementById('chatAttachBtn');
    var chatFileInput = document.getElementById('chatFileInput');
    var attachmentPreview = document.getElementById('attachmentPreview');
    var previewIcon = document.getElementById('previewIcon');
    var previewName = document.getElementById('previewName');
    
    // Business Hours Logic
    function checkBusinessHours() {
        var now = new Date();
        var hour = now.getHours();
        // Set Business Hours: 09:00 - 21:00
        var startHour = 9;
        var endHour = 21;
        var isOnline = hour >= startHour && hour < endHour;
        
        var statusEl = document.getElementById('chatStatus');
        var offlineMsg = document.getElementById('offlineMessage');
        var inputArea = document.querySelector('.chat-input-area');
        
        if (isOnline) {
            statusEl.innerHTML = '<i class="fas fa-circle" style="font-size: 7px; color: #34D399;"></i> Online';
            offlineMsg.style.display = 'none';
            // Enable inputs if previously disabled by offline logic (not by sending)
            if (!isSending) {
               // chatInput.disabled = false; // logic handled by isSending
            }
        } else {
            statusEl.innerHTML = '<i class="fas fa-circle" style="font-size: 7px; color: #9CA3AF;"></i> Offline';
            offlineMsg.style.display = 'block';
            // Optional: Disable input or just leave it for "leaving a message"
            // The prompt asks for "professional notification", usually allowing offline messages is better.
            // But if user wants strictly "notif kasir offline", maybe just warn.
            // We kept the input enabled so they can leave a message.
        }
    }
    
    // Check on load and every minute
    checkBusinessHours();
    setInterval(checkBusinessHours, 60000);
    
    // File Handler
    chatAttachBtn.onclick = function() { chatFileInput.click(); };
    
    chatFileInput.onchange = function() {
        if (this.files && this.files[0]) {
            var file = this.files[0];
            previewName.textContent = file.name;
            attachmentPreview.classList.add('active');
            
            // Set icon based on type
            if (file.type.startsWith('image/')) {
                previewIcon.innerHTML = '<i class="fas fa-image"></i>';
                // Try preview image
                var reader = new FileReader();
                reader.onload = function(e) {
                     previewIcon.innerHTML = '<img src="' + e.target.result + '">';
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                previewIcon.innerHTML = '<i class="fas fa-video"></i>';
            } else {
                previewIcon.innerHTML = '<i class="fas fa-file"></i>';
            }
        }
    };
    
    window.clearAttachment = function() {
        chatFileInput.value = '';
        attachmentPreview.classList.remove('active');
        previewIcon.innerHTML = '<i class="fas fa-file"></i>';
    };
    
    // State
    var lastMsgId = 0;
    var convId = null;
    var isOpen = false;
    var pollTimer = null;
    var bgPollTimer = null;
    var isSending = false;
    var displayedMsgIds = {}; // Track displayed message IDs
    var pollAbortController = null; // To cancel in-flight fetch
    var pollVersion = 0; // Version counter to ignore stale responses

    if (!chatToggle || !chatBox) {
        console.error('Chat elements not found');
        return;
    }

    // Toggle chat
    chatToggle.onclick = function() {
        isOpen = !isOpen;
        if (isOpen) {
            chatBox.classList.add('active');
            chatInput.focus();
            startPoll(); // startPoll will call loadMessages first
            hideBadge();
        } else {
            chatBox.classList.remove('active');
            stopPoll();
        }
    };

    // Send message - abort any running poll and pause polling
    function sendMsg() {
        var msg = chatInput.value.trim();
        var file = chatFileInput.files[0];
        
        if ((!msg && !file) || isSending) return;
        
        // CRITICAL: Abort any in-flight poll request and stop polling
        stopPoll();
        pollVersion++; // Increment version to ignore any stale response
        
        isSending = true;
        chatInput.disabled = true;
        chatSend.disabled = true;
        chatAttachBtn.disabled = true;
        
        var msgToSend = msg;
        chatInput.value = '';
        
        // Prepare data
        var fetchBody;
        var fetchHeaders = {};
        
        if (file) {
            fetchBody = new FormData();
            fetchBody.append('message', msgToSend);
            if (convId) fetchBody.append('conversation_id', convId);
            fetchBody.append('attachment', file);
            // clear attachment UI but keep file ref in fetchBody
            clearAttachment();
        } else {
            fetchHeaders = {'Content-Type': 'application/json'};
            fetchBody = JSON.stringify({message: msgToSend, conversation_id: convId});
        }
        
        fetch('../api/chat_send.php', {
            method: 'POST',
            headers: fetchHeaders, // Empty for FormData (browser sets it)
            body: fetchBody
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                convId = data.conversation_id;
                
                // NUCLEAR OPTION: Rely 100% on Polling/Fetch
                // Do NOT add to UI manually.
                
                isSending = false; // Allow fetch
                loadMessages(); // Immediate fetch
                
            } else {
                chatInput.value = msgToSend;
                alert('Gagal mengirim: ' + (data.message || 'Error'));
            }
        })
        .catch(function(e) {
            console.error(e);
            chatInput.value = msgToSend;
            alert('Gagal mengirim pesan.');
        })
        .finally(function() {
            isSending = false;
            chatInput.disabled = false;
            chatSend.disabled = false;
            chatAttachBtn.disabled = false;
            chatInput.focus();
            // Resume polling after delay
            if (isOpen) {
                setTimeout(function() {
                    // Only start polling if not sending (avoid overlap)
                    if (!isSending) startPoll();
                }, 1000);
            }
        });
    }

    chatSend.onclick = sendMsg;
    chatInput.onkeypress = function(e) {
        if (e.key === 'Enter') sendMsg();
    };

    // Load messages from server with version check
    function loadMessages() {
        // Don't load while sending
        if (isSending) return;
        
        var currentVersion = pollVersion;
        
        // Cancel previous fetch if still running
        if (pollAbortController) {
            pollAbortController.abort();
        }
        pollAbortController = new AbortController();
        
        var url = '../api/chat_fetch.php?last_id=' + lastMsgId;
        if (convId) url += '&conversation_id=' + convId;
        
        fetch(url, { signal: pollAbortController.signal })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // CRITICAL: Block response if send started during fetch
            if (isSending) {
                console.log('Ignoring poll response - send in progress');
                return;
            }
            
            // Ignore response if version changed (means send happened)
            if (currentVersion !== pollVersion) {
                console.log('Ignoring stale poll response');
                return;
            }
            
            if (data.success) {
                if (data.conversation_id) convId = data.conversation_id;
                
                if (data.messages && data.messages.length > 0) {
                    hideEmptyState();
                    
                    for (var i = 0; i < data.messages.length; i++) {
                        var m = data.messages[i];
                        var mid = parseInt(m.id);
                        
                        // Skip if already displayed
                        if (displayedMsgIds[mid] === true) continue;
                        
                        // Mark as displayed BEFORE adding to UI
                        displayedMsgIds[mid] = true;
                        
                        // Update lastMsgId
                        if (mid > lastMsgId) {
                            lastMsgId = mid;
                        }
                        
                        var senderName = m.sender_role === 'customer' ? 'Anda' : (m.sender_name || 'Admin');
                        // Use raw attachment path from DB. Client logic will adjust if needed.
                        addMsgUI(m.message, m.sender_role, m.created_at, mid, senderName, m.attachment, m.attachment_type);
                    }
                }
                
                if (!isOpen && data.unread_count > 0) {
                    showBadge(data.unread_count);
                }
            }
        })
        .catch(function(e) {
            // Ignore abort errors
            if (e.name !== 'AbortError') {
                console.error(e);
            }
        });
    }

    // Add message to UI - with aggressive duplicate prevention
    function addMsgUI(text, role, time, msgId, senderName, attachment, attachmentType) {
        // SAFETY NET 1: DOM check by ID
        if (msgId && chatMessages.querySelector('[data-msg-id="' + msgId + '"]')) {
            console.log('DOM duplicate blocked for msgId:', msgId);
            return;
        }
        
        // SAFETY NET 2: If we have an ID, remove any orphan messages with same text but no ID
        // Skip if attachment only
        if (msgId && text) {
            var existingMsgs = chatMessages.querySelectorAll('.chat-message:not([data-msg-id])');
            for (var i = 0; i < existingMsgs.length; i++) {
                var msgText = existingMsgs[i].textContent || '';
                // Check if text matches
                if (msgText.indexOf(text) !== -1) {
                    existingMsgs[i].remove();
                }
            }
        }
        
        // SAFETY NET 3: Prevent duplicate text for customer messages
        // Normalize role
        var normalizedRole = String(role || '').toLowerCase().trim();
        
        if (normalizedRole === 'customer') {
            var allMsgs = chatMessages.querySelectorAll('.chat-message.sent');
            for (var j = 0; j < allMsgs.length; j++) {
                var existingText = allMsgs[j].textContent || '';
                if (text && existingText.indexOf(text) !== -1) {
                    // Only check text duplication if text is not empty and matches
                    var existingId = allMsgs[j].getAttribute('data-msg-id');
                    if (existingId && msgId && existingId === String(msgId)) return;
                    if (!existingId && msgId) {
                        allMsgs[j].setAttribute('data-msg-id', msgId);
                        return; 
                    }
                    if (!existingId && !msgId) return;
                }
            }
        }
        
        hideEmptyState();
        
        var div = document.createElement('div');
        div.className = 'chat-message ' + (normalizedRole === 'customer' ? 'sent' : 'received');
        if (msgId) {
            div.setAttribute('data-msg-id', msgId);
        }
        
        var timeStr = '';
        if (time) {
            var d = new Date(time);
            timeStr = d.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        } else {
            var now = new Date();
            timeStr = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        }
        
        var senderHtml = '';
        if (normalizedRole !== 'customer' && senderName) {
            senderHtml = '<span class="msg-sender">' + escapeHtml(senderName) + '</span>';
        }
        
        // Attachment HTML
        var attachmentHtml = '';
        if (attachment) {
            // Adjust path: If current page is in subdir (e.g. customer/), prepend ../
            // But if attachment path already starts with ../ (from API send response), keep it.
            // DB paths are usually 'assets/...'
            var displayPath = attachment;
            if (attachment.indexOf('../') === -1) {
                // Heuristic: If we are not in root, we need ../
                // Simple check: count slashed in pathname
                var depth = (window.location.pathname.match(/\//g) || []).length;
                // /distrozoneweb/index.php -> depth 2.
                // /distrozoneweb/customer/index.php -> depth 3.
                // But this depends on deployment.
                // Safer: Just try to detect if we are in admin or customer folder
                if (window.location.pathname.indexOf('/customer/') !== -1 || window.location.pathname.indexOf('/admin/') !== -1) {
                    displayPath = '../' + attachment;
                }
            }
            
            if (attachmentType === 'image') {
                attachmentHtml = '<div style="margin-bottom:5px;"><img src="' + displayPath + '" style="max-width:200px; border-radius:8px; cursor:pointer;" onclick="window.open(this.src)"></div>';
            } else if (attachmentType === 'video') {
                attachmentHtml = '<div style="margin-bottom:5px;"><video src="' + displayPath + '" controls style="max-width:200px; border-radius:8px;"></video></div>';
            } else {
                attachmentHtml = '<div style="margin-bottom:5px;"><a href="' + displayPath + '" target="_blank" style="color:inherit; text-decoration:underline;"><i class="fas fa-file"></i> Lihat File</a></div>';
            }
        }
        
        div.innerHTML = senderHtml + attachmentHtml + escapeHtml(text) + '<span class="msg-time">' + timeStr + '</span>';
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideEmptyState() {
        if (chatEmptyState) {
            chatEmptyState.style.display = 'none';
        }
    }

    function showBadge(count) {
        chatBadge.textContent = count > 9 ? '9+' : count;
        chatBadge.style.display = 'flex';
    }

    function hideBadge() {
        chatBadge.style.display = 'none';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Polling when chat is open
    function startPoll() {
        stopPoll();
        loadMessages(); // Load immediately first time
        pollTimer = setInterval(loadMessages, 2000);
    }

    function stopPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        // Abort any in-flight fetch
        if (pollAbortController) {
            pollAbortController.abort();
            pollAbortController = null;
        }
    }

    // Background polling for badge only (not when chat is open)
    function bgPoll() {
        if (!isOpen && !isSending) {
            fetch('../api/chat_fetch.php?last_id=' + lastMsgId + (convId ? '&conversation_id=' + convId : ''))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.conversation_id) {
                    convId = data.conversation_id;
                    if (data.unread_count > 0) {
                        showBadge(data.unread_count);
                    }
                }
            })
            .catch(function() {});
        }
    }

    bgPollTimer = setInterval(bgPoll, 5000);
    setTimeout(bgPoll, 1000);
});
</script>
