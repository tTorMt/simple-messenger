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

continerHeightResize();

