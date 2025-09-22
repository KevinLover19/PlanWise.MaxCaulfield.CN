<?php
// /www/wwwroot/planwise.maxcaulfield.cn/logout.php
// 用户登出功能

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 清除所有会话数据
$_SESSION = array();

// 如果使用了cookie，也要清除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到首页
header('Location: /index.php?logout=success');
exit();
