/**
 * Authorizes the user.
 *
 * @param user
 * @param password
 * @returns {Promise<number>} response status code. 200 - for success.
 */
async function authorize(user, password) {
    let userData = new FormData();
    userData.append('userName', user);
    userData.append('userPassword', password);
    let response = await fetch('/auth', {
        method: 'POST',
        body: userData
    });
    return response.status;
}

/**
 * Registers a new user.
 * @param user
 * @param password
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: NameError PasswordError NameExists InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function register(user, password) {
    let userData = new FormData();
    userData.append('userName', user);
    userData.append('userPassword', password);
    let response = await fetch('/newUser', {
        method: 'POST',
        body: userData
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                case 400 : return { Error: 'WrongRequest'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }
        }
    }
    return {};
}

/**
 * Sets active chat to show messages to user.
 *
 * @param chatId
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: SuchChatDoesntExist HostNotInTheChat InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function setActiveChat(chatId) {
    let chatData = new FormData();
    chatData.append('chatId', chatId);
    let response = await fetch('/activeChat', {
        method: 'POST',
        body: chatData
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                case 400 : return { Error: 'WrongRequest'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }
        }
    }
    return {};
}

/**
 * Creates a new chat.
 *
 * @param chatName
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: NameError NameExists InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function newChat(chatName) {
    let chatData = new FormData();
    chatData.append('chatName', chatName);
    let response = await fetch('/newChat', {
        method: 'POST',
        body: chatData
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                case 400 : return { Error: 'WrongRequest'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }

        }
    }
    return {};
}

/**
 * Adds an existing user to an existing chat.
 *
 * @param userName
 * @param chatId
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: UserNotFound SuchChatDoesntExist HostNotInTheChat InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function addUserToChat(userName, chatId){
    let data = new FormData();
    data.append('userName', userName);
    data.append('chatId', chatId);
    let response = await fetch('/addUserToChat', {
        method: 'POST',
        body: data
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                case 400 : return { Error: 'WrongRequest'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }

        }
    }
    return {};
}

/**
 * Retrieves the chat list of the current user
 *
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: InternalServerError Unauthorized Unknown error. Response code: status code
 */
async function getChatList() {
    let response = await fetch('/chatList');
    try {
        return await response.json();
    } catch (exception) {
        switch (response.status) {
            case 500 :
                return {Error: 'InternalServerError'};
            case 401 :
                return {Error: 'Unauthorized'};
            default :
                return {Error: 'Unknown error. Response code: ' + response.status};
        }

    }
}

async function startUpdates(){

}


/**
 * Loads all messages from the chosen chat
 *
 * @returns {Promise<any|{Error: string}>}
 */
async function getMessages(){
    let response = await fetch('/loadMessages');
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                case 400 : return { Error: 'WrongRequest'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }
        }
    }
    return await response.json();
}