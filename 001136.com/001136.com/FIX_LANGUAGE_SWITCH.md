# 语言切换与轮播图问题修复说明

## 修复日期
2025年10月30日

## 问题描述

### 问题1: 语言切换跳转到首页
- **现象**: 在产品页或其他页面切换语言时，会跳转到首页而不是停留在当前页
- **原因**: 
  1. `language.php` 控制器使用 `HTTP_REFERER` 获取当前URL不够可靠
  2. 没有使用 `REQUEST_URI` 获取真实访问路径
  3. SEO友好URL和hash片段未正确保留

### 问题2: Lucky Purchase 轮播图消失
- **可能原因**: 
  1. 模块未在后台正确配置到首页布局
  2. 语言切换后页面状态丢失
  3. JavaScript初始化时机问题

## 修复内容

### 1. catalog/controller/common/language.php

#### 修复 index() 方法
```php
// 改进前: 仅使用 HTTP_REFERER
// 改进后: 优先使用 REQUEST_URI，保留hash片段

// 首先尝试获取当前实际访问的URL
$config_url = $this->config->get('config_url');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

if ($request_uri) {
    // 使用实际访问的URI构建完整URL
    $current_url = rtrim($config_url, '/') . $request_uri;
}

// 为每个语言生成redirect时保留hash
if (!empty($url_parts['fragment'])) {
    $redirect_url .= '#' . $url_parts['fragment'];
}
```

#### 修复 save() 方法
```php
// 保留hash片段（如 #lucky-purchase）
if (!empty($url_info['fragment'])) {
    $redirect .= '#' . $url_info['fragment'];
}
```

### 2. catalog/view/template/common/language.twig

#### 改进JavaScript逻辑
```javascript
// 使用完整的当前URL，包含hash
redirectInput.value = window.location.href;

// 添加错误日志
.catch(function(err) {
    console.error('Language switch error:', err);
    if (fallbackRedirect) {
        window.location.href = fallbackRedirect;
    }
});
```

## 测试步骤

### 测试语言切换
1. 访问任意产品页面，例如: `/index.php?route=product/product&product_id=247&language=zh-cn`
2. 点击顶部地球图标切换语言（中文 ↔ English）
3. **预期结果**: 停留在同一产品页，仅语言参数改变
4. **验证**: URL应该变为 `...&language=en-gb`，页面内容显示英文

### 测试hash锚点保留
1. 访问首页并滚动到Lucky Purchase区域: `/?language=zh-cn#lucky-purchase`
2. 切换语言
3. **预期结果**: 切换后仍然停留在Lucky Purchase区域
4. **验证**: URL保留 `#lucky-purchase`，页面自动滚动到该区域

### 测试Lucky Purchase显示
1. 清除浏览器缓存
2. 访问首页: `/?language=zh-cn`
3. **预期结果**: 看到"Lucky Purchase"模块显示4个产品卡片
4. **检查**: 
   - 运行 `php check_duobao_config.php` 检查模块配置
   - 确认模块已添加到首页布局的 content_bottom 或 content_top 位置

## 后续检查

如果Lucky Purchase仍然不显示，请执行以下步骤:

1. **检查模块配置**
   ```bash
   php check_duobao_config.php
   ```

2. **后台检查**
   - 登录后台: `/admin67676`
   - 进入 Extensions → Modules
   - 确认 Duobao 模块已安装并启用
   - 进入 Design → Layouts
   - 编辑 "Home" 布局
   - 确认 content_bottom 或 content_top 位置包含 duobao 模块

3. **数据库检查**
   - 检查 `oc_layout_module` 表
   - 查找 `code = 'opencart.duobao'` 或类似记录
   - 确认 `layout_id` 对应首页布局（通常是1）

4. **清除缓存**
   ```bash
   # 删除缓存文件
   rm -rf system/storage/cache/*
   ```

## 文件变更清单

- ✅ `catalog/controller/common/language.php` - 改进URL构建和hash保留
- ✅ `catalog/view/template/common/language.twig` - 增强JavaScript错误处理
- ✅ `check_duobao_config.php` - 新增模块配置检查工具

## 注意事项

1. **REQUEST_URI优先**: 现在优先使用 `$_SERVER['REQUEST_URI']` 获取准确的当前路径
2. **Hash保留**: URL中的hash片段（如 `#lucky-purchase`）会在语言切换时保留
3. **向后兼容**: 如果REQUEST_URI不可用，会回退到HTTP_REFERER，再回退到构建URL
4. **错误处理**: JavaScript增加了console.error输出，方便调试

## 测试环境

- PHP 7.4+
- OpenCart 4.x
- 浏览器: Chrome/Firefox/Edge (最新版本)

## 已知限制

1. 如果用户浏览器禁用了JavaScript，语言切换会使用传统的链接跳转方式
2. 某些极端情况下（如反向代理配置），REQUEST_URI可能不准确

## 支持

如遇问题，请检查:
1. 浏览器控制台错误信息
2. PHP错误日志
3. OpenCart系统日志 (`system/storage/logs/error.log`)
