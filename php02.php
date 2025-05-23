if (!hash_equals($_POST['signature'], hash_hmac('sha256', $_POST['email'], '你的签名密钥'))) {
    die('非法请求签名');
}
