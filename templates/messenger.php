<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="/styles/chat.css" rel="stylesheet">
    <script src="/scripts/client.js" async></script>
    <script src="/scripts/chat.js" defer></script>
    <title>Simple messenger</title>
</head>
<body>
<div id="change-pass" hidden="">
    <form>
        <h1>Change password</h1>
        <label for="old-password">Old password</label>
        <input type="password" id="old-password">
        <label for="new-password">New password</label>
        <input type="password" id="new-password">
        <label for="retype">Retype password</label>
        <input type="password" id="retype">
        <button id="change-btn">Change</button>
        <button id="change-close">Close</button>
        <p id="change-pass-error" hidden=""></p>
    </form>
</div>
<div id="app">
    <header>
        <?php echo "<h4>".$_SESSION['userName']."</h4>" ?>
        <a href="" id="pass-change-link">Change password</a>
    </header>
    <aside id="chat-list">
        <p id="result-chat-field" hidden>Error field</p>
        <div id="new-chat">
            <input type="text" id="chatName" name="chatName" placeholder="New chat name..." >
            <button id="createChat">Create</button>
        </div>
        <button id="reload-chats">Reload chat list</button>
        
    </aside>
    <main id="messenger" style="display: none">
        <button id="back">Back</button>
        <h4></h4>
        <div id="message-list">
            <!--
            Messages example:
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
                -->
        </div>
        <div id="actions">
            <p id="result-message-field" hidden>Error message</p>
            <div id="add-user">
                <input id="user-name" name="user-name" placeholder="Type a user name...">
                <button id="add">Add</button>
            </div>
            <div id="file-send">
                <label for="file">Send file</label>
                <input type="file" id="file" name="file">
                <button id="send-file">Send</button>
            </div>
            <div id="message-send">
                <textarea id="message" rows="1" placeholder="Type a message..." disabled></textarea>
                <button id="send" disabled>Send</button>
            </div>
        </div>
    </main>
</div>
</body>
</html>