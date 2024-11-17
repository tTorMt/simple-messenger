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
 * @param email
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: NameError PasswordError NameExists EmailError InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function register(user, password, email) {
    let userData = new FormData();
    userData.append('userName', user);
    userData.append('userPassword', password);
    userData.append('userEmail', email);
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
 * Verifies a user's email
 *
 * @param token
 * @returns {Promise<{}|{Error: string}>}
 * Error types: WrongRequest, WrongToken, UnknownError
 */
async function emailVerification(token) {
    let response = await fetch('/emailVerification?token=' + token);
    if (!response.ok) {
        switch (response.status) {
            case 404 : return { Error: 'WrongRequest' };
            case 400 : return { Error: 'WrongToken' };
            default: return { Error: 'UnknownError' };
        }
    }
    return {};
}

/**
 * Sends password restoration email
 *
 * @param email
 * @returns {Promise<{}|any|{Error: string}>}
 * Error types: NoSuchEmail, InternalServerError, UnknownError
 */
async function forgotPassword(email) {
    let formData = new FormData();
    formData.append('email', email);
    let response = await fetch('/forgotPassword', {
        method: 'POST',
        body: formData
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError'};
                default : return { Error: 'UnknownError'};
            }
        }
    }
    return {};
}

/**
 * Changes the user's password using either a password token or the old password.
 *
 * @param newPassword
 * @param oldPassword
 * @param token
 * @returns {Promise<{}|any|{Error: string}>}
 * Error types: PasswordError, WrongToken, WrongUserData, InternalServerError, UnknownError
 */
async function changePassword(newPassword, oldPassword, token) {
    let passwordData = new FormData();
    passwordData.append('newPassword', newPassword);
    if (token !== undefined) {
        passwordData.append('token', token);
    }
    if (oldPassword !== undefined) {
        passwordData.append('oldPassword', oldPassword);
    }
    let response = await fetch('/changePassword', {
        method: 'POST',
        body: passwordData
    });

    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 401 : return { Error: 'WrongUserData' };
                case 500 : return { Error: 'InternalServerError' };
                default : return { Error: 'UnknownError' };
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
 * Error types: SuchChatDoesntExist HostNotInTheChat InternalServerError Unauthorized WrongRequest Unknown error. Response code: status code
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
 * Error types: NameError NameExists InternalServerError Unauthorized WrongRequest Unknown error. Response code: status code
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
 * @returns {Promise<{}|{ Error: 'error type' }>}
 * Error types: UserNotFound SuchChatDoesntExist HostNotInTheChat Unauthorized InternalServerError WrongRequest Unknown error. Response code: status code
 */
async function addUserToChat(userName){
    let data = new FormData();
    data.append('userName', userName);
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
                case 401 : return { Error: 'Unauthorized'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }
        }
    }
    return await response.json();
}

/**
 * Loads a file from file type message ID
 * @param messageID
 * @returns {Promise<HTMLAnchorElement|HTMLImageElement|any|{Error: string}>}
 */
async function loadFile(messageID) {
    let response = await fetch('/getFile?messageId=' + messageID);
    const imageTypes = ['image/jpeg', 'image/gif', 'image/png'];
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 500 : return { Error: 'InternalServerError' };
                case 400 : return { Error: 'WrongRequest' };
                default : return { Error: 'Unknown error. Response code: ' + response.status }
            }
        }
    }
    let file;
    try {
        file = await response.blob();
    } catch (exception) {
        return { Error: 'FileCreationError'};
    }
    if (imageTypes.includes(response.headers.get('Content-Type'))) {
        let imageURL = URL.createObjectURL(file);
        let imgElement = document.createElement('img');
        imgElement.classList.add('message-image');
        imgElement.src = imageURL;
        return imgElement;
    }
    let fileURL = URL.createObjectURL(file);
    let fileElement = document.createElement('a');
    fileElement.classList.add('message-file');
    fileElement.href = fileURL;
    let fileName = (response.headers.get('Content-Disposition')).split('filename=')[1];
    fileElement.download = fileName;
    fileElement.textContent = fileName;
    return fileElement;
}

/**
 * Sends file to the server
 * @param file
 */
async function sendFile(file) {
    let data = new FormData();
    data.append('file', file);
    let response = await fetch('/uploadFile',{
        method: 'POST',
        body: data
    });
    if (!response.ok) {
        try {
            return await response.json();
        } catch (exception) {
            switch (response.status) {
                case 400 : return { Error: 'WrongRequest'};
                case 500 : return { Error: 'InternalServerError'};
                default : return { Error: 'Unknown error. Response code: ' + response.status };
            }
        }
    }
    return {};
}

/**
 * Connects to the WebSocket server for the message updates
 */
function connectToWS() {
    return new WebSocket('/webs');
}

