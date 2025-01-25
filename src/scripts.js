let pinTimer = null;
let resendTimer = null;
let canResendPin = false;
let resetEmail = '';

function isRecaptchaLoaded() {
    return typeof grecaptcha !== 'undefined' && window.recaptchaLoaded;
}

function initRecaptcha() {
    if (typeof grecaptcha === 'undefined') {
        console.log('Waiting for reCAPTCHA to load...');
        setTimeout(initRecaptcha, 100);
        return;
    }

    try {
        grecaptcha.render('recaptcha-container', {
            'sitekey': '6Lcbf7oqAAAAAD6SdlbYuMU19-wDDCkbuI0r1tYq', // Replace with your site key
            'callback': function(response) {
                console.log('reCAPTCHA verified');
            }
        });
    } catch (e) {
        console.error('reCAPTCHA initialization error:', e);
    }
}

function waitForRecaptcha() {
    return new Promise((resolve) => {
        if (isRecaptchaLoaded()) {
            resolve();
        } else {
            const checkRecaptcha = setInterval(() => {
                if (isRecaptchaLoaded()) {
                    clearInterval(checkRecaptcha);
                    resolve();
                }
            }, 100);
        }
    });
}

function startPinTimer() {
    let timeLeft = 60;
    const pinCountdown = $('#pin-countdown');

    clearInterval(pinTimer);
    pinCountdown.text(timeLeft).removeClass('timer-warning');

    pinTimer = setInterval(() => {
        timeLeft--;
        pinCountdown.text(timeLeft);

        if (timeLeft <= 10) {
            pinCountdown.addClass('timer-warning');
        }

        if (timeLeft <= 0) {
            clearInterval(pinTimer);
            showFormError('forgot-form', 'PIN has expired. Please request a new one.');
            $('#reset-pin').val('').prop('disabled', true);
            $('#verify-pin-form button[type="submit"]').prop('disabled', true);
        }
    }, 1000);
}

function startResendTimer() {
    let timeLeft = 60;
    const resendBtn = $('#resend-pin');
    const resendCountdown = $('#resend-countdown');

    canResendPin = false;
    clearInterval(resendTimer);
    resendBtn.prop('disabled', true);
    resendCountdown.text(timeLeft);

    resendTimer = setInterval(() => {
        timeLeft--;
        resendCountdown.text(timeLeft);
        resendBtn.text(`Request New PIN (${timeLeft}s)`);

        if (timeLeft <= 0) {
            clearInterval(resendTimer);
            resendBtn.prop('disabled', false);
            resendBtn.text('Request New PIN');
            canResendPin = true;
        }
    }, 1000);
}

// Global functions
function openModal(type) {
    const modal = document.getElementById('modal');
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');
    const forgotForm = document.getElementById('forgot-form');

    modal.classList.add('active');
    loginForm["style"].display = 'none';
    signupForm["style"].display = 'none';
    if (forgotForm) forgotForm["style"].display = 'none';

    // Clean up any existing reCAPTCHA
    $('#recaptcha-container').empty();

    if (type === 'login') {
        loginForm["style"].display = 'block';
    } else if (type === 'signup') {
        signupForm["style"].display = 'block';
    } else if (type === 'forgot') {
        forgotForm["style"].display = 'block';
        $('#forgot-email-form').show();
        $('#verify-pin-form').hide();
        $('#reset-password-form').hide();

        // Only initialize reCAPTCHA if we're showing the email form
        if ($('#forgot-email-form').is(':visible')) {
            setTimeout(() => {
                initRecaptcha();
            }, 100);
        }
    }

    $('.error-message').hide();
}

function cleanupModal() {
    // Clear all timers
    clearInterval(pinTimer);
    clearInterval(resendTimer);

    // Reset forms
    $('form').trigger('reset');

    // Hide all error messages
    $('.error-message').hide();

    // Clean up reCAPTCHA
    $('#recaptcha-container').empty();
    if (typeof grecaptcha !== 'undefined') {
        try {
            grecaptcha.reset();
        } catch (e) {
            console.log('reCAPTCHA cleanup error:', e);
        }
    }
}

