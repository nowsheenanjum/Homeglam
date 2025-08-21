<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Check if we need to start a conversation with a specific parlour
$start_with_parlour = $_GET['parlour_id'] ?? null;
$parlour_info = null;

if ($start_with_parlour) {
    // Get parlour information
    $parlour_sql = "SELECT p.*, u.name as owner_name 
                    FROM parlours p 
                    JOIN users u ON p.owner_id = u.id 
                    WHERE p.owner_id = '$start_with_parlour'";
    $parlour_result = mysqli_query($conn, $parlour_sql);
    if (mysqli_num_rows($parlour_result) > 0) {
        $parlour_info = mysqli_fetch_assoc($parlour_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Home Glam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            background: #f1f5f9;
            color: #334155;
        }

        /* Messages Container */
        .messages-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            height: calc(100vh - 140px);
        }

        /* Conversations Sidebar */
        .conversations-sidebar {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .conversations-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s;
        }

        .conversation-item:hover {
            background: #f8fafc;
        }

        .conversation-item.active {
            background: #eff6ff;
            border-right: 3px solid #2563eb;
        }

        .conversation-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .conversation-preview {
            font-size: 0.875rem;
            color: #64748b;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .unread-badge {
            background: #dc2626;
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            min-width: 1.5rem;
            text-align: center;
        }

        /* Chat Area */
        .chat-area {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .chat-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
        }

        .messages-area {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 70%;
            padding: 0.875rem 1.25rem;
            border-radius: 1rem;
            position: relative;
        }

        .message.sent {
            align-self: flex-end;
            background: #2563eb;
            color: white;
            border-bottom-right-radius: 0.25rem;
        }

        .message.received {
            align-self: flex-start;
            background: #f1f5f9;
            color: #1e293b;
            border-bottom-left-radius: 0.25rem;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message-input-area {
            padding: 1.5rem 2rem;
            border-top: 1px solid #f1f5f9;
            background: #ffffff;
        }

        .message-input-form {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 1.5rem;
            font-size: 0.875rem;
            resize: none;
            min-height: 2.5rem;
            max-height: 6rem;
        }

        .message-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .send-button {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-button:hover {
            background: #1d4ed8;
        }

        .send-button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #64748b;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }

        .empty-state .btn {
            margin-top: 1rem;
            background: #2563eb;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .empty-state .btn:hover {
            background: #1d4ed8;
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #64748b;
        }

        /* New conversation indicator */
        .new-conversation-banner {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
                height: calc(100vh - 120px);
            }

            .conversations-sidebar {
                display: none;
            }

            .conversations-sidebar.mobile-show {
                display: flex;
                position: fixed;
                top: 80px;
                left: 1rem;
                right: 1rem;
                bottom: 1rem;
                z-index: 200;
            }
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header class="header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-logo">HG</div>
                <h1>Messages</h1>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="customer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="booking.php"><i class="fas fa-calendar"></i> Bookings</a></li>
                    <li><a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Messages Container -->
    <div class="messages-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h3>
                    <i class="fas fa-comments"></i>
                    Conversations
                </h3>
            </div>
            <div class="conversations-list" id="conversations-list">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading conversations...
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($parlour_info): ?>
                <div class="new-conversation-banner">
                    <i class="fas fa-plus-circle"></i>
                    Starting new conversation with <?php echo htmlspecialchars($parlour_info['name']); ?>
                </div>
            <?php endif; ?>
            
            <div class="chat-header" id="chat-header" style="display: none;">
                <div class="chat-title" id="chat-title"></div>
            </div>
            
            <div class="messages-area" id="messages-area">
                <div class="empty-state">
                    <i class="fas fa-comment-dots"></i>
                    <h3>Select a conversation</h3>
                    <p>Choose a parlour from the sidebar to start chatting</p>
                </div>
            </div>
            
            <div class="message-input-area" id="message-input-area" style="display: none;">
                <form class="message-input-form" id="message-form">
                    <textarea 
                        class="message-input" 
                        id="message-input" 
                        placeholder="Type your message..."
                        rows="1"
                    ></textarea>
                    <button type="submit" class="send-button" id="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentConversation = null;
        let conversations = [];
        const startWithParlour = <?php echo $start_with_parlour ? json_encode(['id' => $start_with_parlour, 'name' => $parlour_info['name']]) : 'null'; ?>;

        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadConversations();
            
            // Auto-resize textarea
            const messageInput = document.getElementById('message-input');
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 96) + 'px';
            });

            // Handle form submission
            document.getElementById('message-form').addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });

            // Handle Enter key (Shift+Enter for new line)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });

        async function loadConversations() {
            const conversationsList = document.getElementById('conversations-list');
            
            try {
                const response = await fetch('messages.php?action=get_conversations');
                const result = await response.json();
                
                if (result.success) {
                    conversations = result.conversations;
                    
                    let conversationsHtml = '';
                    
                    // If we're starting with a specific parlour and it's not in existing conversations
                    if (startWithParlour) {
                        const existingConversation = conversations.find(c => c.parlour_id == startWithParlour.id);
                        
                        if (!existingConversation) {
                            // Add the new conversation to the top
                            conversationsHtml += `
                                <div class="conversation-item" onclick="selectConversation(${startWithParlour.id}, '${escapeHtml(startWithParlour.name)}')">
                                    <div class="conversation-name">${escapeHtml(startWithParlour.name)}</div>
                                    <div class="conversation-preview">Start a new conversation...</div>
                                    <div class="conversation-meta">
                                        <span class="conversation-time">New</span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                    
                    if (conversations.length === 0 && !startWithParlour) {
                        conversationsList.innerHTML = `
                            <div class="empty-state" style="padding: 2rem;">
                                <i class="fas fa-inbox"></i>
                                <h4 style="margin-bottom: 0.5rem;">No conversations yet</h4>
                                <p style="font-size: 0.875rem;">Start a conversation by booking a service!</p>
                                <button class="btn" onclick="window.location.href='customer_dashboard.php'">
                                    Find Parlours
                                </button>
                            </div>
                        `;
                    } else {
                        conversations.forEach(conversation => {
                            const unreadBadge = conversation.unread_count > 0 
                                ? `<span class="unread-badge">${conversation.unread_count}</span>` 
                                : '';
                            
                            const timeStr = conversation.last_message_time 
                                ? new Date(conversation.last_message_time).toLocaleString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })
                                : '';
                            
                            conversationsHtml += `
                                <div class="conversation-item" onclick="selectConversation(${conversation.parlour_id}, '${escapeHtml(conversation.parlour_name)}')">
                                    <div class="conversation-name">${escapeHtml(conversation.parlour_name)}</div>
                                    <div class="conversation-preview">${escapeHtml(conversation.last_message || 'No messages yet')}</div>
                                    <div class="conversation-meta">
                                        <span class="conversation-time">${timeStr}</span>
                                        ${unreadBadge}
                                    </div>
                                </div>
                            `;
                        });
                        
                        conversationsList.innerHTML = conversationsHtml;
                        
                        // Auto-select the conversation if we're starting with a specific parlour
                        if (startWithParlour) {
                            setTimeout(() => {
                                selectConversation(startWithParlour.id, startWithParlour.name);
                            }, 100);
                        }
                    }
                } else {
                    conversationsList.innerHTML = `
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h4>Error loading conversations</h4>
                            <p style="font-size: 0.875rem;">Please try refreshing the page</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
                conversationsList.innerHTML = `
                    <div class="empty-state" style="padding: 2rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Error loading conversations</h4>
                        <p style="font-size: 0.875rem;">Please try refreshing the page</p>
                    </div>
                `;
            }
        }

        async function selectConversation(parlourId, parlourName) {
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Find and mark the clicked item as active
            const conversationItems = document.querySelectorAll('.conversation-item');
            conversationItems.forEach(item => {
                if (item.onclick && item.onclick.toString().includes(parlourId)) {
                    item.classList.add('active');
                }
            });
            
            // Show chat interface
            document.getElementById('chat-header').style.display = 'block';
            document.getElementById('message-input-area').style.display = 'block';
            document.getElementById('chat-title').textContent = parlourName;
            
            currentConversation = { id: parlourId, name: parlourName };
            
            // Load messages
            await loadMessages(parlourId);
            
            // Mark messages as read
            markAsRead(parlourId);
            
            // Remove the new conversation banner if it exists
            const banner = document.querySelector('.new-conversation-banner');
            if (banner) {
                banner.remove();
            }
        }

        async function loadMessages(parlourId) {
            const messagesArea = document.getElementById('messages-area');
            
            try {
                messagesArea.innerHTML = `
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading messages...
                    </div>
                `;
                
                const response = await fetch(`messages.php?action=get_messages&other_user_id=${parlourId}`);
                const result = await response.json();
                
                if (result.success) {
                    if (result.messages.length === 0) {
                        messagesArea.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-comment-dots"></i>
                                <h3>Start the conversation</h3>
                                <p>Send your first message to ${escapeHtml(currentConversation.name)}!</p>
                            </div>
                        `;
                    } else {
                        let messagesHtml = '';
                        
                        result.messages.forEach(message => {
                            const isSent = message.sender_type === 'customer';
                            const messageClass = isSent ? 'sent' : 'received';
                            const timeStr = new Date(message.created_at).toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            messagesHtml += `
                                <div class="message ${messageClass}">
                                    <div>${escapeHtml(message.message)}</div>
                                    <div class="message-time">${timeStr}</div>
                                </div>
                            `;
                        });
                        
                        messagesArea.innerHTML = messagesHtml;
                        
                        // Scroll to bottom
                        messagesArea.scrollTop = messagesArea.scrollHeight;
                    }
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                messagesArea.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error loading messages</h3>
                        <p>Please try again</p>
                    </div>
                `;
            }
        }

        async function sendMessage() {
            if (!currentConversation) return;
            
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            const sendButton = document.getElementById('send-button');
            sendButton.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('other_user_id', currentConversation.id);
                formData.append('message', message);
                
                const response = await fetch('messages.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    
                    // Reload messages
                    await loadMessages(currentConversation.id);
                    
                    // Reload conversations to update last message
                    loadConversations();
                } else {
                    alert('Failed to send message. Please try again.');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            } finally {
                sendButton.disabled = false;
            }
        }

        async function markAsRead(parlourId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('other_user_id', parlourId);
                
                await fetch('messages.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</body>
</html>