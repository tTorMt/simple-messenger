//Blocks to show and hide
let chatBlock = document.querySelector(".chat-block");
let userSearchBlock = document.querySelector(".user-search-block");

function hideAllBlocks() {
  chatBlock.classList.add("hidden");
  userSearchBlock.classList.add("hidden");
}

mainInit();

//Main initialize function
function mainInit() {
  let openConvBtn = document.getElementById("open-conv");
  let chooseUserBtn = document.getElementById("choose-user");
  let chooseConvBtn = document.getElementById("choose-conv");
  let newChatBtn = document.getElementById("new-chat");
  let convMenuDiv = document.querySelector(".conv-menu");

  if (convMenuDiv) {
    initChatBlock();
    convMenuDiv.addEventListener("click", (event) => {
      switch (event.target) {
        case openConvBtn:
          showChatBlock();
          break;
        case chooseUserBtn: {
          showUserSearchBlock();
          initUserSearch();
          break;
        }
        case chooseConvBtn:
          hideAllBlocks();
          break; //To do
        case newChatBtn:
          hideAllBlocks();
          break; //To do
      }
    });
  }
}

//scroll chat down to the last message
//TO DO find use for this function
function chatDown() {
  let chat = document.querySelector(".chat");
  chat.scrollTop = 9999;
}

//User search block
function showUserSearchBlock() {
  hideAllBlocks();
  userSearchBlock.classList.remove("hidden");
}

function initUserSearch() {
  let searchField = document.getElementById("contact-search");
  let userList = document.querySelector('.user-list');
  searchField.addEventListener("keyup", userSearch);
  userList.addEventListener('click', initPrivateConversation);

  function clearUserList() {
    let userList = document.querySelector(".user-list");
    numFound = document.querySelector(".num-found");
    while (userList.lastChild != numFound) {
      userList.removeChild(userList.lastChild);
    }
    changeNumFound();
  }

  function fillUserList(listOfUsersFound) {
    clearUserList();
    let userList = document.querySelector(".user-list");
    for (let userName in listOfUsersFound) {
      let userNode = createUserNode(userName, listOfUsersFound[userName]);
      userList.appendChild(userNode);
    }
    changeNumFound();

    function createUserNode(name, userId) {
      let userNode = document.createElement("div");
      let userChooseBtn = document.createElement("button");
      let userName = document.createElement("div");
      let userNameText = document.createTextNode(name);
      let btnText = document.createTextNode("V");
      userChooseBtn.setAttribute('id', 'user-' + userId);
      userNode.classList.add("user-node");
      userChooseBtn.classList.add("user-choose");
      userChooseBtn.appendChild(btnText);
      userName.classList.add("user-name");
      userName.appendChild(userNameText);
      userNode.appendChild(userName);
      userNode.appendChild(userChooseBtn);
      return userNode;
    }
  }

  function changeNumFound() {
    let numFound = document.querySelector(".num-found p");
    let userList = document.querySelectorAll(".user-list .user-node");
    numFound.textContent = "Найдено: " + userList.length;
  }

  async function userSearch(event) {
    if (event.code === "ShiftLeft" || event.code === "ShiftRight") return;
    let namePart = document.getElementById("contact-search").value;
    if (namePart.length < 4) {
      clearUserList();
      return;
    }
    let response = await fetch(`index.php?namePart=${namePart}`);
    if (!response.ok) {
      console.error("Error in user search. Error response from server");
      clearUserList();
      return;
    }
    let userNames = await response.json();
    fillUserList(userNames);
  }

  async function initPrivateConversation(event) {
    if (event.target.classList.contains('user-choose')) {
      event.stopPropagation();
      let userId = event.target.getAttribute('id').split('-')[1];
      let response = await fetch(`index.php?convUserId=${userId}`);
      if (response.ok) {
        let result = await response.text();
        if (result != 'ok') {
          console.error('Cannot start conversation with choosen user');
          return;
        }
        showChatBlock();
      } else {
        console.error('Failed response from server');
      }
    }
  }
}

function initChatBlock() {
  let sendMessageBtn = document.getElementById('send');
  let messageText = document.getElementById('user-message');
  sendMessageBtn.addEventListener('click', (event) => {
    event.stopPropagation();
    let message = messageText.value;
    if (message.length > 0) {
      sendMessage(message);
    }
    messageText.value = '';
  });

  async function sendMessage(message) {
    let response = await fetch('index.php', {
      method: 'POST',
      headers: {
        'Content-type': 'application/x-www-form-urlencoded'
      },
      body: 'message=' + message
    });
    if (!response.ok) {
      console.error('Send message error');
      return;
    }
    let status = await response.text();
    if (status === 'ok') {
      return;
    }
    console.error('Error storing message');
  }
}

function showChatBlock() {
  hideAllBlocks();
  chatBlock.classList.remove("hidden");
  let chat = document.querySelector('.chat');
  loadMessages();

  async function loadMessages() {
    clearChat();
    let response = await fetch('index.php?loadMessages');
    if (!response.ok) {
      console.error('Could not load messages');
    }
    let messages = await response.json();
    fillMessagesBlock(messages);
  }

  function fillMessagesBlock(messages) {
    for (const message of messages) {
      chat.appendChild(createMessageNode(message));
    }
  }

  function createMessageNode(message) {
    let userName = message.user_name;
    let date = message.ms_date;
    let messageText = message.message;
    let messageNode = document.createElement('div');
    messageNode.classList.add('message');
    if (userName !== 'me') {
      let nameNode = document.createElement('h4');
      nameNode.textContent = userName;
      messageNode.appendChild(nameNode);
      messageNode.classList.add('left');
    } else {
      messageNode.classList.add('right');
    }
    let textNode = document.createTextNode(messageText);
    let dateNode = document.createElement('p');
    dateNode.textContent = date;
    dateNode.classList.add('date-time');
    messageNode.appendChild(textNode);
    messageNode.appendChild(dateNode);
    return messageNode;
  }

  function clearChat() {
    while (chat.lastChild) {
      chat.removeChild(chat.lastChild);
    }
  }
}
