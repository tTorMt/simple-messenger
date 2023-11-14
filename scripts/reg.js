function initializeAuthFields() {
    let loginField = document.getElementById('name');
    let passField = document.getElementById('pass');
    let passRepeatField = document.getElementById('repeat');
    let submit = document.getElementById('submit');
    let correctFieldsFlag = 0;
    let ALL_CORRECT = 7;
    let LOGIN_CORRECT = 4;
    let LOGIN_INCORRECT = 3;
    let PASS_CORRECT = 2;
    let PASS_INCORRECT = 5;
    let PASS_REPEAT_CORRECT = 1;
    let PASS_REPEAT_INCORRECT = 6;

    passRepeatField.addEventListener('input', passRepeatCheck);
    passField.addEventListener('input', passValidationCheck);
    loginField.addEventListener('input', loginCheck);
    document.addEventListener('input', submitActivator);

    function passRepeatCheck() {
        let errField = document.querySelector('main .content .input-error.repeat');
        if (!passRepeatField.value || passField.value === passRepeatField.value) {
            errField.style.display = 'none';
            if (passRepeatField.value)
                correctFieldsFlag |= PASS_REPEAT_CORRECT;
            else
                correctFieldsFlag &= PASS_REPEAT_INCORRECT;
        } else {
            errField.style.display = 'unset';
            correctFieldsFlag &= PASS_REPEAT_INCORRECT;
        }
    }

    function passValidationCheck() {
        const PASS_PATTERNS = [/[a-z]/, /[A-Z]/, /[0-9]/, /[!@$%&.]/];
        passRepeatCheck();
        let errField = document.querySelector('main .content .input-error.pass');
        for (const pattern of PASS_PATTERNS) {
            if (!pattern.test(passField.value)) {
                errField.style.display = 'unset';
                correctFieldsFlag &= PASS_INCORRECT;
                return;
            }
        }
        errField.style.display = 'none';
        correctFieldsFlag |= PASS_CORRECT;
    }

    async function loginIsVacantCheck() {
        let loginErrorField = document.querySelector('main .content .input-error.login');
        let login = loginField.value;
        let response = await fetch(`registr.php?isVacant=${login}`);
        if (!response.ok) {
            console.error('Error is vacant check. Error response from server');
            return;
        }
        let result = (await response.json())['vacant'];
        if (result === 'false') {
            correctFieldsFlag &= LOGIN_INCORRECT;
            loginErrorField.style.display = 'unset';
        } else {
            loginErrorField.style.display = 'none';
        }
    }

    function loginCheck() {
        if (loginField.value) {
            correctFieldsFlag |= LOGIN_CORRECT;
            loginIsVacantCheck();
        } else {
            correctFieldsFlag &= LOGIN_INCORRECT;
        }
    }

    function submitActivator() {
        if (correctFieldsFlag === ALL_CORRECT) {
            if (submit.getAttribute('disabled'))
                submit.removeAttribute('disabled');
        } else {
            if (!submit.getAttribute('disabled'))
                submit.setAttribute('disabled', 'true');
        }
    }
}

initializeAuthFields();