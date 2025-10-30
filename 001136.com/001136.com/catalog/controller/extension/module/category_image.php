<?php
namespace Opencart\Catalog\Controller\Extension\Module;

class CategoryImage extends \Opencart\System\Engine\Controller {
    public function index(array $setting): string {
        $this->load->language('extension/module/category_image');
        
        $this->load->model('catalog/category');
        $this->load->model('tool/image');
        
        $data['heading_title'] = $this->language->get('heading_title');
        
        // Get module settings
        $limit = !empty($setting['limit']) ? (int)$setting['limit'] : 8;
        $image_width = !empty($setting['width']) ? (int)$setting['width'] : 200;
        $image_height = !empty($setting['height']) ? (int)$setting['height'] : 200;
        $show_title = isset($setting['show_title']) ? $setting['show_title'] : true;
        
        $data['categories'] = [];
        
        // Get top level categories
        $categories = $this->model_catalog_category->getCategories(0);
        
        $count = 0;
        foreach ($categories as $category) {
            if ($count >= $limit) {
                break;
            }
            
            // Only show categories with images
            if ($category['image']) {
                $category_info = $this->model_catalog_category->getCategory($category['category_id']);
                
                if ($category_info && $category_info['status']) {
                    // Get category description (strip HTML tags and limit length)
                    $description = strip_tags(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'));
                    if (mb_strlen($description) > 60) {
                        $description = mb_substr($description, 0, 60) . '...';
                    }
                    
                    $data['categories'][] = [
                        'category_id' => $category_info['category_id'],
                        'name'        => $category_info['name'],
                        'description' => $description,
                        'image'       => $this->model_tool_image->resize($category_info['image'], $image_width, $image_height),
                        'href'        => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'])
                    ];
                    
                    $count++;
                }
            }
        }
        
        $data['show_title'] = $show_title;
        
        if ($data['categories']) {
            return $this->load->view('extension/module/category_image', $data);
        }
        
        return '';
    }
}

