<?php
require_once __DIR__ . '/../config.php';

db()->exec('CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomlogin VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(120) NULL,
    email VARCHAR(120) NULL,
    password_hash VARCHAR(255) NOT NULL,
    verify_token VARCHAR(128) NULL,
    verify_token_expires_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB');

$dbNameStmt = db()->query('SELECT DATABASE()');
$dbName = (string) $dbNameStmt->fetchColumn();

$fullNameCol = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND COLUMN_NAME = \'full_name\'');
$fullNameCol->execute(['schema' => $dbName]);
if ((int) $fullNameCol->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD COLUMN full_name VARCHAR(120) NULL AFTER nomlogin');
}

$emailCol = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND COLUMN_NAME = \'email\'');
$emailCol->execute(['schema' => $dbName]);
if ((int) $emailCol->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD COLUMN email VARCHAR(120) NULL AFTER full_name');
}

$verifyTokenCol = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND COLUMN_NAME = \'verify_token\'');
$verifyTokenCol->execute(['schema' => $dbName]);
if ((int) $verifyTokenCol->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD COLUMN verify_token VARCHAR(128) NULL AFTER password_hash');
}

$verifyExpireCol = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND COLUMN_NAME = \'verify_token_expires_at\'');
$verifyExpireCol->execute(['schema' => $dbName]);
if ((int) $verifyExpireCol->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD COLUMN verify_token_expires_at DATETIME NULL AFTER verify_token');
}

$verifiedAtCol = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND COLUMN_NAME = \'email_verified_at\'');
$verifiedAtCol->execute(['schema' => $dbName]);
if ((int) $verifiedAtCol->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD COLUMN email_verified_at DATETIME NULL AFTER verify_token_expires_at');
}

$emailIndex = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = \'students\' AND INDEX_NAME = \'uniq_students_email\'');
$emailIndex->execute(['schema' => $dbName]);
if ((int) $emailIndex->fetchColumn() === 0) {
    db()->exec('ALTER TABLE students ADD UNIQUE KEY uniq_students_email (email)');
}

/**
 * Send verification email with a unique link.
 */
function sendStudentVerificationEmail(string $email, string $fullName, string $token): bool
{
    $verifyUrl = app_absolute_url('student/verify.php?token=' . urlencode($token));
    $subject = 'Verify your Student Portal email';
    $displayName = $fullName !== '' ? $fullName : 'Student';
    $message = "Hello {$displayName},\n\n"
        . "Please verify your account by clicking the link below:\n"
        . "{$verifyUrl}\n\n"
        . "This link expires in 24 hours.\n\n"
        . "Student Portal";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: no-reply@edu.ac.ma',
    ];

    return @mail($email, $subject, $message, implode("\r\n", $headers));
}

if (!empty($_SESSION['student_id'])) {
    redirectTo('index.php');
}

$error = '';
$success = '';
$activePanel = 'login';
$showVerificationNotice = false;
$registeredEmail = '';
$verificationFallbackLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $password = $_POST['password'] ?? '';

    if ($action === 'register') {
        $activePanel = 'register';
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $error = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } elseif (!preg_match('/@edu\.ac\.ma$/i', $email)) {
            $error = 'Utilisez uniquement une adresse academique se terminant par @edu.ac.ma.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $nomlogin = strstr($email, '@', true) ?: $email;
            $baseNomlogin = preg_replace('/[^a-zA-Z0-9._-]/', '', $nomlogin) ?: 'student';
            $nomlogin = $baseNomlogin;
            $counter = 1;
            while (true) {
                $checkNomlogin = db()->prepare('SELECT id FROM students WHERE nomlogin = :nomlogin LIMIT 1');
                $checkNomlogin->execute(['nomlogin' => $nomlogin]);
                if (!$checkNomlogin->fetch()) {
                    break;
                }
                $counter++;
                $nomlogin = $baseNomlogin . $counter;
            }

            $checkEmail = db()->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
            $checkEmail->execute(['email' => $email]);
            $existingEmail = $checkEmail->fetch();

            if ($existingEmail) {
                $error = 'Cet email existe deja.';
            } else {
                $verifyToken = bin2hex(random_bytes(32));
                $verifyExpiresAt = date('Y-m-d H:i:s', time() + 86400);

                $insert = db()->prepare('INSERT INTO students (nomlogin, full_name, email, password_hash, verify_token, verify_token_expires_at, email_verified_at)
                    VALUES (:nomlogin, :full_name, :email, :password_hash, :verify_token, :verify_token_expires_at, NULL)');
                $insert->execute([
                    'nomlogin' => $nomlogin,
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'verify_token' => $verifyToken,
                    'verify_token_expires_at' => $verifyExpiresAt,
                ]);

                $mailSent = sendStudentVerificationEmail($email, $fullName, $verifyToken);
                $showVerificationNotice = true;
                $registeredEmail = $email;
                if ($mailSent) {
                    $success = 'Compte cree. Un lien de verification a ete envoye a votre email.';
                } else {
                    $verificationFallbackLink = app_url('student/verify.php?token=' . urlencode($verifyToken));
                    $success = 'Compte cree, mais email non envoye localement.';
                }
            }
        }
    } else {
        $activePanel = 'login';
        $loginValue = trim($_POST['login_value'] ?? '');
        if ($loginValue === '' || $password === '') {
            $error = 'Username/Email et mot de passe sont obligatoires.';
        } else {
            $stmt = db()->prepare('SELECT id, nomlogin, full_name, email, password_hash, email_verified_at FROM students WHERE nomlogin = :value OR email = :value LIMIT 1');
            $stmt->execute(['value' => $loginValue]);
            $student = $stmt->fetch();

            if (!$student || !password_verify($password, $student['password_hash'])) {
                $error = 'Identifiants etudiant invalides.';
            } elseif (empty($student['email_verified_at'])) {
                $error = 'Veuillez verifier votre email avant de vous connecter.';
            } else {
                $_SESSION['student_id'] = (int) $student['id'];
                $_SESSION['student_name'] = $student['full_name'] ?: $student['nomlogin'];
                redirectTo('index.php');
            }
        }
    }
}

