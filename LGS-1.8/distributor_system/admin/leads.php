<?php
// admin/leads.php
require_once __DIR__ . '/../functions.php';
require_role('admin');

// Load controller (handles DB + logic)
require_once __DIR__ . '/leads_controller.php';

// Render view (HTML only)
require_once __DIR__ . '/leads_view.php';
