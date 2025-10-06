<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h2 class="text-primary">Loan Management</h2>
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form action="/auth/login" method="post" id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Enter your email" autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required
                                   placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Sign In
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="/forgot-password" class="text-decoration-none">Forgot password?</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <p class="text-muted">&copy; <?= date('Y') ?> Loan Management System</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission with AJAX
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            email: $('#email').val(),
            password: $('#password').val()
        };
        
        $.ajax({
            url: '/auth/authenticate',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.error || 'An error occurred. Please try again.';
                alert(error);
            }
        });
    });
});
</script>