$pageTitle = 'Connexion Etudiant';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .student-auth-wrap {
        min-height: calc(100vh - 170px);
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 50%, #e0f2fe 100%);
        border-radius: 20px;
        padding: 24px;
    }
    .student-auth-card {
        width: 100%;
        max-width: 460px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
        padding: 28px;
        transition: transform 0.25s ease;
    }
    .student-auth-card:hover {
        transform: translateY(-2px);
    }
    .student-title {
        font-size: 1.7rem;
        font-weight: 700;
        margin-bottom: 18px;
        text-align: center;
    }
    .form-panel {
        display: none;
        animation: fadeIn 0.28s ease;
    }
    .form-panel.active {
        display: block;
    }
    .input-label {
        font-weight: 600;
        margin-bottom: 6px;
    }
    .input-field {
        width: 100%;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 11px 12px;
        margin-bottom: 14px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-field:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    .password-row {
        position: relative;
    }
    .toggle-pass {
        position: absolute;
        right: 10px;
        top: 34px;
        border: 0;
        background: transparent;
        color: #475569;
        cursor: pointer;
    }
    .primary-btn {
        width: 100%;
        border: 0;
        border-radius: 10px;
        padding: 11px;
        background: #4f46e5;
        color: #fff;
        font-weight: 600;
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .primary-btn:hover {
        background: #4338ca;
        transform: translateY(-1px);
    }
    .primary-btn.ready {
        background: #16a34a;
    }
    .primary-btn.ready:hover {
        background: #15803d;
    }
    .secondary-link {
        border: 0;
        background: transparent;
        color: #4f46e5;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
    }
    .switch-row {
        text-align: center;
        margin-top: 14px;
        color: #475569;
    }
    .verify-note {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 14px;
        font-size: 0.95rem;
    }
    .verify-note strong {
        display: block;
        margin-bottom: 6px;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="student-auth-wrap">
    <div class="student-auth-card">
        <h1 class="student-title">Student Portal</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <div id="loginPanel" class="form-panel <?= $activePanel === 'login' ? 'active' : ''; ?>">
            <form method="post" id="loginForm" novalidate>
                <input type="hidden" name="action" value="login">
                <label class="input-label" for="login_value">Username or Email</label>
                <input class="input-field" id="login_value" name="login_value" type="text" placeholder="Enter username or email">

                <div class="password-row">
                    <label class="input-label" for="login_password">Password</label>
                    <input class="input-field" id="login_password" name="password" type="password" placeholder="Enter password">
                    <button type="button" class="toggle-pass" data-target="login_password">Show</button>
                </div>

                <button class="primary-btn" type="submit">Login</button>
            </form>

            <p class="switch-row">
                Don't have an account?
                <button class="secondary-link" id="openRegister" type="button">Create Account</button>
            </p>
        </div>

        <div id="registerPanel" class="form-panel <?= $activePanel === 'register' ? 'active' : ''; ?>">
            <?php if ($showVerificationNotice): ?>
                <div class="verify-note">
                    <strong>Verification required</strong>
                    A verification link was sent to <b><?= e($registeredEmail); ?></b>.<br>
                    Please open your email and click the link to activate your account.
                    <?php if ($verificationFallbackLink !== ''): ?>
                        <br><br>
                        Local fallback link: <a href="<?= e($verificationFallbackLink); ?>"><?= e($verificationFallbackLink); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form method="post" id="registerForm" novalidate>
                <input type="hidden" name="action" value="register">
                <label class="input-label" for="full_name">Full Name</label>
                <input class="input-field" id="full_name" name="full_name" type="text" placeholder="Enter full name">

                <label class="input-label" for="email">Email</label>
                <input class="input-field" id="email" name="email" type="email" placeholder="yourname@edu.ac.ma">

                <div class="password-row">
                    <label class="input-label" for="register_password">Password</label>
                    <input class="input-field" id="register_password" name="password" type="password" placeholder="Create password">
                    <button type="button" class="toggle-pass" data-target="register_password">Show</button>
                </div>

                <div class="password-row">
                    <label class="input-label" for="confirm_password">Confirm Password</label>
                    <input class="input-field" id="confirm_password" name="confirm_password" type="password" placeholder="Confirm password">
                    <button type="button" class="toggle-pass" data-target="confirm_password">Show</button>
                </div>

                <button class="primary-btn" id="registerButton" type="submit">Complete all fields</button>
            </form>

            <p class="switch-row">
                Already have an account?
                <button class="secondary-link" id="backToLogin" type="button">Back to Login</button>
            </p>
        </div>
    </div>
</div>

<script>
    (function () {
        var loginPanel = document.getElementById('loginPanel');
        var registerPanel = document.getElementById('registerPanel');
        var openRegister = document.getElementById('openRegister');
        var backToLogin = document.getElementById('backToLogin');
        var toggleButtons = document.querySelectorAll('.toggle-pass');
        var loginForm = document.getElementById('loginForm');
        var registerForm = document.getElementById('registerForm');
        var registerButton = document.getElementById('registerButton');
        var fullNameInput = document.getElementById('full_name');
        var emailInput = document.getElementById('email');
        var passwordInput = document.getElementById('register_password');
        var confirmInput = document.getElementById('confirm_password');

        function showPanel(panel) {
            loginPanel.classList.remove('active');
            registerPanel.classList.remove('active');
            panel.classList.add('active');
        }

        if (openRegister) {
            openRegister.addEventListener('click', function () {
                showPanel(registerPanel);
            });
        }

        if (backToLogin) {
            backToLogin.addEventListener('click', function () {
                showPanel(loginPanel);
            });
        }

        Array.prototype.forEach.call(toggleButtons, function (button) {
            button.addEventListener('click', function () {
                var targetInput = document.getElementById(button.getAttribute('data-target'));
                if (!targetInput) return;
                var isHidden = targetInput.type === 'password';
                targetInput.type = isHidden ? 'text' : 'password';
                button.textContent = isHidden ? 'Hide' : 'Show';
            });
        });

        function updateRegisterButtonState() {
            if (!registerButton) return;
            var fullNameValue = fullNameInput ? fullNameInput.value.trim() : '';
            var emailValue = emailInput ? emailInput.value.trim() : '';
            var passwordValue = passwordInput ? passwordInput.value.trim() : '';
            var confirmValue = confirmInput ? confirmInput.value.trim() : '';

            var hasAllFields = fullNameValue !== '' && emailValue !== '' && passwordValue !== '' && confirmValue !== '';
            var validAcademicEmail = /@edu\.ac\.ma$/i.test(emailValue);
            var samePassword = passwordValue !== '' && passwordValue === confirmValue;
            var isReady = hasAllFields;

            registerButton.classList.toggle('ready', isReady && validAcademicEmail && samePassword);

            if (!hasAllFields) {
                registerButton.textContent = 'Complete all fields';
            } else if (!validAcademicEmail) {
                registerButton.textContent = 'Use @edu.ac.ma email';
            } else if (!samePassword) {
                registerButton.textContent = 'Passwords must match';
            } else {
                registerButton.textContent = 'Register now';
            }
        }

        [fullNameInput, emailInput, passwordInput, confirmInput].forEach(function (input) {
            if (input) {
                input.addEventListener('input', updateRegisterButtonState);
            }
        });
        updateRegisterButtonState();

        if (loginForm) {
            loginForm.addEventListener('submit', function (event) {
                var loginValue = document.getElementById('login_value');
                var loginPassword = document.getElementById('login_password');
                if (!loginValue.value.trim() || !loginPassword.value.trim()) {
                    event.preventDefault();
                    alert('Please fill in username/email and password.');
                }
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', function (event) {
                var fullName = document.getElementById('full_name');
                var email = document.getElementById('email');
                var password = document.getElementById('register_password');
                var confirm = document.getElementById('confirm_password');

                if (!fullName.value.trim() || !email.value.trim() || !password.value.trim() || !confirm.value.trim()) {
                    event.preventDefault();
                    alert('Please fill in all fields.');
                    return;
                }

                if (!/@edu\.ac\.ma$/i.test(email.value.trim())) {
                    event.preventDefault();
                    alert('Please use an academic email ending with @edu.ac.ma.');
                    return;
                }

                if (password.value !== confirm.value) {
                    event.preventDefault();
                    alert('Passwords do not match.');
                }
            });
        }
    })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
