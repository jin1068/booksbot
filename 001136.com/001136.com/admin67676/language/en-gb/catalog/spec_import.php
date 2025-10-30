<?php
// Heading
$_['heading_title']    = 'Spec Data Importer';

// Text
$_['text_home']        = 'Dashboard';
$_['text_catalog']     = 'Catalog';
$_['text_description'] = 'Import the bilingual smartphone & tablet specification dataset as editable products under <b>Catalog &gt; Products</b>. The importer links data to your fixed phone/tablet categories and builds colour / storage options with pricing.';
$_['text_notes']       = 'Importer Notes';
$_['text_notes_list']  = '• Reads the bilingual specification dataset and converts every model into a configurable product with colour & storage options.<br/>• Automatically applies the tiered promotion (满1000减100 / 满2000减200 / 满3000减350) so the displayed price already reflects the discount.<br/>• Existing products with the same name are skipped, therefore you can re-run the importer safely whenever the dataset is updated.<br/>• Images remain empty by default – upload your own marketing images / banners afterwards if required.';
$_['text_stats']       = 'Last Import Summary';
$_['text_stats_detail']= 'Products created: %d | Skipped (duplicates): %d';
$_['text_last_run']    = 'Last run: %s';
$_['text_success']     = '%d products created. %d existing records were skipped.';
$_['text_no_history']  = 'No import has been executed yet.';
$_['text_preview_title']   = 'Import Preview';
$_['text_preview_created'] = 'Products to be created';
$_['text_preview_skipped'] = 'Products skipped / duplicates';
$_['text_preview_warnings']= 'Warnings';
$_['text_preview_error']   = 'Preview failed, please retry.';
$_['text_recent_warnings'] = 'Latest warnings';
$_['text_preview_empty']   = 'No entries';
$_['text_reason_duplicate']      = 'Existing product detected, automatically skipped.';
$_['text_reason_invalid_storage']= 'Missing storage capacity or price information.';
$_['text_reason_missing_name']   = 'Missing product name in source data.';

// Button
$_['button_import']    = 'Run Import';
$_['button_preview']   = 'Preview Import';
$_['button_close']     = 'Close';

// Error
$_['error_permission'] = 'Warning: You do not have permission to use the spec importer.';
