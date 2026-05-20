<form id="registrationForm" method="post" action="/auth/register">
    <input type="text" id="name" name="name" placeholder="Full Name" required>
    <input type="email" id="email" name="email" placeholder="Email" required>
    <input type="password" id="password" name="password" placeholder="Password" required>
    <input type="text" id="phone" name="phone" placeholder="Phone Number" required>
    <input type="text" id="location" name="location" placeholder="Location" required>
    <select id="role" name="role">
        <option value="Customer">Customer</option>
        <option value="Provider">Provider</option>
    </select>
    <button type="submit">Register</button>
    <div id="message"></div> <!-- To show success/error messages -->
</form>