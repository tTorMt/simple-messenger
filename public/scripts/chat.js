createChatInit();
changePasswordInit();
chatListReloadInit();
addUserToChatInit();
sendFileInit();
chooseChatInit();
setWindowMode();
changeModeInit();
enterKeyInit();

/**
 * Changes between the compact and the full mode
 */
function setWindowMode() {
    let compactMode = window.innerWidth < 600;
    let messenger = document.getElementById('messenger');

    if (compactMode && !messenger.hasAttribute('style')) {
        hideChatList();
    }
    if (!compactMode) {
        showChatList();
    }
}

/**
 * Change password
 */
function changePasswordInit() {
    let passChangeLink = document.getElementById('pass-change-link');
    let passChangeClose = document.getElementById('change-close');
    let passChangeWindow = document.getElementById('change-pass');
    let passChangeStatus = document.getElementById('change-pass-error');
    let oldPasswordInput = document.getElementById('old-password');
    let newPasswordInput = document.getElementById('new-password');
    let retypePasswordInput = document.getElementById('retype');
    let changeBtn = document.getElementById('change-btn');

    changeBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        toggleChangeStatus();
        changeBtn.setAttribute('disabled', '');
        let oldPassword = oldPasswordInput.value;
        let newPassword = newPasswordInput.value;
        let retype = retypePasswordInput.value;
        if (retype === newPassword) {
            let result = await changePassword(newPassword, oldPassword, undefined);
            if (Object.hasOwn(result, 'Error')) {
                toggleChangeStatus('error', result.Error);
            } else {
                toggleChangeStatus('success', 'Success');
            }
        } else {
            toggleChangeStatus('error', "Password and retype password do not match.");
        }
        changeBtn.removeAttribute('disabled');
    });

    passChangeClose.addEventListener('click', (event) => {
        event.preventDefault();
        passChangeWindow.setAttribute('hidden', '');
    });

    passChangeLink.addEventListener('click', (event) => {
        event.preventDefault();
        passChangeWindow.removeAttribute('hidden');
    });

    function toggleChangeStatus(type, text) {
        if (type === undefined || text === undefined) {
            passChangeStatus.setAttribute('hidden', '');
            passChangeStatus.classList.remove('error');
            passChangeStatus.classList.remove('success');
            return;
        }
        passChangeStatus.classList.add(type);
        passChangeStatus.textContent = text;
        passChangeStatus.removeAttribute('hidden');
    }
}

/**
 * Changes the compact/full mode on the window resizing and clicking the back button
 */
function changeModeInit() {
    window.addEventListener('resize', setWindowMode);
    let backBtn = document.getElementById('back');
    backBtn.addEventListener('click', () => {
        toggleMessenger();
        showChatList();
    });
}

/**
 * Shows the chat list if it's hidden
 */
function showChatList() {
    let chatList = document.getElementById('chat-list');
    if (chatList.hasAttribute('style')) {
        chatList.removeAttribute('style');
    }
}

/**
 * Hides the chat list
 */
function hideChatList() {
    let chatList = document.getElementById('chat-list');
    chatList.setAttribute('style', 'display: none');
}

/**
 * Create a new chat form initialization
 */
function createChatInit() {
    let chatName = document.getElementById('chatName');
    let createBtn = document.getElementById('createChat');

    createBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        toggleResultChatField();
        createBtn.setAttribute('disabled', '');
        let result = await newChat(chatName.value);
        if (Object.hasOwn(result, 'Error')) {
            createBtn.removeAttribute('disabled');
            toggleResultChatField('error', result.Error);
            return;
        }
        toggleResultChatField('success');
        await chatListReload();
        createBtn.removeAttribute('disabled');
    });
}

/**
 * Reloads the chat list on the buttons click (back and reload buttons)
 */
function chatListReloadInit() {
    let reloadBtn = document.getElementById('reload-chats');
    let backBtn = document.getElementById('back');
    reloadBtn.addEventListener('click', chatListReload);
    backBtn.addEventListener('click', chatListReload);
}

/**
 * Reload chat list
 */
async function chatListReload() {
    let chatList = document.querySelectorAll('#app aside .chat-node');
    let chatListNode = document.getElementById('chat-list');
    for (const chat of chatList) {
        chat.remove();
    }
    chatList = await getChatList();
    if (chatList.Error !== undefined) {
        toggleResultChatField('error', chatList.Error);
        return;
    }
    for (const chat of chatList) {
        let chatNode = createChatNode(chat.chat_name, chat.chat_id);
        chatListNode.append(chatNode);
    }
}

/**
 * Sets an active chat ID
 */