// Update closeModal to use cleanup function
function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.remove('active');
    cleanupModal();
}

function validateSignupForm() {
    const username = $('#signup-username').val().trim();
    const email = $('#signup-email').val().trim();
    const password = $('#signup-password').val();
    const confirmPassword = $('#signup-confirm-password').val();

    // Clear previous error messages
    $('.error-message').hide();

    if (username.length < 3) {
        showFormError('signup-form-element', 'Username must be at least 3 characters long');
        return false;
    }

    if (!email) {
        showFormError('signup-form-element', 'Email is required');
        return false;
    }

    if (password.length < 8) {
        showFormError('signup-form-element', 'Password must be at least 8 characters long');
        return false;
    }

    if (password !== confirmPassword) {
        showFormError('signup-form-element', 'Passwords do not match');
        return false;
    }

    return true;
}

function showFormError(formId, message) {
    console.log('Form error:', message); // Debug log
    const errorElement = $(`#${formId} .error-message`);
    if (!errorElement.length) {
        // If error element doesn't exist, create it
        $(`#${formId}`).prepend(`<div class="error-message"></div>`);
    }
    errorElement.text(message)
        .slideDown()
        .css({
            'background': 'rgba(255, 68, 68, 0.1)',
            'border': '1px solid rgba(255, 68, 68, 0.3)',
            'color': '#ff4444',
            'padding': '10px',
            'border-radius': '8px',
            'margin-bottom': '15px',
            'font-size': '0.9rem'
        });

    // Auto-hide after 3 seconds
    setTimeout(() => {
        errorElement.slideUp();
    }, 3000);
}

$('#profile-picture-input').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        console.log('Selected file:', file); // Debug log

        // Validate file size
        if (file.size > 500 * 1024) {
            showFormError('profile-form', 'Profile picture must be less than 500KB');
            this.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showFormError('profile-form', 'Only JPG, PNG and GIF files are allowed');
            this.value = '';
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#current-profile-picture').attr('src', e.target.result);
        };
        reader.onerror = function(e) {
            console.error('FileReader error:', e);
            showFormError('profile-form', 'Error reading file');
        };
        reader.readAsDataURL(file);
    }
});

function showPopup(type, message) {
    // Remove any existing popups
    $('.popup').remove();

    const popup = $('<div>')
        .addClass(`popup ${type}`)
        .text(message);
    $('body').append(popup);

    // Auto-remove popup after 3 seconds
    setTimeout(() => {
        popup.fadeOut(500, () => popup.remove());
    }, 3000);
}

function openProfileModal() {
    const modal = document.getElementById('profile-modal');
    modal.classList.add('active');

    // Load current user data
    $.ajax({
        url: 'edit_profile.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const userData = response.data;
                $('#new-username').val(userData.username);
                $('#new-phone').val(userData.phone);

                // Set profile picture
                const profilePic = document.getElementById('current-profile-picture');
                profilePic.src = userData.profile_picture || 'https://via.placeholder.com/150';
            }
        }
    });
}

function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    modal.classList.remove('active');
    $('#profile-form')[0].reset();
}

// Handle profile picture preview
document.getElementById('profile-picture-input').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('current-profile-picture').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});

$('#profile-form').on('submit', function(e) {
    e.preventDefault();
    console.log('Profile form submitted'); // Debug log

    const formData = new FormData(this);
    const submitButton = $(this).find('button[type="submit"]');

    // Debug log for form data
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    submitButton.prop('disabled', true).text('Saving...');

    $.ajax({
        url: 'edit_profile.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Server response:', response); // Debug log
            if (response.success) {
                showPopup('success', response.message);
                setTimeout(() => {
                    closeProfileModal();
                    window.location.reload();
                }, 2000);
            } else {
                showFormError('profile-form', response.message || 'Failed to update profile');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            showFormError('profile-form', 'An error occurred while updating your profile');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Save Changes');
        }
    });
});

