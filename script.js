//make container resize than window resized or loaded
function continerHeightResize() {
    window.addEventListener('load', resizeContainer);
    window.addEventListener('resize', resizeContainer);

    function resizeContainer() {
        let container = document.getElementById('main-container');
        if (window.innerHeight > 500)
            container.style.height =  window.innerHeight + "px";
    }
}

continerHeightResize();