function chooseChatInit() {
    let chatListNode = document.getElementById('chat-list');
    let webSocketServer;

    chatListNode.addEventListener('click', async event => {
        toggleResultChatField();
        if (event.target.dataset === undefined || event.target.dataset.chatId === undefined) {
            return;
        }
        let activeChatId = event.target.dataset.chatId;
        let result = await setActiveChat(activeChatId);
        if (result.Error === undefined) {
            toggleMessenger(event.target.dataset.chatName);
            await loadMessages();
            setWindowMode();
            if (!webSocketServer) {
                webSocketServer = connectToWS();
                webSocketServer.onerror = () => {
                    setMessagingActive(false);
                    toggleResultMessageField('error', 'An error occurred. Try again later');
                    webSocketServer = null;
                }
                webSocketServer.onopen = () => {
                    setMessagingActive(true);
                    toggleResultMessageField();
                    webSocketServer.send(JSON.stringify(['setMID', getLastMessageID()]));
                }
            }
            messageSendingInit(webSocketServer);
            startMessageUpdates(webSocketServer);
            return;
        }
        toggleResultChatField('error', result.Error);
    });
}

/**
 * Manipulates the chat node error field
 *
 * @param result ('error'|'success')
 * @param message ('error message')
 */
function toggleResultChatField(result, message) {
    let resultField = document.getElementById('result-chat-field');
    resultField.textContent = '';
    resultField.classList.remove('success', 'error');
    if (result === 'error') {
        resultField.removeAttribute('hidden');
        resultField.classList.add('error');
        resultField.textContent = message;
        return;
    }
    if (result === 'success') {
        resultField.removeAttribute('hidden');
        resultField.classList.add('success');
        resultField.textContent = 'Success';
        return;
    }
    resultField.setAttribute('hidden', '');
}

/**
 * Creates a chat node element for the chat list
 * @param chatName
 * @param chatId
 * @returns {HTMLDivElement}
 */
function createChatNode(chatName, chatId) {
    let chatNode = document.createElement('div');
    chatNode.classList.add('chat-node');
    let header = document.createElement('h3');
    header.textContent = chatName;
    let selectBtn = document.createElement('button');
    selectBtn.textContent = 'Open';
    selectBtn.setAttribute('data-chat-id', chatId);
    selectBtn.setAttribute('data-chat-name', chatName);
    chatNode.append(header, selectBtn);
    return chatNode;
}

/**
 * Adds a user to the active chat
 */
function addUserToChatInit() {
    let userNameField = document.getElementById('user-name');
    let addBtn = document.getElementById('add');

    addBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        toggleResultMessageField();
        addBtn.setAttribute('disabled', '');
        let userName = userNameField.value;
        let result = await addUserToChat(userName);
        if (result.Error !== undefined) {
            toggleResultMessageField('error', result.Error);
        } else {
            toggleResultMessageField('success');
        }
        addBtn.removeAttribute('disabled');
    });
}

/**
 * Sends a file message to the server
 */
function sendFileInit() {
    let fileField = document.getElementById('file');
    let fileSendBtn = document.getElementById('send-file');

    fileSendBtn.addEventListener('click', async () => {
        fileSendBtn.setAttribute('disabled', 'true');
        if (fileField.files.length === 0) {
            return;
        }
        let result = await sendFile(fileField.files[0]);
        if (result.Error !== undefined) {
            toggleResultMessageField('error', result.Error);
        } else {
            toggleResultMessageField('success');
        }
        fileSendBtn.removeAttribute('disabled');
    });
}

/**
 * Manages the error message field
 *
 * @param result
 * @param message
 */
function toggleResultMessageField(result, message) {
    let resultField = document.getElementById('result-message-field');
    resultField.textContent = '';
    if (result === 'success') {
        resultField.removeAttribute('hidden');
        resultField.classList.add('success');
        resultField.textContent = 'Success';
        return;
    }
    if (result === 'error') {
        resultField.removeAttribute('hidden');
        resultField.classList.add('error');
        resultField.textContent = message;
        return;
    }
    resultField.classList.remove('error', 'success');
    resultField.setAttribute('hidden', '');
}

/**
 * Toggles the messenger window display
 *
 * @param chatName if provided displays the messenger. Hides without arguments.
 */
function toggleMessenger(chatName) {
    let messengerNode = document.getElementById('messenger');
    let messageList = document.getElementById('message-list');
    if (messageList !== null) {
        messageList.remove();
    }
    if (chatName === undefined) {
        messengerNode.setAttribute('style', 'display: none');
        return;
    }
    messageList = document.createElement('div');
    messageList.id = 'message-list';
    (document.getElementById('actions')).insertAdjacentElement('beforebegin', messageList);
    document.querySelector('#messenger h4').textContent = chatName;
    messengerNode.removeAttribute('style');
}

/**
 * Fills the messages list then the chat starts
 */
