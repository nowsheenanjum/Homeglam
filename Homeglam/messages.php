<?php
session_start();
include('db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'customer';
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// For direct page access, show the HTML interface
if (empty($action)) {
    displayMessagesInterface();
    exit;
}

// API endpoints handling
switch ($action) {
    case 'get_conversations':
        getConversations();
        break;
    case 'get_messages':
        getMessages();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'mark_read':
        markAsRead();
        break;
    case 'check_parlour_exists':
        checkParlourExists();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function displayMessagesInterface() {
    global $user_id, $user_role, $user_name;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Messages - Home Glam</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #4F46E5;
                --primary-dark: #4338CA;
                --primary-light: #6366F1;
                --secondary: #EC4899;
                --success: #10B981;
                --warning: #F59E0B;
                --danger: #EF4444;
                --light-bg: #F9FAFB;
                --light-border: #E5E7EB;
                --dark-text: #1F2937;
                --gray-text: #6B7280;
                --white: #FFFFFF;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                --radius: 8px;
                --radius-lg: 12px;
                --transition: all 0.2s ease-in-out;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background-color: var(--light-bg);
                color: var(--dark-text);
                line-height: 1.6;
                height: 100vh;
                display: flex;
                flex-direction: column;
            }

            /* Header */
            .header {
                background: var(--white);
                border-bottom: 1px solid var(--light-border);
                padding: 1rem 0;
                box-shadow: var(--shadow-sm);
            }

            .header-content {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 2rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .header-title {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .back-button {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--primary);
                text-decoration: none;
                padding: 0.5rem 1rem;
                border-radius: var(--radius);
                transition: var(--transition);
            }

            .back-button:hover {
                background: rgba(79, 70, 229, 0.1);
            }

            .header h1 {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--dark-text);
            }

            .user-info {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 1rem;
            }

            /* Main Container */
            .container {
                flex: 1;
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
                display: flex;
                gap: 2rem;
                height: calc(100vh - 80px);
            }

            /* Conversations Sidebar */
            .conversations-sidebar {
                width: 350px;
                background: var(--white);
                border-radius: var(--radius-lg);
                border: 1px solid var(--light-border);
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .sidebar-header {
                padding: 1.5rem;
                border-bottom: 1px solid var(--light-border);
            }

            .sidebar-header h2 {
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--dark-text);
                margin-bottom: 0.5rem;
            }

            .search-box {
                position: relative;
            }

            .search-input {
                width: 100%;
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                border: 1px solid var(--light-border);
                border-radius: var(--radius);
                font-size: 0.9rem;
            }

            .search-icon {
                position: absolute;
                left: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                color: var(--gray-text);
            }

            .conversations-list {
                flex: 1;
                overflow-y: auto;
                padding: 0.5rem;
            }

            .conversation-item {
                display: flex;
                align-items: center;
                padding: 1rem;
                border-radius: var(--radius);
                cursor: pointer;
                transition: var(--transition);
                margin-bottom: 0.25rem;
            }

            .conversation-item:hover {
                background: #f3f4f6;
            }

            .conversation-item.active {
                background: rgba(79, 70, 229, 0.1);
            }

            .conversation-avatar {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary-light), var(--secondary));
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 1.1rem;
                flex-shrink: 0;
                margin-right: 1rem;
            }

            .conversation-info {
                flex: 1;
                min-width: 0;
            }

            .conversation-name {
                font-weight: 600;
                margin-bottom: 0.25rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .conversation-preview {
                font-size: 0.875rem;
                color: var(--gray-text);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .conversation-meta {
                text-align: right;
                margin-left: 1rem;
            }

            .conversation-time {
                font-size: 0.75rem;
                color: var(--gray-text);
                margin-bottom: 0.5rem;
            }

            .unread-badge {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                background: var(--primary);
                color: white;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            /* Chat Container */
            .chat-container {
                flex: 1;
                background: var(--white);
                border-radius: var(--radius-lg);
                border: 1px solid var(--light-border);
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .chat-header {
                padding: 1.5rem;
                border-bottom: 1px solid var(--light-border);
                display: flex;
                align-items: center;
            }

            .chat-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary-light), var(--secondary));
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 1rem;
                margin-right: 1rem;
            }

            .chat-info h3 {
                font-weight: 600;
                margin-bottom: 0.25rem;
            }

            .chat-info p {
                font-size: 0.875rem;
                color: var(--gray-text);
            }

            .chat-messages {
                flex: 1;
                padding: 1.5rem;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .message {
                max-width: 70%;
                padding: 0.75rem 1rem;
                border-radius: var(--radius-lg);
                position: relative;
                animation: fadeIn 0.3s ease-out;
            }

            .message.sent {
                align-self: flex-end;
                background: var(--primary);
                color: white;
                border-bottom-right-radius: 4px;
            }

            .message.received {
                align-self: flex-start;
                background: #f3f4f6;
                color: var(--dark-text);
                border-bottom-left-radius: 4px;
            }

            .message-time {
                font-size: 0.75rem;
                margin-top: 0.5rem;
                opacity: 0.8;
                text-align: right;
            }

            .chat-input-container {
                padding: 1.5rem;
                border-top: 1px solid var(--light-border);
            }

            .chat-input-form {
                display: flex;
                gap: 0.75rem;
            }

            .message-input {
                flex: 1;
                padding: 0.75rem 1rem;
                border: 1px solid var(--light-border);
                border-radius: var(--radius);
                resize: none;
                font-family: inherit;
                font-size: 0.95rem;
                line-height: 1.5;
            }

            .message-input:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            }

            .send-button {
                padding: 0.75rem 1.5rem;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: var(--radius);
                font-weight: 600;
                cursor: pointer;
                transition: var(--transition);
                align-self: flex-end;
            }

            .send-button:hover {
                background: var(--primary-dark);
            }

            /* Empty State */
            .empty-state {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                text-align: center;
                padding: 2rem;
                color: var(--gray-text);
            }

            .empty-state i {
                font-size: 3rem;
                margin-bottom: 1rem;
                color: #d1d5db;
            }

            .empty-state h3 {
                font-size: 1.25rem;
                margin-bottom: 0.5rem;
                color: var(--dark-text);
            }

            /* Loading State */
            .loading {
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 2rem;
            }

            .spinner {
                width: 2rem;
                height: 2rem;
                border: 2px solid #e5e7eb;
                border-top: 2px solid var(--primary);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            /* Responsive Design */
            @media (max-width: 968px) {
                .container {
                    flex-direction: column;
                    padding: 1rem;
                    height: auto;
                }

                .conversations-sidebar {
                    width: 100%;
                    height: 300px;
                }

                .chat-container {
                    height: 500px;
                }

                .header-content {
                    padding: 0 1rem;
                }
            }

            @media (max-width: 640px) {
                .header-content {
                    flex-direction: column;
                    gap: 1rem;
                    text-align: center;
                }

                .header-title {
                    flex-direction: column;
                    align-items: center;
                    gap: 1rem;
                }

                .user-info {
                    display: none;
                }

                .message {
                    max-width: 85%;
                }
            }
        </style>
    </head>
    <body>
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <a href="<?php echo $user_role === 'customer' ? 'customer_dashboard.php' : 'parlour_dashboard.php'; ?>" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <h1>Messages</h1>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Container -->
        <div class="container">
            <!-- Conversations Sidebar -->
            <div class="conversations-sidebar">
                <div class="sidebar-header">
                    <h2>Conversations</h2>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search conversations...">
                    </div>
                </div>
                <div class="conversations-list" id="conversationsList">
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="chat-container">
                <div class="empty-state" id="emptyState">
                    <i class="fas fa-comments"></i>
                    <h3>No conversation selected</h3>
                    <p>Select a conversation from the sidebar to start messaging</p>
                </div>

                <div class="chat-header" id="chatHeader" style="display: none;">
                    <div class="chat-avatar" id="chatAvatar">U</div>
                    <div class="chat-info">
                        <h3 id="chatName">User Name</h3>
                        <p id="chatStatus">Online</p>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages" style="display: none;"></div>

                <div class="chat-input-container" id="chatInput" style="display: none;">
                    <form class="chat-input-form" id="messageForm">
                        <input type="hidden" id="currentConversationId">
                        <textarea 
                            class="message-input" 
                            id="messageInput" 
                            placeholder="Type your message..." 
                            rows="1"
                        ></textarea>
                        <button type="submit" class="send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // DOM elements
                const conversationsList = document.getElementById('conversationsList');
                const emptyState = document.getElementById('emptyState');
                const chatHeader = document.getElementById('chatHeader');
                const chatMessages = document.getElementById('chatMessages');
                const chatInput = document.getElementById('chatInput');
                const messageForm = document.getElementById('messageForm');
                const messageInput = document.getElementById('messageInput');
                const currentConversationId = document.getElementById('currentConversationId');
                const chatAvatar = document.getElementById('chatAvatar');
                const chatName = document.getElementById('chatName');
                const chatStatus = document.getElementById('chatStatus');
                const searchInput = document.querySelector('.search-input');

                // State variables
                let conversations = [];
                let currentConversation = null;
                let pollInterval = null;

                // Load conversations
                loadConversations();

                // Event listeners
                messageForm.addEventListener('submit', handleMessageSubmit);
                searchInput.addEventListener('input', filterConversations);

                // Auto-resize textarea
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });

                // Load conversations from server
                function loadConversations() {
                    fetch('messages.php?action=get_conversations')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                conversations = data.conversations;
                                renderConversations(conversations);
                                
                                // Start polling for new messages
                                if (!pollInterval) {
                                    pollInterval = setInterval(loadConversations, 5000);
                                }
                            } else {
                                showError('Failed to load conversations');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showError('Network error loading conversations');
                        });
                }

                // Render conversations list
                function renderConversations(conversations) {
                    if (conversations.length === 0) {
                        conversationsList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <h3>No conversations yet</h3>
                                <p>Start a conversation with a ${'<?php echo $user_role === 'customer' ? 'parlour' : 'customer'; ?>'}</p>
                            </div>
                        `;
                        return;
                    }

                    conversationsList.innerHTML = conversations.map(conversation => {
                        const name = conversation.<?php echo $user_role === 'customer' ? 'parlour_name' : 'customer_name'; ?>;
                        const lastMessage = conversation.last_message || 'No messages yet';
                        const lastMessageTime = conversation.last_message_time ? formatTime(conversation.last_message_time) : '';
                        const unreadCount = conversation.unread_count || 0;
                        const id = conversation.<?php echo $user_role === 'customer' ? 'parlour_id' : 'customer_id'; ?>;
                        
                        return `
                            <div class="conversation-item ${currentConversation?.id === id ? 'active' : ''}" data-id="${id}" data-name="${name}">
                                <div class="conversation-avatar">${name.charAt(0).toUpperCase()}</div>
                                <div class="conversation-info">
                                    <div class="conversation-name">${name}</div>
                                    <div class="conversation-preview">${lastMessage}</div>
                                </div>
                                <div class="conversation-meta">
                                    <div class="conversation-time">${lastMessageTime}</div>
                                    ${unreadCount > 0 ? `<div class="unread-badge">${unreadCount}</div>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');

                    // Add click event listeners
                    document.querySelectorAll('.conversation-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const id = item.getAttribute('data-id');
                            const name = item.getAttribute('data-name');
                            selectConversation(id, name);
                        });
                    });
                }

                // Select a conversation
                function selectConversation(id, name) {
                    currentConversation = { id, name };
                    currentConversationId.value = id;
                    
                    // Update UI
                    document.querySelectorAll('.conversation-item').forEach(item => {
                        item.classList.remove('active');
                        if (item.getAttribute('data-id') === id) {
                            item.classList.add('active');
                        }
                    });
                    
                    chatAvatar.textContent = name.charAt(0).toUpperCase();
                    chatName.textContent = name;
                    
                    // Show chat interface
                    emptyState.style.display = 'none';
                    chatHeader.style.display = 'flex';
                    chatMessages.style.display = 'flex';
                    chatInput.style.display = 'block';
                    
                    // Load messages
                    loadMessages(id);
                    
                    // Mark as read
                    markAsRead(id);
                }

                // Load messages for a conversation
                function loadMessages(otherUserId) {
                    chatMessages.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
                    
                    fetch(`messages.php?action=get_messages&other_user_id=${otherUserId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                renderMessages(data.messages);
                                scrollToBottom();
                            } else {
                                showError('Failed to load messages');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showError('Network error loading messages');
                        });
                }

                // Render messages
                function renderMessages(messages) {
                    if (messages.length === 0) {
                        chatMessages.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-comment"></i>
                                <h3>No messages yet</h3>
                                <p>Start the conversation by sending a message</p>
                            </div>
                        `;
                        return;
                    }

                    chatMessages.innerHTML = messages.map(message => {
                        const isSent = message.sender_type === '<?php echo $user_role; ?>';
                        const time = formatTime(message.created_at);
                        
                        return `
                            <div class="message ${isSent ? 'sent' : 'received'}">
                                <div class="message-content">${message.message}</div>
                                <div class="message-time">${time}</div>
                            </div>
                        `;
                    }).join('');
                }

                // Handle message submission
                function handleMessageSubmit(e) {
                    e.preventDefault();
                    
                    const message = messageInput.value.trim();
                    const otherUserId = currentConversationId.value;
                    
                    if (!message || !otherUserId) return;
                    
                    // Add message to UI immediately for better UX
                    const tempId = Date.now();
                    const time = formatTime(new Date().toISOString());
                    
                    chatMessages.innerHTML += `
                        <div class="message sent" data-temp="${tempId}">
                            <div class="message-content">${message}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;
                    
                    scrollToBottom();
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    
                    // Send to server
                    const formData = new FormData();
                    formData.append('other_user_id', otherUserId);
                    formData.append('message', message);
                    formData.append('action', 'send_message');
                    
                    fetch('messages.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            showError('Failed to send message');
                            // Remove the temporary message if sending failed
                            document.querySelector(`[data-temp="${tempId}"]`).remove();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Network error sending message');
                        document.querySelector(`[data-temp="${tempId}"]`).remove();
                    });
                }

                // Mark messages as read
                function markAsRead(otherUserId) {
                    const formData = new FormData();
                    formData.append('other_user_id', otherUserId);
                    formData.append('action', 'mark_read');
                    
                    fetch('messages.php', {
                        method: 'POST',
                        body: formData
                    });
                }

                // Filter conversations
                function filterConversations() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const filtered = conversations.filter(conversation => {
                        const name = conversation.<?php echo $user_role === 'customer' ? 'parlour_name' : 'customer_name'; ?>;
                        return name.toLowerCase().includes(searchTerm);
                    });
                    renderConversations(filtered);
                }

                // Scroll to bottom of messages
                function scrollToBottom() {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                // Format time
                function formatTime(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const diff = now - date;
                    const diffMinutes = Math.floor(diff / 60000);
                    const diffHours = Math.floor(diff / 3600000);
                    const diffDays = Math.floor(diff / 86400000);
                    
                    if (diffMinutes < 1) return 'Just now';
                    if (diffMinutes < 60) return `${diffMinutes}m ago`;
                    if (diffHours < 24) return `${diffHours}h ago`;
                    if (diffDays < 7) return `${diffDays}d ago`;
                    
                    return date.toLocaleDateString();
                }

                // Show error message
                function showError(message) {
                    // Create a simple toast notification
                    const toast = document.createElement('div');
                    toast.style.cssText = `
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: var(--danger);
                        color: white;
                        padding: 0.75rem 1.5rem;
                        border-radius: var(--radius);
                        box-shadow: var(--shadow-md);
                        z-index: 1000;
                    `;
                    toast.textContent = message;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 3000);
                }
            });
        </script>
    </body>
    </html>
    <?php
}

