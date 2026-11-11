<?php
require_once __DIR__.'/config/config.php';require_once __DIR__.'/includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){redirect('index.php');}verify_csrf();$id=current_user()['id']??null;if($id)log_audit($pdo,(int)$id,'LOGOUT','user',(int)$id);$_SESSION=[];session_regenerate_id(true);set_flash('You have been logged out.');redirect('index.php');
