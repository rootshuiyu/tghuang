<?php
// 设置响应头为JSON格式
header('Content-Type: application/json');

// 定义消息存储文件路径
$messageFile = 'messages.json';

// 确保文件存在
if (!file_exists($messageFile)) {
    file_put_contents($messageFile, json_encode([]));
}

// 获取请求动作
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send':
        // 发送消息
        $user = $_POST['user'] ?? 'Anonymous';
        $message = $_POST['message'] ?? '';

        if (!empty($message)) {
            // 读取现有消息
            $messages = json_decode(file_get_contents($messageFile), true);

            // 创建新消息
            $newMessage = [
                'id' => uniqid(),
                'user' => $user,
                'message' => $message,
                'timestamp' => time()
            ];

            // 添加新消息
            $messages[] = $newMessage;

            // 保存消息
            file_put_contents($messageFile, json_encode($messages));

            echo json_encode(['status' => 'success', 'message' => 'Message sent']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
        }
        break;

    case 'clear':
        // 清空消息
        file_put_contents($messageFile, json_encode([]));
        echo json_encode(['status' => 'success', 'message' => 'Messages cleared']);
        break;

    case 'get':
        // 获取消息
        $lastId = $_GET['lastId'] ?? '';

        // 读取现有消息
        $messages = json_decode(file_get_contents($messageFile), true);

        if (!empty($lastId)) {
            // 增量获取消息（只获取ID大于lastId的消息）
            $newMessages = array_filter($messages, function($msg) use ($lastId) {
                return $msg['id'] > $lastId;
            });
            echo json_encode(['status' => 'success', 'messages' => array_values($newMessages)]);
        } else {
            // 获取所有消息
            echo json_encode(['status' => 'success', 'messages' => $messages]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>