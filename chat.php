<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Chat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; height: 100vh; background: #f0f2f5; }
        .container { display: flex; width: 100%; max-width: 1200px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .sidebar { width: 30%; background: white; border-right: 1px solid #ddd; }
        .chat-area { width: 70%; background: url('https://i.imgur.com/8zJ1Q3B.png'); display: flex; flex-direction: column; }
        .sidebar-header { padding: 15px; background: #25D366; color: white; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header h2 { font-size: 18px; }
        .logout-btn { background: none; border: none; color: white; cursor: pointer; font-size: 16px; }
        .tabs { display: flex; }
        .tab { flex: 1; text-align: center; padding: 10px; background: #eee; cursor: pointer; }
        .tab.active { background: white; border-bottom: 2px solid #25D366; }
        .chat-list, .contact-list { overflow-y: auto; height: calc(100vh - 100px); }
        .chat-item, .contact-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; align-items: center; }
        .chat-item:hover, .contact-item:hover { background: #f5f5f5; }
        .chat-item img, .contact-item img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
        .chat-item div, .contact-item div { flex: 1; }
        .chat-item h3, .contact-item h3 { font-size: 16px; color: #333; }
        .chat-item p { font-size: 14px; color: #666; }
        .chat-header { padding: 15px; background: #25D366; color: white; display: flex; align-items: center; }
        .chat-header img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
        .chat-header h2 { font-size: 18px; }
        .messages { flex: 1; overflow-y: auto; padding: 20px; }
        .date-divider { text-align: center; margin: 10px 0; color: #666; font-size: 12px; }
        .message { max-width: 60%; margin-bottom: 10px; padding: 10px; border-radius: 10px; position: relative; }
        .message.sent { background: #DCF8C6; margin-left: auto; }
        .message.received { background: white; }
        .message p { font-size: 14px; }
        .message span { font-size: 12px; color: #666; position: absolute; bottom: 5px; right: 10px; }
        .message.sent span { color: #34B7F1; }
        .message-input { padding: 15px; background: white; display: flex; align-items: center; border-top: 1px solid #ddd; }
        .message-input input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; margin-right: 10px; }
        .message-input button { background: #25D366; border: none; color: white; padding: 10px 20px; border-radius: 20px; cursor: pointer; }
        .message-input button:hover { background: #128C7E; }
        .no-chat { display: flex; justify-content: center; align-items: center; height: 100%; color: #666; }
        @media (max-width: 800px) {
            .container { flex-direction: column; }
            .sidebar, .chat-area { width: 100%; }
            .sidebar { height: 50vh; }
            .chat-area { height: 50vh; }
        }
        @media (max-width: 600px) {
            .chat-item h3, .contact-item h3, .chat-header h2 { font-size: 14px; }
            .message p { font-size: 12px; }
            .message-input input { padding: 8px; }
            .message-input button { padding: 8px 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>WhatsApp Clone</h2>
                <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
            </div>
            <div class="tabs">
                <div class="tab active" onclick="showChats()">Chats</div>
                <div class="tab" onclick="showContacts()">Contacts</div>
            </div>
            <div class="chat-list" id="chat-list"></div>
            <div class="contact-list" id="contact-list" style="display: none;"></div>
        </div>
        <div class="chat-area" id="chat-area" style="display: none;">
            <div class="chat-header" id="chat-header"></div>
            <div class="messages" id="messages"></div>
            <div class="message-input">
                <input type="text" id="message-input" placeholder="Type a message">
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
        <div class="no-chat">Select a chat or contact to start messaging</div>
    </div>
    <script>
        let currentChatId = null;
        let currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let lastDate = null;

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);

            if (date.toDateString() === today.toDateString()) return 'Today';
            if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function showChats() {
            document.getElementById('chat-list').style.display = 'block';
            document.getElementById('contact-list').style.display = 'none';
            document.querySelectorAll('.tab')[0].classList.add('active');
            document.querySelectorAll('.tab')[1].classList.remove('active');
            fetchChats();
        }

        function showContacts() {
            document.getElementById('chat-list').style.display = 'none';
            document.getElementById('contact-list').style.display = 'block';
            document.querySelectorAll('.tab')[0].classList.remove('active');
            document.querySelectorAll('.tab')[1].classList.add('active');
            fetchContacts();
        }

        function fetchChats() {
            fetch('fetch_chats.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const chatList = document.getElementById('chat-list');
                    chatList.innerHTML = '';
                    if (data.length === 0) {
                        chatList.innerHTML = '<p>No chats available</p>';
                    }
                    data.forEach(chat => {
                        const chatItem = document.createElement('div');
                        chatItem.className = 'chat-item';
                        chatItem.innerHTML = `
                            <img src="${chat.profile_picture || 'default.jpg'}" alt="Profile">
                            <div>
                                <h3>${chat.username}</h3>
                                <p>${chat.last_message || 'No messages yet'}</p>
                            </div>
                        `;
                        chatItem.onclick = () => loadChat(chat.chat_id, chat.user_id, chat.username);
                        chatList.appendChild(chatItem);
                    });
                })
                .catch(error => console.error('Error fetching chats:', error));
        }

        function fetchContacts() {
            fetch('fetch_contacts.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const contactList = document.getElementById('contact-list');
                    contactList.innerHTML = '';
                    if (data.length === 0) {
                        contactList.innerHTML = '<p>No contacts available</p>';
                    }
                    data.forEach(contact => {
                        const contactItem = document.createElement('div');
                        contactItem.className = 'contact-item';
                        contactItem.innerHTML = `
                            <img src="${contact.profile_picture || 'default.jpg'}" alt="Profile">
                            <div>
                                <h3>${contact.username}</h3>
                            </div>
                        `;
                        contactItem.onclick = () => startChat(contact.id, contact.username);
                        contactList.appendChild(contactItem);
                    });
                })
                .catch(error => console.error('Error fetching contacts:', error));
        }

        function startChat(userId, username) {
            fetch('start_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.chat_id) {
                        loadChat(data.chat_id, userId, username);
                        showChats();
                    }
                })
                .catch(error => console.error('Error starting chat:', error));
        }

        function loadChat(chatId, userId, username) {
            currentChatId = chatId;
            document.getElementById('chat-area').style.display = 'flex';
            document.querySelector('.no-chat').style.display = 'none';
            document.getElementById('chat-header').innerHTML = `
                <img src="default.jpg" alt="Profile">
                <h2>${username}</h2>
            `;
            fetchMessages();
        }

        function fetchMessages() {
            if (!currentChatId) {
                document.getElementById('messages').innerHTML = '<div class="no-chat">Select a chat to start messaging</div>';
                return;
            }
            fetch(`fetch_messages.php?chat_id=${currentChatId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const messagesDiv = document.getElementById('messages');
                    messagesDiv.innerHTML = '';
                    lastDate = null;
                    data.forEach(msg => {
                        const msgDate = formatDate(msg.sent_at);
                        if (msgDate !== lastDate) {
                            const dateDivider = document.createElement('div');
                            dateDivider.className = 'date-divider';
                            dateDivider.textContent = msgDate;
                            messagesDiv.appendChild(dateDivider);
                            lastDate = msgDate;
                        }
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
                        messageDiv.innerHTML = `
                            <p>${msg.message}</p>
                            <span>${new Date(msg.sent_at).toLocaleTimeString()} ${msg.is_read && msg.sender_id == currentUserId ? '✓✓' : '✓'}</span>
                        `;
                        messagesDiv.appendChild(messageDiv);
                    });
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            if (!message || !currentChatId) return;

            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `chat_id=${currentChatId}&message=${encodeURIComponent(message)}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    input.value = '';
                    fetchMessages();
                    fetchChats();
                })
                .catch(error => console.error('Error sending message:', error));
        }

        document.getElementById('message-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        setInterval(fetchMessages, 2000);
        setInterval(fetchChats, 5000);
        fetchChats();
    </script>
</body>
</html>