async function loadMessages() {
    let messageList = document.getElementById('message-list');
    let messages = await getMessages();
    if (messages.Error !== undefined) {
        toggleResultMessageField(messages.Error);
        return;
    }
    for (const message of messages) {
        let messageNode = createMessageNode(message);
        messageList.append(messageNode);
    }
}

/**
 * Gets last showed message ID
 * @returns {number}
 */
function getLastMessageID() {
    let messageList = document.getElementById('message-list');
    if (!messageList) {
        return -1;
    }
    let lastMessage = messageList.lastElementChild;
    if (!lastMessage) {
        return 0;
    }
    return +(lastMessage.dataset.messageId);
}

/**
 * Loads messages from server and starts the message updates
 */
function startMessageUpdates(webSocketServer) {
    webSocketServer.onmessage = (event) => {
        let lastMessageId = getLastMessageID();
        let messages = JSON.parse(event.data);
        if (messages.length === 0) {
            return;
        }
        if (messages.Error !== undefined) {
            toggleResultMessageField('error', 'An error occurred. Try again later');
            if (messages.Error === 'MessageStoreError') {
                setMessagingActive(true);
            }
            return;
        }
        if (messages.OK !== undefined) {
            setMessagingActive(true);
            return;
        }
        if (messages[messages.length - 1].message_id <= lastMessageId) {
            webSocketServer.send(JSON.stringify(['setMID', lastMessageId]));
            return;
        }
        appendMessages(messages, lastMessageId);
        webSocketServer.send(JSON.stringify(['setMID', messages[messages.length - 1].message_id]));
    }
}

/**
 * Adds new messages to the message list
 *
 * @param messages
 * @param lastMessageId
 */
function appendMessages(messages, lastMessageId) {
    let messageList = document.getElementById('message-list');
    for (const message of messages) {
        if (message.message_id <= lastMessageId) {
            continue;
        }
        let messageNode = createMessageNode(message);
        messageList.append(messageNode);
    }
}

/**
 * Creates a message node for the message list
 */
function createMessageNode(message) {
    let messageNode = document.createElement('p');
    messageNode.setAttribute('data-message-id', message.message_id);
    messageNode.textContent = message.user_name + ' ' + message.message_date + ': ' + message.message;
    if (message.is_file == 1) {
        createFileNode(messageNode);
    }
    return messageNode;
}

/**
 * Creates a file node and appends it to message node
 * @param messageNode
 */
async function createFileNode(messageNode) {
    let messageId = messageNode.dataset.messageId;
    let fileElement = await loadFile(messageId);
    if (fileElement.Error !== undefined) {
        messageNode.append(fileElement.Error);
        return;
    }
    messageNode.append(fileElement);
}

/**
 * Activates or deactivates the messaging field.
 *
 * @param activate bool
 */
function setMessagingActive(activate) {
    let messageTextField = document.getElementById('message');
    let sendBtn = document.getElementById('send');
    if (activate) {
        sendBtn.removeAttribute('disabled');
        messageTextField.removeAttribute('disabled');
        messageTextField.focus();
        return;
    }
    sendBtn.setAttribute('disabled', '');
    messageTextField.setAttribute('disabled', '');
}

/**
 * Initialize the message sending
 *
 * @param webSocketServer
 */
function messageSendingInit(webSocketServer) {
    let messageTextField = document.getElementById('message');
    let sendBtn = document.getElementById('send');
    let sendBtnReplacer = sendBtn.cloneNode(true);
    sendBtn.parentNode.replaceChild(sendBtnReplacer, sendBtn);
    
    sendBtnReplacer.addEventListener('click', () => {
        setMessagingActive(false);
        let messageText = messageTextField.value;
        if (!messageText) {
            setMessagingActive(true);
            return;
        }
        webSocketServer.send(JSON.stringify(['message', messageText]));
    });
}

/**
 * Enter key events handler
 * @param event
 */
function onButtonPress(event) {
    let newChatInputField = document.getElementById('chatName');
    let createChatBtn = document.getElementById('createChat');
    let userNameField = document.getElementById('user-name');
    let addUserBtn = document.getElementById('add');
    let sendMessageField = document.getElementById('message');
    let sendMessageBtn = document.getElementById('send');

    if (event.code === 'NumpadEnter' || event.code === 'Enter') {
        if (document.activeElement === newChatInputField) {
            createChatBtn.click();
            return;
        }
        if (document.activeElement === userNameField) {
            addUserBtn.click();
            return;
        }
        if (document.activeElement === sendMessageField && event.ctrlKey) {
            sendMessageBtn.click();
            return;
        }
    }
}

/**
 * Initialize enter key handler
 */
function enterKeyInit() {
    window.addEventListener('keyup', onButtonPress);
}