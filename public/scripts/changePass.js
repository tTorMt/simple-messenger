passwordChangeInit();

/**
 * Password change initialization
 */
function passwordChangeInit() {
    let passwordInputNode = document.getElementById('userPassword');
    let passwordRetypeNode = document.getElementById('password-retype');
    let passTokenNode = document.getElementById('pass-token');
    let changeButton = document.getElementById('auth');

    changeButton.addEventListener('click', async (event) => {
        event.preventDefault();
        changeButton.setAttribute('disabled', '');
        let newPassword = passwordInputNode.value;
        let retype = passwordRetypeNode.value;
        let passToken = passTokenNode.value;

        if (newPassword !== retype) {
            errorFieldToggle('error', "Password and retype password do not match");
        } else {
            let result = await changePassword(newPassword, undefined, passToken);
            if (Object.hasOwn(result, 'Error')) {
                errorFieldToggle('error', result.Error);
            } else {
                errorFieldToggle('success', 'Success');
            }
        }
        changeButton.removeAttribute('disabled');
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