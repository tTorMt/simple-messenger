emailVerificationInit();

/**
 * Initializes email verification
 */
function emailVerificationInit() {
    let tokenField = document.getElementById('email-change-token');
    let verifyBtn = document.getElementById('auth');

    verifyBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        errorFieldToggle();
        verifyBtn.setAttribute('disabled', '');
        let token = tokenField.value;
        let result = await emailVerification(token);
        if (Object.hasOwn(result, 'Error')) {
            errorFieldToggle('error', result.Error);
        } else {
            errorFieldToggle('success', 'Success');
        }
        verifyBtn.removeAttribute('disabled');
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