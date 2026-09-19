<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$u = exigirLogin();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$codigo = trim((string)($input['servico'] ?? ''));
$qtd = (float)($input['quantidade'] ?? 1);
$referencia = isset($input['referencia']) ? (string)$input['referencia'] : null;

if ($codigo === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'erro'=>'SERVICO_OBRIGATORIO']);
    exit;
}

$res = consumirServico((int)$u['id'], $codigo, $qtd, $referencia, ['origem'=>'api']);
http_response_code(!empty($res['ok']) ? 200 : 403);
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
