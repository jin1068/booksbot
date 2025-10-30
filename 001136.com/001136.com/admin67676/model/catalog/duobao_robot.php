<?php
namespace Opencart\Admin\Model\Catalog;
/**
 * Class DuobaoRobot
 * 
 * 一元夺宝机器人配置管理模型
 * 
 * @package Opencart\Admin\Model\Catalog
 */
class DuobaoRobot extends \Opencart\System\Engine\Model {
    
    /**
     * 获取机器人时间段配置列表
     *
     * @param int $issue_id
     * @return array
     */
    public function getSchedules($issue_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "duobao_robot_schedule` 
                WHERE `issue_id` = '" . (int)$issue_id . "' 
                ORDER BY `sort_order` ASC, `start_time` ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * 添加机器人时间段配置
     *
     * @param int $issue_id
     * @param array $data
     * @return int
     */
    public function addSchedule($issue_id, $data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "duobao_robot_schedule` SET 
                `issue_id` = '" . (int)$issue_id . "',
                `start_time` = '" . $this->db->escape($data['start_time']) . "',
                `end_time` = '" . $this->db->escape($data['end_time']) . "',
                `target_percent` = '" . (int)$data['target_percent'] . "',
                `purchase_interval_min` = '" . (int)$data['purchase_interval_min'] . "',
                `purchase_interval_max` = '" . (int)$data['purchase_interval_max'] . "',
                `quantity_min` = '" . (int)$data['quantity_min'] . "',
                `quantity_max` = '" . (int)$data['quantity_max'] . "',
                `is_completed` = 0,
                `sort_order` = '" . (int)$data['sort_order'] . "',
                `date_added` = NOW()";
        
        $this->db->query($sql);
        
        return $this->db->getLastId();
    }
    
    /**
     * 更新机器人时间段配置
     *
     * @param int $schedule_id
     * @param array $data
     */
    public function editSchedule($schedule_id, $data) {
        $sql = "UPDATE `" . DB_PREFIX . "duobao_robot_schedule` SET 
                `start_time` = '" . $this->db->escape($data['start_time']) . "',
                `end_time` = '" . $this->db->escape($data['end_time']) . "',
                `target_percent` = '" . (int)$data['target_percent'] . "',
                `purchase_interval_min` = '" . (int)$data['purchase_interval_min'] . "',
                `purchase_interval_max` = '" . (int)$data['purchase_interval_max'] . "',
                `quantity_min` = '" . (int)$data['quantity_min'] . "',
                `quantity_max` = '" . (int)$data['quantity_max'] . "',
                `sort_order` = '" . (int)$data['sort_order'] . "'
                WHERE `schedule_id` = '" . (int)$schedule_id . "'";
        
        $this->db->query($sql);
    }
    
    /**
     * 删除机器人时间段配置
     *
     * @param int $schedule_id
     */
    public function deleteSchedule($schedule_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "duobao_robot_schedule` 
                WHERE `schedule_id` = '" . (int)$schedule_id . "'";
        
        $this->db->query($sql);
    }
    
    /**
     * 批量保存机器人时间段配置
     *
     * @param int $issue_id
     * @param array $schedules
     */
    public function saveSchedules($issue_id, $schedules) {
        // 删除旧配置
        $this->db->query("DELETE FROM `" . DB_PREFIX . "duobao_robot_schedule` 
                         WHERE `issue_id` = '" . (int)$issue_id . "'");
        
        // 添加新配置
        if (!empty($schedules)) {
            foreach ($schedules as $schedule) {
                $this->addSchedule($issue_id, $schedule);
            }
        }
    }
    
    /**
     * 获取机器人购买统计
     *
     * @param int $issue_id
     * @return array
     */
    public function getRobotStats($issue_id) {
        // 获取机器人购买总数
        $robot_query = $this->db->query("
            SELECT COUNT(*) as total_robot_tickets
            FROM `" . DB_PREFIX . "duobao_ticket`
            WHERE `issue_id` = '" . (int)$issue_id . "'
            AND `ticket_type` = 'robot'
        ");
        
        // 获取真人购买总数
        $real_query = $this->db->query("
            SELECT COUNT(*) as total_real_tickets
            FROM `" . DB_PREFIX . "duobao_ticket`
            WHERE `issue_id` = '" . (int)$issue_id . "'
            AND `ticket_type` = 'real'
        ");
        
        // 获取期次信息
        $issue_query = $this->db->query("
            SELECT `total_slots`, `joined_slots`, `robot_current_purchases`
            FROM `" . DB_PREFIX . "duobao_issue`
            WHERE `issue_id` = '" . (int)$issue_id . "'
        ");
        
        $issue_data = $issue_query->row;
        $total_slots = (int)($issue_data['total_slots'] ?? 0);
        $joined_slots = (int)($issue_data['joined_slots'] ?? 0);
        $robot_purchases = (int)($issue_data['robot_current_purchases'] ?? 0);
        
        $robot_tickets = (int)($robot_query->row['total_robot_tickets'] ?? 0);
        $real_tickets = (int)($real_query->row['total_real_tickets'] ?? 0);
        
        $total = $robot_tickets + $real_tickets;
        $progress = $total_slots > 0 ? ($total / $total_slots) * 100 : 0;
        
        return [
            'total_slots' => $total_slots,
            'robot_tickets' => $robot_tickets,
            'real_tickets' => $real_tickets,
            'total_tickets' => $total,
            'remaining' => max(0, $total_slots - $total),
            'progress' => round($progress, 2),
            'robot_percent' => $total > 0 ? round(($robot_tickets / $total) * 100, 2) : 0,
            'real_percent' => $total > 0 ? round(($real_tickets / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * 获取机器人购买号码列表
     *
     * @param int $issue_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getRobotTickets($issue_id, $start = 0, $limit = 20) {
        $sql = "SELECT `ticket_id`, `ticket_no`, `date_added`
                FROM `" . DB_PREFIX . "duobao_ticket`
                WHERE `issue_id` = '" . (int)$issue_id . "'
                AND `ticket_type` = 'robot'
                ORDER BY `ticket_id` ASC
                LIMIT " . (int)$start . "," . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * 获取真人购买号码列表
     *
     * @param int $issue_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getRealTickets($issue_id, $start = 0, $limit = 20) {
        $sql = "SELECT t.`ticket_id`, t.`ticket_no`, t.`customer_id`, t.`date_added`,
                       CONCAT(c.`firstname`, ' ', c.`lastname`) as customer_name,
                       c.`email` as customer_email
                FROM `" . DB_PREFIX . "duobao_ticket` t
                LEFT JOIN `" . DB_PREFIX . "customer` c ON t.`customer_id` = c.`customer_id`
                WHERE t.`issue_id` = '" . (int)$issue_id . "'
                AND t.`ticket_type` = 'real'
                ORDER BY t.`ticket_id` ASC
                LIMIT " . (int)$start . "," . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * 获取机器人日志
     *
     * @param int $issue_id
     * @param int $limit
     * @return array
     */
    public function getRobotLogs($issue_id, $limit = 50) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "duobao_robot_log`
                WHERE `issue_id` = '" . (int)$issue_id . "'
                ORDER BY `date_added` DESC
                LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * 验证票号类型
     *
     * @param string $ticket_no
     * @return string 'real' 或 'robot' 或 'not_found'
     */
    public function getTicketType($ticket_no) {
        $sql = "SELECT `ticket_type` FROM `" . DB_PREFIX . "duobao_ticket`
                WHERE `ticket_no` = '" . $this->db->escape($ticket_no) . "'
                LIMIT 1";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            return $query->row['ticket_type'];
        }
        
        return 'not_found';
    }
    
    /**
     * 自动派奖给真人
     *
     * @param int $issue_id
     * @return array|null 中奖票据信息或null
     */
    public function autoDrawToReal($issue_id) {
        // 随机获取一个真人购买的票据
        $sql = "SELECT `ticket_id`, `ticket_no`, `customer_id`, `order_id`
                FROM `" . DB_PREFIX . "duobao_ticket`
                WHERE `issue_id` = '" . (int)$issue_id . "'
                AND `ticket_type` = 'real'
                ORDER BY RAND()
                LIMIT 1";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            return $query->row;
        }
        
        return null;
    }
    
    /**
     * 自动派奖给机器人
     *
     * @param int $issue_id
     * @return array|null 中奖票据信息或null
     */
    public function autoDrawToRobot($issue_id) {
        // 随机获取一个机器人购买的票据
        $sql = "SELECT `ticket_id`, `ticket_no`, `customer_id`, `order_id`
                FROM `" . DB_PREFIX . "duobao_ticket`
                WHERE `issue_id` = '" . (int)$issue_id . "'
                AND `ticket_type` = 'robot'
                ORDER BY RAND()
                LIMIT 1";
        
        $query = $this->db->query($sql);
        
        if ($query->num_rows > 0) {
            return $query->row;
        }
        
        return null;
    }
}

