signTypeToggle();
authUserInit();

/**
 * Changes the sing Up/In mode
 */
function signTypeToggle() {
    let signBtn = document.getElementById('sign');
    let retypeInput = document.getElementById('password-retype');
    let retypeLabel = document.querySelector('label[for="password-retype"]');
    let emailInput = document.getElementById('userEmail');
    let emailLabel = document.querySelector('label[for="userEmail"]');
    let signTypeHeader = document.querySelector('#app main h1');
    let sendBtn = document.getElementById('auth');
    let rules = document.getElementById('rules');

    signBtn.addEventListener('click', (event) => {
        event.preventDefault();
        errorFieldToggle();
        emailInput.toggleAttribute('hidden');
        emailLabel.toggleAttribute('hidden');
        emailInput.toggleAttribute('required');
        retypeInput.toggleAttribute('hidden');
        retypeInput.toggleAttribute('required');
        retypeLabel.toggleAttribute('hidden');
        if (retypeInput.hasAttribute('hidden')) {
            signTypeHeader.textContent = 'Sign In';
            sendBtn.textContent = 'Sign In';
            signBtn.textContent = 'Sign Up';
            rules.setAttribute('hidden', '');
            return;
        }
        rules.removeAttribute('hidden');
        signTypeHeader.textContent = 'Sign Up';
        sendBtn.textContent = 'Sign Up';
        signBtn.textContent = 'Sign In';
    });
}

/**
 * Handles log in or registration of the user
 */
function authUserInit() {
    let authBtn = document.getElementById('auth');

    authBtn.addEventListener('click', (event) => {
        event.preventDefault();
        authBtn.setAttribute('disabled', '');
        errorFieldToggle();
        let userName = document.getElementById('userName').value;
        let password = document.getElementById('userPassword').value;
        let email = document.getElementById('userEmail').value;
        let retype = document.getElementById('password-retype').value;
        let authType = document.querySelector('#app main h1').textContent;
        if (authType === 'Sign In') {
            authorize(userName, password).then(result => {
                if (result !== 200) {
                    if (result === 500) {
                        errorFieldToggle('An error happened. Try again later');
                    } else {
                        errorFieldToggle('Wrong name or password');
                    }
                    authBtn.removeAttribute('disabled');
                    return;
                }
                window.location.reload();
            });
            return;
        }

        if (password !== retype) {
            errorFieldToggle("The password and the retype password fields aren't equal");
            authBtn.removeAttribute('disabled');
            return;
        }
        register(userName, password, email).then(result => {
            if (Object.hasOwn(result, 'Error')) {
                errorFieldToggle(result.Error);
                authBtn.removeAttribute('disabled');
                return;
            }
            window.location.reload();
        });
    });
}

/**
 * Toggles an error message
 *
 * @param errorText
 */
function errorFieldToggle(errorText) {
    let errorField = document.getElementById('error-field');
    if (errorText === undefined) {
        errorField.setAttribute('hidden', '');
        return;
    }
    errorField.removeAttribute('hidden');
    errorField.textContent = errorText;
}