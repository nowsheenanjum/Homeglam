<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Home Glam Parlour</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav a:hover {
            opacity: 0.8;
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
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .conversations-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
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
            background: #f8f9fa;
        }

        .conversation-item.active {
            background: #e3f2fd;
            border-right: 4px solid #667eea;
        }

        .conversation-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .conversation-preview {
            font-size: 0.875rem;
            color: #6c757d;
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
            color: #adb5bd;
        }

        .unread-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            min-width: 1.5rem;
            text-align: center;
        }

        /* Chat Area */
        .chat-area {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .chat-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .messages-area {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #f8f9fa;
        }

        .message {
            max-width: 70%;
            padding: 0.875rem 1.25rem;
            border-radius: 18px;
            position: relative;
        }

        .message.sent {
            align-self: flex-end;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 0.25rem;
        }

        .message.received {
            align-self: flex-start;
            background: white;
            color: #2c3e50;
            border-bottom-left-radius: 0.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message-input-area {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e9ecef;
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
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 0.875rem;
            resize: none;
            min-height: 2.5rem;
            max-height: 6rem;
            transition: border-color 0.3s ease;
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .send-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .send-button:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #6c757d;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
                height: calc(100vh - 120px);
                padding: 0 1rem;
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

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            nav ul {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="header-content">
            <h1>Customer Messages</h1>
            <nav>
                <ul>
                    <li><a href="parlour_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="parlour_profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
                    <i class="fas fa-users"></i>
                    Customer Conversations
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
            <div class="chat-header" id="chat-header" style="display: none;">
                <div class="chat-title" id="chat-title"></div>
            </div>
            
            <div class="messages-area" id="messages-area">
                <div class="empty-state">
                    <i class="fas fa-comment-dots"></i>
                    <h3>Select a conversation</h3>
                    <p>Choose a customer from the sidebar to start chatting</p>
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
                    
                    if (conversations.length === 0) {
                        conversationsList.innerHTML = `
                            <div class="empty-state" style="padding: 2rem;">
                                <i class="fas fa-inbox"></i>
                                <h4 style="margin-bottom: 0.5rem;">No conversations yet</h4>
                                <p style="font-size: 0.875rem;">Customers will appear here when they message you!</p>
                            </div>
                        `;
                    } else {
                        let conversationsHtml = '';
                        
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
                                <div class="conversation-item" onclick="selectConversation(${conversation.customer_id}, '${escapeHtml(conversation.customer_name)}')">
                                    <div class="conversation-name">${escapeHtml(conversation.customer_name)}</div>
                                    <div class="conversation-preview">${escapeHtml(conversation.last_message || 'No messages yet')}</div>
                                    <div class="conversation-meta">
                                        <span class="conversation-time">${timeStr}</span>
                                        ${unreadBadge}
                                    </div>
                                </div>
                            `;
                        });
                        
                        conversationsList.innerHTML = conversationsHtml;
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

        async function selectConversation(customerId, customerName) {
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show chat interface
            document.getElementById('chat-header').style.display = 'block';
            document.getElementById('message-input-area').style.display = 'block';
            document.getElementById('chat-title').textContent = customerName;
            
            currentConversation = { id: customerId, name: customerName };
            
            // Load messages
            await loadMessages(customerId);
            
            // Mark messages as read
            markAsRead(customerId);
        }

        async function loadMessages(customerId) {
            const messagesArea = document.getElementById('messages-area');
            
            try {
                messagesArea.innerHTML = `
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading messages...
                    </div>
                `;
                
                const response = await fetch(`messages.php?action=get_messages&other_user_id=${customerId}`);
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
                            const isSent = message.sender_type === 'parlour_owner';
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

        async function markAsRead(customerId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('other_user_id', customerId);
                
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