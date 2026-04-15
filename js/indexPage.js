/*
    Author: Caden Chan
    Date: April 2nd, 2026
    Description: indexPage.js — Client-side form validation for the login page (index.php).
    Takes the form submission to verify that the email matches the required format
    (name@domain.ext) and that a birth date has been selected before allowing the POST
    to proceed to login.php. Displays an inline error message for invalid inputs.
*/

document.getElementById("loginForm").addEventListener("submit", function (e) {
    const emailInput = document.getElementById("email");
    const birthInput = document.getElementById("birth_date");
    const errorDiv = document.getElementById("emailError");
    const val = emailInput.value.trim();

    // Anchored regex: must match the ENTIRE string
    // ^ = start, $ = end — prevents "random!!!@b.c!!!" from slipping through
    // Format: chars @ alphanumeric . alphanumeric (e.g. a@b.c minimum)
    const emailRegex = /^[^\s@]+@[A-Za-z0-9]+\.[A-Za-z0-9]+$/;

    if (!emailRegex.test(val)) {
        // Invalid email — block submission and show error
        e.preventDefault();
        errorDiv.textContent = "Invalid email. Must be in the format: name@domain.ext";
        errorDiv.classList.remove("hidden");
        emailInput.focus();
        return;
    }

    if (!birthInput.value) {
        // Missing birth date — block submission and show error
        e.preventDefault();
        errorDiv.textContent = "Please select your birth date.";
        errorDiv.classList.remove("hidden");
        birthInput.focus();
        return;
    }

    // All good — allow the form to submit
    errorDiv.classList.add("hidden");
});

// Hide error as soon as user starts correcting the email
document.getElementById("email").addEventListener("input", function () {
    document.getElementById("emailError").classList.add("hidden");
});

// Hide error as soon as user picks a date
document.getElementById("birth_date").addEventListener("change", function () {
    document.getElementById("emailError").classList.add("hidden");
});