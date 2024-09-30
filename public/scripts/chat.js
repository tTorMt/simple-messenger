let activeChatId;

createChatInit();
chatListReloadInit();
addUserToChatInit();
chooseChatInit();
setWindowMode();
changeModeInit();

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
 * Changes the compact/full mode on the window resizing and clicking the back button
 */
function changeModeInit() {
    window.addEventListener('resize', setWindowMode);
    let backBtn = document.getElementById('back');
    backBtn.addEventListener('click', event => {
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

    createBtn.addEventListener('click', (event) => {
        event.preventDefault();
        toggleResultChatField();
        createBtn.setAttribute('disabled', '');
        newChat(chatName.value).then(result => {
            if (Object.hasOwn(result, 'Error')) {
                createBtn.removeAttribute('disabled');
                toggleResultChatField('error', result.Error);
                return;
            }
            toggleResultChatField('success');
            chatListReload();
            createBtn.removeAttribute('disabled');
        });
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
function chatListReload() {
    let chatList = document.querySelectorAll('#app aside .chat-node');
    let chatListNode = document.getElementById('chat-list');
    for (const chat of chatList) {
        chat.remove();
    }
    getChatList().then(chatList => {
        if (chatList.Error !== undefined) {
            toggleResultChatField('error', chatList.Error);
            return;
        }
        for (const chat of chatList) {
            let chatNode = createChatNode(chat.chat_name, chat.chat_id);
            chatListNode.append(chatNode);
        }
    });
}

/**
 * Sets an active chat ID
 */
function chooseChatInit() {
    let chatListNode = document.getElementById('chat-list');

    chatListNode.addEventListener('click', event => {
        if (event.target.dataset === undefined || event.target.dataset.chatId === undefined) {
            return;
        }
        activeChatId = event.target.dataset.chatId;
        toggleMessenger(event.target.dataset.chatName);
        setWindowMode();
        startMessageUpdates();
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

    addBtn.addEventListener('click', (event) => {
        event.preventDefault();
        toggleResultMessageField();
        addBtn.setAttribute('disabled', '');
        let userName = userNameField.value;
        addUserToChat(userName, activeChatId).then(result => {
            if (result.Error !== undefined) {
                toggleResultMessageField('error', result.Error);
            } else {
                toggleResultMessageField('success');
            }
            addBtn.removeAttribute('disabled');
        });
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
    if (chatName === undefined) {
        messengerNode.setAttribute('style', 'display: none');
        return;
    }
    document.querySelector('#messenger h4').textContent = chatName;
    messengerNode.removeAttribute('style');
}

/**
 * Loads messages from server and starts the message updates
 */
function startMessageUpdates() {
    // TO DO implement messaging
}