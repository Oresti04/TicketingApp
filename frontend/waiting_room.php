<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current user info
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Waiting Room</title>
  <style>
    .container {
      display: flex;
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .chat-container {
      flex: 2;
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-right: 20px;
      display: flex;
      flex-direction: column;
      height: 500px;
    }
    
    .users-container {
      flex: 1;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      height: 500px;
    }
    
    .chat-messages {
      flex-grow: 1;
      overflow-y: auto;
      padding: 15px;
      background-color: #f9f9f9;
    }
    
    .chat-input {
      display: flex;
      padding: 10px;
      border-top: 1px solid #ddd;
    }
    
    .chat-input input {
      flex-grow: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-right: 10px;
    }
    
    .chat-input button {
      padding: 10px 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 5px;
      background-color: #fff;
      border-left: 4px solid #ddd;
    }
    
    .message .username {
      font-weight: bold;
      margin-bottom: 5px;
      color: #333;
    }
    
    .message .time {
      font-size: 0.8em;
      color: #999;
    }
    
    .online-users {
      list-style-type: none;
      padding: 0;
    }
    
    .online-users li {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }
    
    .online-users li:last-child {
      border-bottom: none;
    }
    
    .user-status {
      display: inline-block;
      width: 10px;
      height: 10px;
      background-color: #4CAF50;
      border-radius: 50%;
      margin-right: 5px;
    }
    
    .enter-app {
      display: block;
      margin-top: 20px;
      padding: 15px;
      background-color: #4CAF50;
      color: white;
      text-align: center;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <h1>Welcome to the Waiting Room, <?= htmlspecialchars($username) ?></h1>
  <p>Chat with other users while you wait. When you're ready, proceed to the dashboard.</p>
  
  <div class="container">
    <div class="chat-container">
      <div class="chat-messages" id="chatMessages">
        <!-- Messages will be loaded here via AJAX -->
      </div>
      <div class="chat-input">
        <input type="text" id="messageInput" placeholder="Type your message...">
        <button id="sendButton">Send</button>
      </div>
    </div>
    
    <div class="users-container">
      <h3>Online Users</h3>
      <ul class="online-users" id="onlineUsers">
        <!-- Online users will be loaded here via AJAX -->
      </ul>
      
      <a href="dashboard.php" class="enter-app">Enter Ticketing System</a>
    </div>
  </div>

  <script>
    // Global variables
    const userId = "<?= $user_id ?>";
    const username = "<?= htmlspecialchars($username) ?>";
    let lastMessageId = 0;
    
    // DOM elements
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const onlineUsers = document.getElementById('onlineUsers');
    
    // Function to fetch messages
    function fetchMessages() {
      fetch('../backend/chat.php?action=getMessages&last_id=' + lastMessageId)
        .then(response => response.json())
        .then(data => {
          if (data.messages && data.messages.length > 0) {
            data.messages.forEach(message => {
              appendMessage(message);
              lastMessageId = Math.max(lastMessageId, message.id);
            });
            
            // Scroll to bottom of chat
            chatMessages.scrollTop = chatMessages.scrollHeight;
          }
        })
        .catch(error => console.error('Error fetching messages:', error));
    }
    
    // Function to fetch online users
    function fetchOnlineUsers() {
      fetch('../backend/chat.php?action=getOnlineUsers')
        .then(response => response.json())
        .then(data => {
          if (data.users) {
            onlineUsers.innerHTML = '';
            data.users.forEach(user => {
              const li = document.createElement('li');
              li.innerHTML = `<span class="user-status"></span> ${user.username}`;
              onlineUsers.appendChild(li);
            });
          }
        })
        .catch(error => console.error('Error fetching users:', error));
    }
    
    // Function to append a message to the chat
    function appendMessage(message) {
      const messageDiv = document.createElement('div');
      messageDiv.className = 'message';
      
      const usernameDiv = document.createElement('div');
      usernameDiv.className = 'username';
      usernameDiv.textContent = message.username;
      
      const messageContent = document.createElement('div');
      messageContent.className = 'content';
      messageContent.textContent = message.message;
      
      const timeDiv = document.createElement('div');
      timeDiv.className = 'time';
      timeDiv.textContent = new Date(message.created_at).toLocaleTimeString();
      
      messageDiv.appendChild(usernameDiv);
      messageDiv.appendChild(messageContent);
      messageDiv.appendChild(timeDiv);
      
      chatMessages.appendChild(messageDiv);
    }
    
    // Function to send a message
    function sendMessage() {
      const message = messageInput.value.trim();
      if (message) {
        fetch('../backend/chat.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=sendMessage&message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            messageInput.value = '';
            fetchMessages();
          }
        })
        .catch(error => console.error('Error sending message:', error));
      }
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });
    
    // Update user's online status
    function updateOnlineStatus() {
      fetch('../backend/chat.php?action=updateStatus')
        .catch(error => console.error('Error updating status:', error));
    }
    
    // Initialize
    fetchMessages();
    fetchOnlineUsers();
    
    // Set up polling for new messages and users
    setInterval(fetchMessages, 2000);
    setInterval(fetchOnlineUsers, 5000);
    setInterval(updateOnlineStatus, 30000);
    
    // Update status when page loads
    updateOnlineStatus();
  </script>
</body>
</html>