// Add validation for profile picture size before upload
document.getElementById('profile-picture-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (500KB = 500 * 1024 bytes)
        if (file.size > 500 * 1024) {
            showFormError('profile-form', 'Profile picture must be less than 500KB');
            this.value = ''; // Clear the file input
            return;
        }

        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showFormError('profile-form', 'Only JPG, PNG and GIF files are allowed');
            this.value = '';
            return;
        }

        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('current-profile-picture').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Add password validation as user types
$('#new-password').on('input', function() {
    const password = $(this).val();
    let errorMessage = '';

    if (password.length > 0) {
        if (password.length < 8) {
            errorMessage = 'Password must be at least 8 characters long';
        } else if (!/[A-Z]/.test(password)) {
            errorMessage = 'Password must contain at least one uppercase letter';
        } else if (!/[a-z]/.test(password)) {
            errorMessage = 'Password must contain at least one lowercase letter';
        } else if (!/[0-9]/.test(password)) {
            errorMessage = 'Password must contain at least one number';
        } else if (!/[!@#$%^&*()\-_=+{};:,<.>]/.test(password)) {
            errorMessage = 'Password must contain at least one special character';
        }
    }

    if (errorMessage) {
        showFormError('profile-form', errorMessage);
    } else {
        $('.error-message').hide();
    }
});

// Main initialization function
function initializeEventHandlers() {
    // Clear any existing handlers
    $(document).off('submit', '#signup-form-element');
    $(document).off('submit', '#login-form form');

    // Handle signup form submission
    $('#signup-form-element').on('submit', function(e) {
        e.preventDefault();
        console.log('Signup form submitted');

        if (!validateSignupForm()) {
            return false;
        }

        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Creating Account...');

        const formData = $(this).serialize();
        console.log('Form data:', formData);

        $.ajax({
            url: 'signup.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    showPopup('success', response.message);
                    closeModal(); // Close modal before reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showFormError('signup-form-element', response.message || 'Registration failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showFormError('signup-form-element', 'An error occurred during registration');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Sign Up');
            }
        });
    });

    // Handle login form submission
    $('#login-form form').on('submit', function(e) {
        e.preventDefault();

        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Logging in...');

        const formData = $(this).serialize();

        $.ajax({
            url: 'login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message);
                    closeModal(); // Close modal before redirect
                    setTimeout(() => {
                        // Check if there's a redirect URL in the response
                        if (response.redirect) {
                            window.location.href = response.redirect; // Redirect to specified URL
                        } else {
                            window.location.reload(); // Fallback to reload if no redirect specified
                        }
                    }, 2000);
                } else {
                    showFormError('login-form', response.message || 'Login failed');
                    $('.error-message').css('color', '#dc3545'); // Error color
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', status, error);
                showFormError('login-form', 'Please check your email for verification.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Login');
            }
        });
    });

    // Handle forgot password form submission
    $('#forgot-email-form').off('submit').on('submit', async function(e) {
        e.preventDefault();
        const email = $('#forgot-email').val();
        resetEmail = email;

        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Sending...');

        try {
            // Wait for reCAPTCHA to load
            await waitForRecaptcha();

            const recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse) {
                showFormError('forgot-form', 'Please complete the reCAPTCHA verification');
                submitButton.prop('disabled', false).text('Send Reset Code');
                return;
            }

            $.ajax({
                url: 'forgot_password.php',
                type: 'POST',
                data: {
                    email: email,
                    'g-recaptcha-response': recaptchaResponse
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Success response:', response); // Debug log
                    if (response.success) {
                        showPopup('success', response.message);
                        $('#forgot-email-form').hide();
                        $('#verify-pin-form').show();
                        $('#reset-pin').val('').prop('disabled', false);
                        $('#verify-pin-form button[type="submit"]').prop('disabled', false);
                        startPinTimer();
                        startResendTimer();
                    } else {
                        showFormError('forgot-form', response.message || 'Failed to send reset code');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    let errorMessage = 'An error occurred. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    showFormError('forgot-form', errorMessage);
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Send Reset Code');
                    try {
                        grecaptcha.reset();
                    } catch (e) {
                        console.error('Error resetting reCAPTCHA:', e);
                    }
                }
            });
        } catch (error) {
            console.error('reCAPTCHA error:', error);
            showFormError('forgot-form', 'reCAPTCHA loading failed. Please refresh the page.');
            submitButton.prop('disabled', false).text('Send Reset Code');
        }
    });

    // PIN resend button handler
    $('#resend-pin').off('click').on('click', function() {
        if (!canResendPin) return;

        const resendButton = $(this);
        resendButton.prop('disabled', true).text('Sending...');

        $.ajax({
            url: 'forgot_password.php',
            type: 'POST',
            data: {
                email: resetEmail,
                resend: true
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', 'A new PIN has been sent to your email');
                    $('#reset-pin').val('').prop('disabled', false);
                    $('#verify-pin-form button[type="submit"]').prop('disabled', false);
                    startPinTimer();
                    startResendTimer();

                    // Update button text with countdown
                    resendButton.text('Request New PIN (60s)');
                } else {
                    showFormError('forgot-form', response.message);
                    resendButton.prop('disabled', false).text('Request New PIN');
                }
            },
            error: function() {
                showFormError('forgot-form', 'Failed to send new PIN. Please try again.');
                resendButton.prop('disabled', false).text('Request New PIN');
            },
            complete: function() {
                // This ensures the button text is always reset if something goes wrong
                if (resendButton.text() === 'Sending...') {
                    resendButton.prop('disabled', false).text('Request New PIN');
                }
            }
        });
    });

    // PIN verification form submission
    $('#verify-pin-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        const pin = $('#reset-pin').val();
        const submitButton = $(this).find('button[type="submit"]');

        submitButton.prop('disabled', true).text('Verifying...');

        $.ajax({
            url: 'forgot_password.php',
            type: 'POST',
            data: {
                email: resetEmail,
                pin: pin
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', 'PIN verified successfully!');
                    clearInterval(pinTimer);
                    clearInterval(resendTimer);

                    // Clean up reCAPTCHA
                    $('#recaptcha-container').empty();
                    if (typeof grecaptcha !== 'undefined') {
                        try {
                            grecaptcha.reset();
                        } catch (e) {
                            console.log('reCAPTCHA cleanup error:', e);
                        }
                    }

                    // Show password reset form
                    $('#verify-pin-form').hide();
                    $('#reset-password-form').show();
                } else {
                    showFormError('forgot-form', response.message);
                }
            },
            error: function() {
                showFormError('forgot-form', 'Verification failed. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Verify PIN');
            }
        });
    });

    // Reset password form submission
    $('#reset-password-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        const newPassword = $('#new-password').val();
        const confirmPassword = $('#confirm-new-password').val();
        const submitButton = $(this).find('button[type="submit"]');

        if (newPassword.length < 8) {
            showFormError('forgot-form', 'Password must be at least 8 characters long');
            return;
        }

        if (newPassword !== confirmPassword) {
            showFormError('forgot-form', 'Passwords do not match');
            return;
        }

        submitButton.prop('disabled', true).text('Resetting...');

        $.ajax({
            url: 'forgot_password.php',
            type: 'POST',
            data: {
                email: resetEmail,
                new_password: newPassword,
                action: 'reset_password'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup('success', response.message);
                    setTimeout(() => {
                        closeModal();
                        openModal('login');
                    }, 2000);
                } else {
                    showFormError('forgot-form', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Password reset error:', error);
                showFormError('forgot-form', 'Password reset failed. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Reset Password');
            }
        });
    });
}

// Function to handle alert animations
function handleAlertAnimations() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(function() {
            alert.style.animation = 'slideOut 0.5s ease-out forwards';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    }
}

// Wait for document to be ready
$(document).ready(function() {
    initializeEventHandlers();
    handleAlertAnimations();
});

// Initialize everything when document is ready
$(document).ready(function() {
    initializeEventHandlers();

    // Handle alert animations
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(function() {
            alert.style.animation = 'slideOut 0.5s ease-out forwards';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    }
});