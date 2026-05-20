document.getElementById('registrationForm')?.addEventListener('submit', async (e) => {
    e.preventDefault(); // Stop the page from refreshing

    const messageDiv = document.getElementById('message');// this will get the message div to show success or error messages
    const formData = new FormData(e.target);// this will get all the form data as key-value pairs
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            messageDiv.innerHTML = `<p style="color: green">${result.message}</p>`;
            // Redirect based on role after 1.5 seconds
            setTimeout(() => {
                window.location.href = data.role === 'Customer' ? '/customer/dashboard' : '/provider/dashboard';
            }, 1500);
        } else {
            messageDiv.innerHTML = `<p style="color: red">${result.error || 'Registration failed'}</p>`;
        }
    } catch (error) {
        messageDiv.innerHTML = `<p style="color: red">Connection error. Please try again.</p>`;
    }
});