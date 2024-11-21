signTypeToggle();
forgotPasswordToggle();
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
    let forgotPassword = document.getElementById('forgot-password');

    signBtn.addEventListener('click', (event) => {
        event.preventDefault();
        errorFieldToggle();
        emailInput.toggleAttribute('hidden');
        emailLabel.toggleAttribute('hidden');
        emailInput.toggleAttribute('required');
        retypeInput.toggleAttribute('hidden');
        retypeInput.toggleAttribute('required');
        retypeLabel.toggleAttribute('hidden');
        forgotPassword.toggleAttribute('hidden');
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
 * Changes the forgot password / sign in mode
 */
function forgotPasswordToggle() {
    let header = document.querySelector('#app main h1');
    let userNameLabel = document.querySelector('#app label[for="userName"]');
    let nameInput = document.getElementById('userName');
    let emailLabel = document.querySelector('#app label[for="userEmail"]');
    let emailInput = document.getElementById('userEmail');
    let passwordLabel = document.querySelector('#app label[for="userPassword"]');
    let passwordInput = document.getElementById('userPassword');
    let signBtn = document.getElementById('sign');
    let modeChangeBtn = document.getElementById('forgot-password');
    let sendBtn = document.getElementById('auth');

    modeChangeBtn.addEventListener('click', (event) => {
        event.preventDefault();
        errorFieldToggle();
        userNameLabel.toggleAttribute('hidden');
        nameInput.toggleAttribute('hidden');
        nameInput.toggleAttribute('required');
        emailLabel.toggleAttribute('hidden');
        emailInput.toggleAttribute('hidden');
        emailInput.toggleAttribute('required');
        passwordLabel.toggleAttribute('hidden');
        passwordInput.toggleAttribute('required');
        passwordInput.toggleAttribute('hidden');
        signBtn.toggleAttribute('hidden');

        if (nameInput.hasAttribute('hidden')) {
            header.textContent = 'Forgot password';
            modeChangeBtn.textContent = 'Sign In';
            sendBtn.textContent = 'Send';
            return;
        }

        header.textContent = 'Sign In';
        modeChangeBtn.textContent = 'Forgot password';
        sendBtn.textContent = 'Sign In';
    });
}

/**
 * Handles log in or registration of the user
 */
function authUserInit() {
    let authBtn = document.getElementById('auth');

    authBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        authBtn.setAttribute('disabled', '');
        errorFieldToggle();
        let userName = document.getElementById('userName').value;
        let password = document.getElementById('userPassword').value;
        let email = document.getElementById('userEmail').value;
        let retype = document.getElementById('password-retype').value;
        let authType = document.querySelector('#app main h1').textContent;
        if (authType === 'Sign In') {
            let result = await authorize(userName, password);
            if (result !== 200) {
                if (result === 500) {
                    errorFieldToggle('error', 'An error happened. Try again later');
                } else {
                    errorFieldToggle('error', 'Wrong name or password');
                }
                authBtn.removeAttribute('disabled');
                return;
            }
            window.location.reload();
            return;
        }

        if (authType === 'Forgot password') {
            let result = await forgotPassword(email);
            if (Object.hasOwn(result, 'Error')) {
                errorFieldToggle('error', result.Error);
                authBtn.removeAttribute('disabled');
                return;
            }
            errorFieldToggle('success', 'The password restoration link has been sent');
            authBtn.removeAttribute('disabled');
            return;
        }

        if (password !== retype) {
            errorFieldToggle('error', "The password and the retype password fields aren't equal");
            authBtn.removeAttribute('disabled');
            return;
        }

        let result = await register(userName, password, email);
        if (Object.hasOwn(result, 'Error')) {
            errorFieldToggle('error', result.Error);
            authBtn.removeAttribute('disabled');
            return;
        }
        window.location.reload();
    });
}

/**
 * Toggles an error message
 *
 * @param type
 * @param errorText
 */
function errorFieldToggle(type, errorText) {
    let errorField = document.getElementById('error-field');
    errorField.classList.remove('error');
    errorField.classList.remove('success');

    if (errorText === undefined) {
        errorField.setAttribute('hidden', '');
        return;
    }
    errorField.removeAttribute('hidden');
    errorField.textContent = errorText;
    errorField.classList.add(type);
}