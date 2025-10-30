<?php
namespace Opencart\Admin\Controller\Extension\Module;

class CategoryImage extends \Opencart\System\Engine\Controller {
    private string $error = '';
    
    public function index(): void {
        $this->load->language('extension/module/category_image');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_category_image', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/category_image', 'user_token=' . $this->session->data['user_token'])
        ];
        
        $data['action'] = $this->url->link('extension/module/category_image', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        
        // Get module settings
        if (isset($this->request->post['module_category_image_status'])) {
            $data['module_category_image_status'] = $this->request->post['module_category_image_status'];
        } else {
            $data['module_category_image_status'] = $this->config->get('module_category_image_status');
        }
        
        if (isset($this->request->post['module_category_image_limit'])) {
            $data['module_category_image_limit'] = $this->request->post['module_category_image_limit'];
        } else {
            $data['module_category_image_limit'] = $this->config->get('module_category_image_limit') ?: 8;
        }
        
        if (isset($this->request->post['module_category_image_width'])) {
            $data['module_category_image_width'] = $this->request->post['module_category_image_width'];
        } else {
            $data['module_category_image_width'] = $this->config->get('module_category_image_width') ?: 200;
        }
        
        if (isset($this->request->post['module_category_image_height'])) {
            $data['module_category_image_height'] = $this->request->post['module_category_image_height'];
        } else {
            $data['module_category_image_height'] = $this->config->get('module_category_image_height') ?: 200;
        }
        
        if (isset($this->request->post['module_category_image_show_title'])) {
            $data['module_category_image_show_title'] = $this->request->post['module_category_image_show_title'];
        } else {
            $data['module_category_image_show_title'] = $this->config->get('module_category_image_show_title') !== null ? $this->config->get('module_category_image_show_title') : 1;
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/category_image', $data));
    }
    
    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/module/category_image')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}

