<?php<?php

session_start();// Prevenir cualquier salida antes de los headers

require_once 'conexion.php';ob_start();

require_once 'chatbot_sordos.php';

<?php
// API DEPRECADA: Unificada en chatbot_api_clean.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

http_response_code(410); // Gone
echo json_encode([
    'success' => false,
    'error' => 'Este endpoint fue unificado. Usa /chatbot_api_clean.php para POST {"mensaje":...} y GET/POST de información.',
    'endpoint_unificado' => 'chatbot_api_clean.php'
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>