document.addEventListener('DOMContentLoaded', () => {
    // Auth Page Logic
    const authPage = document.getElementById('auth-page');
    if (authPage) {
        const loginForm = document.getElementById('login-form-container');
        const registerForm = document.getElementById('register-form-container');
        const showRegisterBtn = document.getElementById('show-register');
        const showLoginBtn = document.getElementById('show-login');

        // Toggle Forms
        showRegisterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
        });

        showLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
        });

        // Toggle Password Visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                // Simple icon toggle text for now, could use SVG replacement
                this.innerHTML = type === 'password'
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>';
            });
        });

        // Handle Login Submission
        document.getElementById('login-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'Signing in...';
            submitBtn.disabled = true;

            const formData = {
                email: this.email.value,
                password: this.password.value
            };

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Login successful', 'success');
                    setTimeout(() => window.location.href = result.data.redirect, 500);
                } else {
                    showToast(result.message, 'error');
                    submitBtn.innerText = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        });

        // Handle Register Submission
        document.getElementById('register-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'Creating Account...';
            submitBtn.disabled = true;

            // Basic Client Validation
            if (this.password.value !== this.confirmPassword.value) {
                showToast("Passwords do not match", 'error');
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
                return;
            }

            const formData = {
                fullName: this.fullName.value,
                email: this.email.value,
                gender: this.gender.value, // Ensure hidden input is updated
                designation: this.designation.value,
                department: this.department.value,
                role: this.role.value,
                password: this.password.value
            };

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Registration successful', 'success');
                    setTimeout(() => window.location.href = result.data.redirect, 500);
                } else {
                    showToast(result.message, 'error');
                    submitBtn.innerText = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        });

        // Gender Selection Logic
        const genderBtns = document.querySelectorAll('.gender-btn');
        const genderInput = document.getElementById('gender-input');
        if (genderBtns.length && genderInput) {
            genderBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    // Reset all
                    genderBtns.forEach(b => {
                        b.classList.remove('bg-primary', 'text-primary-foreground', 'border-primary');
                        b.classList.add('bg-card', 'text-foreground', 'border-border', 'hover:bg-muted');
                    });
                    // Activate clicked
                    this.classList.remove('bg-card', 'text-foreground', 'border-border', 'hover:bg-muted');
                    this.classList.add('bg-primary', 'text-primary-foreground', 'border-primary');
                    // Set value
                    genderInput.value = this.dataset.value;
                });
            });
        }
    }
});
