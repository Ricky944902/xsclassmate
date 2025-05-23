<?php
// 配置文件路径（需设置为不可网页访问的目录）
define('LOG_FILE', __DIR__.'/secure/feedback.log');
define('LOCK_DIR', __DIR__.'/secure/locks/');

// 基础防护设置
header('Content-Type: text/html; charset=utf-8');
setlocale(LC_ALL, 'zh_CN.utf8');

// 防护验证函数
function validateSubmission() {
    // 蜜罐检测
    if (!empty($_POST['website'])) {
        return '非法提交行为';
    }

    // 必填字段验证
    $required = ['name', 'email', 'message'];
    foreach ($required as $field) {
        if (empty(trim($_POST[$field]))) {
            return '请填写所有必填字段';
        }
    }

    // 邮箱格式验证
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        return '邮箱格式不正确';
    }

    // 频率限制（同一IP 5秒内只能提交一次）
    $ip = md5($_SERVER['REMOTE_ADDR']); // 使用MD5哈希保护真实IP
    $lockFile = LOCK_DIR.$ip;
    
    if (file_exists($lockFile)) {
        if (time() - filemtime($lockFile) < 5) {
            return '操作过于频繁，请5秒后再试';
        }
    }
    touch($lockFile);

    return true;
}

// 主处理流程
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 执行验证
    $validation = validateSubmission();
    
    if ($validation !== true) {
        die("<script>alert('$validation');history.back();</script>");
    }

    // 准备日志数据
    $logData = [
        'time'    => date('Y-m-d H:i:s'),
        'ip'      => md5($_SERVER['REMOTE_ADDR']),
        'name'    => htmlspecialchars($_POST['name']),
        'email'   => htmlspecialchars($_POST['email']),
        'message' => htmlspecialchars($_POST['message'])
    ];

    // 写入日志文件
    $logEntry = json_encode($logData, JSON_UNESCAPED_UNICODE).PHP_EOL;
    
    if (file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX)) {
        // 可选邮件通知（需服务器支持）
        /*
        $to = 'ricky_xs03@163.com';
        $subject = '新网站反馈 - '.date('Y-m-d H:i');
        $body = print_r($logData, true);
        mail($to, $subject, $body);
        */
        
        echo <<<HTML
        <script>
            alert('提交成功，感谢您的反馈！');
            location.href = history.back();
        </script>
        HTML;
    } else {
        die("<script>alert('系统繁忙，请稍后再试');history.back();</script>");
    }
} else {
    http_response_code(405);
    die('Method Not Allowed');
}
