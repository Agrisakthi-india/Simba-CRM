<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'SME CRM'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }
        body {
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%; animation: gradient 15s ease infinite;
            position: relative;
        }
        @keyframes gradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .login-card {
            background: rgba(255, 255, 255, 0.9); border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px);
        }
        .btn-login {
            background-image: linear-gradient(to right, #DA22FF, #9733EE);
            transition: 0.5s; background-size: 200% auto; color: white; border: none;
        }
        .btn-login:hover { background-position: right center; }
        .auth-footer { position: absolute; bottom: 1rem; width: 100%; text-align: center; color: rgba(255, 255, 255, 0.7); }
        .login-card a { color: #0d6efd; text-decoration: none; }
    </style>
</head>
<body>