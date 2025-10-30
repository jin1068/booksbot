<?php
namespace Opencart\Admin\Controller\Catalog;

class CategoryAnalysis extends \Opencart\System\Engine\Controller {
    
    /**
     * 分析分类和产品分布
     */
    public function index(): void {
        $this->load->language('catalog/category');
        $this->document->setTitle('分类产品分析');
        
        // 获取所有分类及其产品数量
        $categories_with_counts = $this->getCategoriesWithProductCounts();
        
        // 分析结果
        $data['empty_categories'] = [];
        $data['duplicate_products'] = [];
        $data['misplaced_products'] = [];
        
        // 1. 找出没有商品的二级分类
        foreach ($categories_with_counts as $category) {
            if ($category['parent_id'] != 0 && $category['product_count'] == 0) {
                $data['empty_categories'][] = $category;
            }
        }
        
        // 2. 查找重复分类的商品（如智能手表）
        $duplicate_check = $this->findDuplicateProducts();
        $data['duplicate_products'] = $duplicate_check;
        
        // 3. 查找分类错误的商品
        $misplaced_check = $this->findMisplacedProducts();
        $data['misplaced_products'] = $misplaced_check;
        
        // 输出结果
        echo "<h1>分类产品分析报告</h1>";
        
        echo "<h2>1. 没有商品的二级分类：</h2>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>分类ID</th><th>分类名称</th><th>父分类</th></tr>";
        foreach ($data['empty_categories'] as $cat) {
            echo "<tr>";
            echo "<td>{$cat['category_id']}</td>";
            echo "<td>{$cat['name']}</td>";
            echo "<td>{$cat['parent_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>2. 存在于多个分类的商品：</h2>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>商品ID</th><th>商品名称</th><th>所在分类</th></tr>";
        foreach ($data['duplicate_products'] as $product) {
            echo "<tr>";
            echo "<td>{$product['product_id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>" . implode('<br>', $product['categories']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>3. 分类错误的商品：</h2>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>商品ID</th><th>商品名称</th><th>当前分类</th><th>建议分类</th></tr>";
        foreach ($data['misplaced_products'] as $product) {
            echo "<tr>";
            echo "<td>{$product['product_id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>{$product['current_category']}</td>";
            echo "<td>{$product['suggested_category']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    /**
     * 获取所有分类及其产品数量
     */
    private function getCategoriesWithProductCounts(): array {
        $language_id = $this->config->get('config_language_id');
        
        $sql = "SELECT 
                c.category_id,
                c.parent_id,
                cd.name,
                parent_cd.name as parent_name,
                COUNT(DISTINCT p2c.product_id) as product_count
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$language_id . "')
            LEFT JOIN " . DB_PREFIX . "category parent_c ON (c.parent_id = parent_c.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description parent_cd ON (parent_c.category_id = parent_cd.category_id AND parent_cd.language_id = '" . (int)$language_id . "')
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (c.category_id = p2c.category_id)
            GROUP BY c.category_id
            ORDER BY c.parent_id, c.sort_order";
            
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * 查找存在于多个分类的商品
     */
    private function findDuplicateProducts(): array {
        $language_id = $this->config->get('config_language_id');
        
        // 查找特定类型的产品（如智能手表、游戏本等）
        $keywords = ['智能手表', 'Smart Watch', '游戏本', 'Gaming Laptop', '显示器', 'Monitor'];
        $duplicate_products = [];
        
        foreach ($keywords as $keyword) {
            $sql = "SELECT 
                    p.product_id,
                    pd.name as product_name,
                    GROUP_CONCAT(CONCAT(cd.name, ' (', c.category_id, ')') SEPARATOR ', ') as categories,
                    COUNT(DISTINCT c.category_id) as category_count
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$language_id . "')
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
                LEFT JOIN " . DB_PREFIX . "category c ON (p2c.category_id = c.category_id)
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id AND cd.language_id = '" . (int)$language_id . "')
                WHERE pd.name LIKE '%" . $this->db->escape($keyword) . "%'
                GROUP BY p.product_id
                HAVING category_count > 1";
                
            $query = $this->db->query($sql);
            
            foreach ($query->rows as $row) {
                $duplicate_products[] = [
                    'product_id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'categories' => explode(', ', $row['categories'])
                ];
            }
        }
        
        return $duplicate_products;
    }
    
    /**
     * 查找分类错误的商品
     */
    private function findMisplacedProducts(): array {
        $language_id = $this->config->get('config_language_id');
        $misplaced = [];
        
        // 检查显示器分类中的游戏本
        $sql = "SELECT 
                p.product_id,
                pd.name,
                cd.name as category_name
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$language_id . "')
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$language_id . "')
            WHERE cd.name LIKE '%显示器%' 
            AND (pd.name LIKE '%游戏本%' OR pd.name LIKE '%笔记本%' OR pd.name LIKE '%Laptop%' OR pd.name LIKE '%GF63%')";
            
        $query = $this->db->query($sql);
        
        foreach ($query->rows as $row) {
            $suggested = '游戏笔记本';
            if (strpos($row['name'], '轻薄') !== false || strpos($row['name'], 'ultrabook') !== false) {
                $suggested = '轻薄本';
            }
            
            $misplaced[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'current_category' => $row['category_name'],
                'suggested_category' => $suggested
            ];
        }
        
        // 检查其他可能的错误分类
        // 例如：检查手机分类中的平板电脑
        $sql2 = "SELECT 
                p.product_id,
                pd.name,
                cd.name as category_name
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$language_id . "')
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id AND cd.language_id = '" . (int)$language_id . "')
            WHERE cd.name LIKE '%手机%' 
            AND (pd.name LIKE '%平板%' OR pd.name LIKE '%iPad%' OR pd.name LIKE '%Tablet%')";
            
        $query2 = $this->db->query($sql2);
        
        foreach ($query2->rows as $row) {
            $misplaced[] = [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'current_category' => $row['category_name'],
                'suggested_category' => '平板电脑'
            ];
        }
        
        return $misplaced;
    }
}
