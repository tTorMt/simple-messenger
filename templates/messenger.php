<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="/styles/chat.css" rel="stylesheet">
    <script src="/scripts/client.js" async></script>
    <title>Simple messenger</title>
</head>
<body>
<div id="app">
    <header>

    </header>
    <aside id="chat-list">
        <div id="new-chat">
            <input type="text" id="chatName" name="chatName" placeholder="New chat name..." >
            <button id="createChat">Create</button>
        </div>
        <div class="chat-node">
            <h3>chat name chat name chat name chat name chat name</h3>
            <button id="chat-1">Open</button>
        </div>
        <div class="chat-node">
            <h3>chat name</h3>
            <button id="chat-1">Open</button>
        </div>
    </aside>
    <main id="messenger">
        <div id="message-list">
            <button id="back">Back</button>
            <p>user1 11.12.2099 11:22:33: Hello world!</p>
            <p>user1 11.12.2099 11:22:33: Hello world!</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque maximus elit in consequat
                sollicitudin. Aenean sit amet risus convallis nibh tempor sagittis. In scelerisque bibendum ante, in
                molestie velit convallis viverra. Morbi ac urna quis velit vestibulum molestie fermentum ut diam. Proin
                sagittis metus at arcu consequat maximus. Maecenas dictum ante enim, quis sollicitudin metus molestie
                ac. Nullam vel eros congue, placerat elit vitae, tristique ipsum. Vestibulum euismod nec eros sit amet
                commodo. Aenean mollis fermentum tristique. Nunc vel erat arcu. Pellentesque habitant morbi tristique
                senectus et netus et malesuada fames ac turpis egestas. Mauris eget ex ac lacus egestas mattis ut nec
                dui. Maecenas volutpat sit amet justo ut fringilla. Morbi nec varius libero, id hendrerit massa.</p>
        </div>
        <div id="actions">
            <div id="add-user">
                <input id="user-name" name="user-name" placeholder="Type a user name...">
                <button id="add">Add</button>
            </div>
            <div id="message-send">
                <textarea id="message" rows="1" placeholder="Type a message..."></textarea>
                <button id="send">Send</button>
            </div>
        </div>
    </main>
</div>
</body>
</html>