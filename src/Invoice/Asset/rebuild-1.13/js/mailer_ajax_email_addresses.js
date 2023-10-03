document.addEventListener('click', event => {
    if (event.target.matches('#adminEmail')) {
        const input = document.getElementById('adminEmail');
        const div = document.getElementById('option_Email');
        if (input.value) {
            div.value = input.value;
        }
    }
    if (event.target.matches('#senderEmail')) {
        const input = document.getElementById('senderEmail');
        const div = document.getElementById('option_Email');
        if (input.value) {
            div.value = input.value;
        }
    }
    if (event.target.matches('#fromEmail')) {
        const input = document.getElementById('fromEmail');
        const div = document.getElementById('option_Email');
        if (input.value) {
            div.value = input.value;
        }
    }
});
