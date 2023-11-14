//Blocks to show and hide
let chatBlock = document.querySelector('.chat-block');
let userSearchBlock = document.querySelector('.user-search-block');

function hideAllBlocks() {
    chatBlock.classList.add('hidden');
    userSearchBlock.classList.add('hidden');
}

//make container resize than window resized or loaded
function continerHeightResize() {
    window.addEventListener('load', resizeContainer);
    window.addEventListener('resize', resizeContainer);

    function resizeContainer() {
        let container = document.getElementById('main-container');
        if (window.innerHeight > 500)
            container.style.height = window.innerHeight + "px";
        chatDown();
    }
}

//scroll chat down to the last message
function chatDown() {
    let content = document.querySelector('.content');
    content.scrollTop = 9999;
}

//User search block
function showUserSearchBlock() {
    hideAllBlocks();
    userSearchBlock.classList.remove('hidden');
}

function initUserSearch() {
    let searchField = document.getElementById('contact-search');
    searchField.addEventListener('keyup', userSearch);

    function clearUserList() {
        let userList = document.querySelector('.user-list');
        numFound = document.querySelector('.num-found');
        while (userList.lastChild != numFound) {
            userList.removeChild(userList.lastChild);
        }
        changeNumFound();
    }
    
    function fillUserList(listOfUsersFound) {
        let userList = document.querySelector('.user-list');
        for (let i = 0; i < listOfUsersFound.length; i++) {
            let userNode = createUserNode(listOfUsersFound[i]);
            userList.appendChild(userNode);
        }
    
        changeNumFound();
    
        function createUserNode(name) {
            let userNode = document.createElement('div');
            let userChooseBtn = document.createElement('button');
            let userName = document.createElement('div');
            let userNameText = document.createTextNode(name);
            let btnText = document.createTextNode('V');
            userNode.classList.add('user-node');
            userChooseBtn.setAttribute('id', 'user-choose');
            userChooseBtn.appendChild(btnText);
            userName.classList.add('user-name');
            userName.appendChild(userNameText);
            userNode.appendChild(userName);
            userNode.appendChild(userChooseBtn);
            return userNode;
        }
    }
    
    function changeNumFound() {
        let numFound = document.querySelector('.num-found p');
        let userList = document.querySelectorAll('.user-list .user-node');
        numFound.textContent = 'Найдено: ' + userList.length;
    }
    
    async function userSearch() {
        clearUserList();
        let namePart = document.getElementById('contact-search').value;
        if (namePart.length < 4)
            return;
        let response = await fetch(`index.php?namePart=${namePart}`);
        if (!response.ok) {
            console.error('Error in user search. Error response from server')
            return;
        }
        let userNames = await response.json();
        fillUserList(userNames);
    }
}

//To Do chat block functions

continerHeightResize();
showUserSearchBlock();
initUserSearch();