// The rest of the functions remain exactly the same as in the original code
function getConversations() {
    global $conn, $user_id, $user_role;
    
    if ($user_role == 'customer') {
        // For customers: get their conversations with parlours
        $sql = "SELECT DISTINCT 
                    m.parlour_id,
                    p.name as parlour_name,
                    (SELECT message FROM messages WHERE customer_id = '$user_id' AND parlour_id = m.parlour_id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE customer_id = '$user_id' AND parlour_id = m.parlour_id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages WHERE customer_id = '$user_id' AND parlour_id = m.parlour_id AND sender_type = 'parlour_owner' AND is_read = FALSE) as unread_count
                FROM messages m
                JOIN parlours p ON m.parlour_id = p.owner_id
                WHERE m.customer_id = '$user_id'
                ORDER BY last_message_time DESC";
    } else {
        // For parlour owners: get their conversations with customers
        $sql = "SELECT DISTINCT 
                    m.customer_id,
                    u.name as customer_name,
                    (SELECT message FROM messages WHERE customer_id = m.customer_id AND parlour_id = '$user_id' ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE customer_id = m.customer_id AND parlour_id = '$user_id' ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages WHERE customer_id = m.customer_id AND parlour_id = '$user_id' AND sender_type = 'customer' AND is_read = FALSE) as unread_count
                FROM messages m
                JOIN users u ON m.customer_id = u.id
                WHERE m.parlour_id = '$user_id'
                ORDER BY last_message_time DESC";
    }
    
    $result = mysqli_query($conn, $sql);
    $conversations = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $conversations[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

function getMessages() {
    global $conn, $user_id, $user_role;
    
    $other_user_id = mysqli_real_escape_string($conn, $_GET['other_user_id'] ?? '');
    
    if (!$other_user_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Other user ID required']);
        return;
    }
    
    if ($user_role == 'customer') {
        $sql = "SELECT m.*, u.name as sender_name 
                FROM messages m
                JOIN users u ON (CASE WHEN m.sender_type = 'customer' THEN m.customer_id ELSE m.parlour_id END) = u.id
                WHERE m.customer_id = '$user_id' AND m.parlour_id = '$other_user_id'
                ORDER BY m.created_at ASC";
    } else {
        $sql = "SELECT m.*, u.name as sender_name 
                FROM messages m
                JOIN users u ON (CASE WHEN m.sender_type = 'customer' THEN m.customer_id ELSE m.parlour_id END) = u.id
                WHERE m.customer_id = '$other_user_id' AND m.parlour_id = '$user_id'
                ORDER BY m.created_at ASC";
    }
    
    $result = mysqli_query($conn, $sql);
    $messages = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage() {
    global $conn, $user_id, $user_role;
    
    $other_user_id = mysqli_real_escape_string($conn, $_POST['other_user_id'] ?? '');
    $message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
    
    if (!$other_user_id || !$message) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    // Validate that the other user exists and has the correct role
    if ($user_role == 'customer') {
        // Customer sending to parlour owner - verify parlour exists
        $check_sql = "SELECT COUNT(*) as count FROM parlours WHERE owner_id = '$other_user_id'";
        $check_result = mysqli_query($conn, $check_sql);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['count'] == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parlour not found']);
            return;
        }
        
        $sql = "INSERT INTO messages (customer_id, parlour_id, sender_type, message, created_at) 
                VALUES ('$user_id', '$other_user_id', 'customer', '$message', NOW())";
    } else {
        // Parlour owner sending to customer - verify customer exists
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE id = '$other_user_id' AND role = 'customer'";
        $check_result = mysqli_query($conn, $check_sql);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['count'] == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
            return;
        }
        
        $sql = "INSERT INTO messages (customer_id, parlour_id, sender_type, message, created_at) 
                VALUES ('$other_user_id', '$user_id', 'parlour_owner', '$message', NOW())";
    }
    
    if (mysqli_query($conn, $sql)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . mysqli_error($conn)]);
    }
}

function markAsRead() {
    global $conn, $user_id, $user_role;
    
    $other_user_id = mysqli_real_escape_string($conn, $_POST['other_user_id'] ?? '');
    
    if (!$other_user_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Other user ID required']);
        return;
    }
    
    if ($user_role == 'customer') {
        $sql = "UPDATE messages SET is_read = TRUE 
                WHERE customer_id = '$user_id' AND parlour_id = '$other_user_id' AND sender_type = 'parlour_owner'";
    } else {
        $sql = "UPDATE messages SET is_read = TRUE 
                WHERE customer_id = '$other_user_id' AND parlour_id = '$user_id' AND sender_type = 'customer'";
    }
    
    if (mysqli_query($conn, $sql)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
    }
}

function checkParlourExists() {
    global $conn;
    
    $parlour_id = mysqli_real_escape_string($conn, $_GET['parlour_id'] ?? '');
    
    if (!$parlour_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Parlour ID required']);
        return;
    }
    
    $sql = "SELECT p.name, u.name as owner_name 
            FROM parlours p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.owner_id = '$parlour_id'";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $parlour = mysqli_fetch_assoc($result);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'parlour' => $parlour
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Parlour not found']);
    }
}
?>