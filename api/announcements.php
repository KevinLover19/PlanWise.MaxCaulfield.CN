<?php
/**
 * 公告系统API接口
 * 为前台提供公告数据
 */

require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pdo = getConnection();

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_active_announcements':
            getActiveAnnouncements();
            break;
            
        case 'mark_as_viewed':
            markAnnouncementAsViewed();
            break;
            
        default:
            echo json_encode(['error' => '无效的操作']);
    }
    
} catch (Exception $e) {
    error_log("公告API错误: " . $e->getMessage());
    echo json_encode(['error' => '系统错误']);
}

function getActiveAnnouncements() {
    global $pdo;
    
    // 获取用户信息
    $user_id = $_SESSION['user_id'] ?? null;
    $user_membership = 'all'; // 默认为所有用户
    
    if ($user_id) {
        // 获取用户会员类型
        $stmt = $pdo->prepare("
            SELECT COALESCE(q.membership_type, 'free') as membership_type 
            FROM users u
            LEFT JOIN planwise_user_quotas q ON u.id = q.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_membership = $user_data['membership_type'] ?? 'free';
    }
    
    // 获取活跃的公告
    $stmt = $pdo->prepare("
        SELECT id, title, content, announcement_type, show_popup, priority, target_audience, created_at
        FROM planwise_announcements 
        WHERE is_active = 1 
          AND (end_date IS NULL OR end_date > NOW())
          AND (target_audience = 'all' OR target_audience = ? OR target_audience = 'all')
        ORDER BY priority DESC, created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$user_membership]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 如果用户已登录，检查哪些公告已经查看过
    if ($user_id) {
        $announcement_ids = array_column($announcements, 'id');
        if (!empty($announcement_ids)) {
            $placeholders = str_repeat('?,', count($announcement_ids) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT announcement_id 
                FROM planwise_announcement_views 
                WHERE user_id = ? AND announcement_id IN ($placeholders)
            ");
            $stmt->execute(array_merge([$user_id], $announcement_ids));
            $viewed_announcements = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 标记已查看的公告
            foreach ($announcements as &$announcement) {
                $announcement['is_viewed'] = in_array($announcement['id'], $viewed_announcements);
            }
        }
    } else {
        // 游客用户，所有公告都标记为未查看
        foreach ($announcements as &$announcement) {
            $announcement['is_viewed'] = false;
        }
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements,
        'user_logged_in' => !empty($user_id)
    ]);
}

function markAnnouncementAsViewed() {
    global $pdo;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    
    if (!$user_id || $announcement_id <= 0) {
        echo json_encode(['error' => '无效的参数']);
        return;
    }
    
    // 记录查看状态
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO planwise_announcement_views (user_id, announcement_id, viewed_at)
        VALUES (?, ?, NOW())
    ");
    
    $result = $stmt->execute([$user_id, $announcement_id]);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? '标记成功' : '标记失败'
    ]);
}
?>
